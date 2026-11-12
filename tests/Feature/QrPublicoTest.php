<?php

namespace Tests\Feature;

use App\Enums\EstadoOrden;
use App\Enums\Rol;
use App\Models\OrdenServicio;
use App\Models\QrConsulta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

/** La página que ve el cliente al escanear el QR, sin iniciar sesión. */
class QrPublicoTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
    }

    public function test_muestra_servicios_realizados_y_proximo_servicio_sin_datos_personales(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $mecanico = $this->usuario(Rol::Mecanico);
        $orden = $this->ordenEnReparacion($recepcion, $mecanico);
        $orden->trabajos()->create(['servicio_id' => $this->servicio()->id]);
        $orden->cambiarEstado(EstadoOrden::Terminado, $mecanico);
        $entrega = $orden->iniciarEntrega($recepcion);
        $entrega->completar();
        $orden->cambiarEstado(EstadoOrden::Entregado, $recepcion);
        $vehiculo = $orden->vehiculo;

        $this->get(route('qr.show', $vehiculo->qr_token))
            ->assertOk()
            ->assertSee('Nissan Versa')
            ->assertSee('Cambio de aceite')
            ->assertSee('Próximo servicio')
            ->assertSee(number_format($orden->recepcion->km + 10000))
            // Datos del dueño: nunca en la página pública
            ->assertDontSee($vehiculo->cliente->telefono)
            ->assertDontSee('Pérez')
            // Precios: solo si la preferencia lo permite (por defecto no)
            ->assertDontSee('Total:');

        $this->assertSame(1, QrConsulta::count(), 'Registra el escaneo');
    }

    public function test_un_token_inventado_o_regenerado_da_404(): void
    {
        $vehiculo = $this->vehiculo();
        $viejo = $vehiculo->qr_token;

        $this->get('/v/NO-EXISTE')->assertNotFound();

        $vehiculo->regenerarQr();
        $this->get('/v/' . $viejo)->assertNotFound();
        $this->get('/v/' . $vehiculo->qr_token)->assertOk();

        $vehiculo->update(['qr_activo' => false]);
        $this->get('/v/' . $vehiculo->qr_token)->assertNotFound();
    }

    public function test_avisa_si_el_vehiculo_esta_en_el_taller(): void
    {
        $orden = $this->ordenConRecepcion($this->usuario(Rol::Recepcionista));

        $this->get(route('qr.show', $orden->vehiculo->qr_token))->assertSee('está en el taller');
    }

    public function test_el_cliente_ve_el_diagnostico_y_las_piezas_del_servicio_en_curso(): void
    {
        $orden = $this->ordenEnReparacion($this->usuario(Rol::Recepcionista), $this->usuario(Rol::Mecanico));
        $orden->update(['diagnostico' => 'Balatas delanteras gastadas al 90%.']);
        $orden->trabajos()->create(['servicio_id' => $this->servicio()->id]);
        $orden->refacciones()->create(['descripcion' => 'Juego de balatas delanteras', 'cantidad' => 1, 'costo_unitario' => 321.50]);
        $orden->refacciones()->create(['descripcion' => 'Filtro de aceite', 'cantidad' => 2, 'proporcionada_por_cliente' => true]);

        $this->get(route('qr.show', $orden->vehiculo->qr_token))
            ->assertOk()
            ->assertSeeInOrder(['Servicio en curso', 'Diagnóstico del mecánico', 'Balatas delanteras gastadas al 90%.',
                'Trabajos', 'Cambio de aceite', 'Piezas utilizadas', 'Juego de balatas delanteras', 'Filtro de aceite', 'la trajo usted'])
            // El costo del taller nunca se publica
            ->assertDontSee('321.50');
    }

    public function test_el_historial_publico_muestra_diagnostico_y_piezas_de_servicios_entregados(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $mecanico = $this->usuario(Rol::Mecanico);
        $orden = $this->ordenEnReparacion($recepcion, $mecanico);
        $orden->update(['diagnostico' => 'Fuga en el empaque del cárter.']);
        $orden->trabajos()->create(['servicio_id' => $this->servicio()->id]);
        $orden->refacciones()->create(['descripcion' => 'Empaque de cárter', 'cantidad' => 1]);
        $orden->cambiarEstado(EstadoOrden::Terminado, $mecanico);
        $entrega = $orden->iniciarEntrega($recepcion);
        $entrega->completar();
        $orden->cambiarEstado(EstadoOrden::Entregado, $recepcion);

        $this->get(route('qr.show', $orden->vehiculo->qr_token))
            ->assertDontSee('Servicio en curso')
            ->assertSeeInOrder(['Servicios realizados', 'Fuga en el empaque del cárter.', 'Piezas utilizadas', 'Empaque de cárter']);
    }

    public function test_al_completar_la_recepcion_se_abre_la_hoja_para_imprimir_el_qr(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = OrdenServicio::abrir($this->vehiculo(), $recepcion, 'Servicio general');
        $this->llenarInspeccion($orden->recepcion, $orden->vehiculo->km_actual + 100);

        $this->actingAs($recepcion)
            ->post(route('inspecciones.completar', $orden->recepcion))
            ->assertRedirect(route('ordenes.qr', $orden))
            ->assertSessionHas('ok', 'Recepción completada. Imprime el QR y entrégalo al cliente.');

        $this->get(route('ordenes.qr', $orden))
            ->assertOk()
            ->assertSee('Imprimir QR')
            ->assertSee('<svg', false)
            ->assertSee(route('qr.show', $orden->vehiculo->qr_token))
            ->assertSee($orden->folio)
            // Desde aquí también se abre el acta de la recepción en PDF
            ->assertSee('Acta de recepción en PDF')
            ->assertSee(route('inspecciones.pdf', $orden->recepcion))
            // La hoja se le entrega al cliente: sin sus datos personales
            ->assertDontSee($orden->cliente->telefono);
    }

    public function test_la_orden_tiene_el_boton_para_reimprimir_el_qr_cuando_la_recepcion_esta_completa(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = OrdenServicio::abrir($this->vehiculo(), $recepcion, 'Servicio general');

        $this->actingAs($recepcion)->get(route('ordenes.show', $orden))->assertDontSee('Imprimir QR del cliente');

        $this->llenarInspeccion($orden->recepcion, $orden->vehiculo->km_actual + 100);
        $orden->recepcion->completar();

        $this->get(route('ordenes.show', $orden))->assertSee('Imprimir QR del cliente');
    }

    public function test_el_link_de_la_hoja_del_qr_abre_el_historial_de_servicio(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = OrdenServicio::abrir($this->vehiculo(), $recepcion, 'Servicio general');
        $url = route('qr.show', $orden->vehiculo->qr_token);

        $this->actingAs($recepcion)->get(route('ordenes.qr', $orden))
            ->assertSee('<a href="' . $url . '#historial" target="_blank" class="enlace-qr">', false);

        $this->get($url)->assertSee('id="historial"', false);
    }

    public function test_si_el_qr_esta_desactivado_la_hoja_no_muestra_el_codigo(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = $this->ordenConRecepcion($recepcion);
        $orden->vehiculo->update(['qr_activo' => false]);

        $this->actingAs($recepcion)->get(route('ordenes.qr', $orden))
            ->assertOk()
            ->assertSee('está desactivado')
            ->assertDontSee('<svg', false);
    }

    public function test_un_mecanico_no_imprime_el_qr_de_una_orden_que_no_es_suya(): void
    {
        $orden = $this->ordenConRecepcion($this->usuario(Rol::Recepcionista));

        $this->actingAs($this->usuario(Rol::Mecanico))->get(route('ordenes.qr', $orden))->assertForbidden();
    }
}
