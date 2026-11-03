<?php

namespace Tests\Feature;

use App\Enums\EstadoOrden;
use App\Enums\Rol;
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
        $this->llenarInspeccion($entrega, $orden->recepcion->km);
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
}
