<?php

namespace Tests\Feature;

use App\Enums\EstadoOrden;
use App\Enums\Rol;
use App\Enums\TipoInspeccion;
use App\Models\ChecklistItem;
use App\Models\OrdenServicio;
use App\Models\Refaccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

/** El recorrido de una orden desde que se diagnostica hasta que se entrega. */
class OrdenFlujoTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    private User $admin;
    private User $recepcion;
    private User $mecanico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
        Storage::fake('local');
        $this->admin = $this->usuario(Rol::Administrador);
        $this->recepcion = $this->usuario(Rol::Recepcionista);
        $this->mecanico = $this->usuario(Rol::Mecanico);
    }

    public function test_recorrido_completo_de_una_orden(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);
        $aceite = $this->servicio();
        $filtro = Refaccion::create(['descripcion' => 'Filtro de aceite', 'unidad' => 'pieza', 'costo' => 75, 'precio_venta' => 130]);
        $aceite->refaccionesSugeridas()->attach($filtro->id, ['cantidad' => 1]);

        // Recepción asigna mecánico
        $this->actingAs($this->recepcion)->patch(route('ordenes.update', $orden), ['mecanico_responsable_id' => $this->mecanico->id])->assertSessionHas('ok');

        // La recepcionista no puede diagnosticar; el mecánico sí
        $this->post(route('ordenes.estado', $orden), ['estado' => 'diagnostico'])->assertSessionHasErrors('estado');
        $this->actingAs($this->mecanico)->post(route('ordenes.estado', $orden), ['estado' => 'diagnostico', 'comentario' => 'Revisando'])->assertSessionHas('ok');

        // El mecánico registra diagnóstico y el trabajo con sus piezas sugeridas
        $this->patch(route('ordenes.update', $orden), ['diagnostico' => 'Aceite degradado', 'descuento' => 500])->assertSessionHas('ok');
        $this->post(route('trabajos.store', $orden), ['servicio_id' => $aceite->id, 'agregar_sugeridas' => '1'])->assertSessionHas('ok');
        $this->post(route('trabajos.store', $orden), ['descripcion' => 'Revisión de frenos', 'tipo_cobro' => 'por_hora', 'cantidad' => 1])->assertSessionHas('ok');

        $orden->refresh();
        $this->assertSame('Aceite degradado', $orden->diagnostico);
        $this->assertEquals(0, $orden->descuento, 'El mecánico no puede poner descuentos');
        $this->assertSame(2, $orden->trabajos()->count());
        $this->assertSame(1, $orden->refacciones()->count(), 'Agrega el filtro sugerido');
        $this->assertTrue($orden->trabajos()->first()->mecanicos->contains($this->mecanico), 'El mecánico queda asignado al trabajo');
        $this->assertEquals(980.00, $orden->total); // 850 + 130; la revisión de frenos no tiene precio de catálogo

        // Autorización y reparación (recepción)
        $this->actingAs($this->recepcion)->post(route('ordenes.estado', $orden), ['estado' => 'por_autorizar']);
        $this->post(route('ordenes.estado', $orden), ['estado' => 'en_reparacion', 'comentario' => 'Autorizó por teléfono']);

        // El mecánico registra horas y termina
        $frenos = $orden->trabajos()->where('descripcion', 'Revisión de frenos')->firstOrFail();
        $this->actingAs($this->mecanico)->patch(route('trabajos.update', $frenos), ['estado' => 'terminado', 'horas' => 1.5])->assertSessionHas('ok');
        $this->assertEquals(1.5, $frenos->fresh()->cantidad, 'En cobro por hora, las horas son la cantidad');
        $this->post(route('ordenes.estado', $orden), ['estado' => 'terminado'])->assertSessionHas('ok');

        // No se entrega sin la inspección de entrega (preferencia obligatoria)
        $this->actingAs($this->recepcion)->post(route('ordenes.estado', $orden), ['estado' => 'entregado'])
            ->assertSessionHasErrors(['estado' => 'La inspección de entrega no está completada.']);

        // Inspección de entrega
        $this->post(route('ordenes.entrega', $orden))->assertRedirect();
        $entrega = $orden->fresh()->entrega;
        $this->assertSame(TipoInspeccion::Entrega, $entrega->tipo);
        $this->llenarInspeccion($entrega, $orden->recepcion->km + 5);
        $this->post(route('inspecciones.completar', $entrega))->assertRedirect(route('ordenes.show', $orden));

        // Entrega
        $this->post(route('ordenes.estado', $orden), ['estado' => 'entregado'])->assertSessionHas('ok');

        $orden->refresh();
        $this->assertSame(EstadoOrden::Entregado, $orden->estado);
        $this->assertSame($this->recepcion->id, $orden->entregado_por_id);
        // recibido, diagnóstico, por autorizar, en reparación, terminado, entregado
        $this->assertSame(6, $orden->bitacoraEstados()->count());

        // Se programó el próximo cambio de aceite
        $proximo = $orden->vehiculo->recordatorios()->pendientes()->firstOrFail();
        $this->assertSame($entrega->km + 10000, $proximo->km_programado);
        $this->assertTrue($proximo->fecha_programada->isSameDay(now()->addMonths(6)));

        // Orden cerrada: ya no se le agregan trabajos
        $this->actingAs($this->mecanico)->post(route('trabajos.store', $orden), ['descripcion' => 'Otro'])->assertSessionHasErrors('orden');
        $this->actingAs($this->admin)->get(route('ordenes.show', $orden))->assertOk()->assertSee('Entregado')->assertDontSee('Agregar trabajo');
    }

    public function test_no_se_puede_diagnosticar_sin_recepcion_completa(): void
    {
        $orden = OrdenServicio::abrir($this->vehiculo(), $this->recepcion, 'Revisión');

        $this->actingAs($this->mecanico);
        $orden->update(['mecanico_responsable_id' => $this->mecanico->id]);

        $this->post(route('ordenes.estado', $orden), ['estado' => 'diagnostico'])
            ->assertSessionHasErrors(['estado' => 'La inspección de recepción no está completada.']);
        $this->post(route('trabajos.store', $orden), ['descripcion' => 'Algo'])->assertSessionHasErrors('orden');
    }

    public function test_cancelar_exige_motivo(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);

        $this->actingAs($this->recepcion)->post(route('ordenes.estado', $orden), ['estado' => 'cancelado'])
            ->assertSessionHasErrors(['estado' => 'Indica el motivo de cancelación.']);

        $this->post(route('ordenes.estado', $orden), ['estado' => 'cancelado', 'comentario' => 'No autorizó'])->assertSessionHas('ok');
        $this->assertSame('No autorizó', $orden->fresh()->motivo_cancelacion);
    }

    public function test_rechaza_saltarse_pasos_del_ciclo(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);

        $this->actingAs($this->admin)->post(route('ordenes.estado', $orden), ['estado' => 'entregado'])
            ->assertSessionHasErrors(['estado' => 'No se puede pasar de «Recibido» a «Entregado».']);
    }

    public function test_un_vehiculo_no_puede_tener_dos_ordenes_abiertas(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);

        $this->actingAs($this->recepcion)
            ->post('/ordenes', ['vehiculo_id' => $orden->vehiculo_id, 'motivo_ingreso' => 'Otra vez', 'tipo' => 'servicio'])
            ->assertSessionHasErrors('vehiculo_id');

        $this->get('/ordenes/nueva?vehiculo_id=' . $orden->vehiculo_id)->assertSee('ya está en el taller');
    }

    public function test_los_folios_son_consecutivos(): void
    {
        $this->actingAs($this->recepcion);
        $this->post('/ordenes', ['vehiculo_id' => $this->vehiculo()->id, 'motivo_ingreso' => 'A', 'tipo' => 'servicio']);
        $this->post('/ordenes', ['vehiculo_id' => $this->vehiculo(['placas' => 'B'])->id, 'motivo_ingreso' => 'B', 'tipo' => 'servicio']);

        $this->assertSame(['MTZ-000001', 'MTZ-000002'], OrdenServicio::orderBy('id')->pluck('folio')->all());
    }

    public function test_la_garantia_exige_la_orden_original(): void
    {
        $this->actingAs($this->recepcion)
            ->post('/ordenes', ['vehiculo_id' => $this->vehiculo()->id, 'motivo_ingreso' => 'Falla otra vez', 'tipo' => 'garantia'])
            ->assertSessionHasErrors('orden_garantia_de_id');
    }

    public function test_quitar_un_trabajo_recalcula_el_total_y_deja_sus_piezas(): void
    {
        $orden = $this->ordenEnReparacion($this->recepcion, $this->mecanico);
        $servicio = $this->servicio();
        $filtro = Refaccion::create(['descripcion' => 'Filtro', 'unidad' => 'pieza', 'precio_venta' => 130]);
        $servicio->refaccionesSugeridas()->attach($filtro->id, ['cantidad' => 1]);

        $this->actingAs($this->mecanico)->post(route('trabajos.store', $orden), ['servicio_id' => $servicio->id, 'agregar_sugeridas' => '1']);
        $this->assertEquals(980.00, $orden->fresh()->total);

        $this->delete(route('trabajos.destroy', $orden->trabajos()->first()))->assertSessionHas('ok');

        $this->assertEquals(130.00, $orden->fresh()->total);
        $this->assertNull($orden->refacciones()->first()->orden_servicio_item_id);
    }
}
