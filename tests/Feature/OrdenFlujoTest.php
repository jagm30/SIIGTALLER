<?php

namespace Tests\Feature;

use App\Enums\EstadoOrden;
use App\Enums\EstadoTrabajo;
use App\Enums\Rol;
use App\Enums\TipoInspeccion;
use App\Models\ChecklistItem;
use App\Models\OrdenServicio;
use App\Models\PreferenciaOperacion;
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

    public function test_diagnostico_no_se_puede_editar_en_estado_recibido(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);
        $orden->update(['mecanico_responsable_id' => $this->mecanico->id]);
        $this->assertSame(\App\Enums\EstadoOrden::Recibido, $orden->estado);

        // Recepción intenta guardar el diagnóstico antes de que el mecánico empiece
        $this->actingAs($this->recepcion)
            ->patch(route('ordenes.update', $orden), ['diagnostico' => 'Aceite degradado'])
            ->assertSessionHas('ok');

        // El diagnóstico debe seguir vacío: el campo fue ignorado por el servidor
        $this->assertNull($orden->fresh()->diagnostico);

        // Una vez en fase de diagnóstico, sí se puede guardar
        $orden->cambiarEstado(\App\Enums\EstadoOrden::Diagnostico, $this->mecanico);
        $this->actingAs($this->mecanico)
            ->patch(route('ordenes.update', $orden), ['diagnostico' => 'Aceite degradado'])
            ->assertSessionHas('ok');

        $this->assertSame('Aceite degradado', $orden->fresh()->diagnostico);
    }

    public function test_el_campo_diagnostico_aparece_bloqueado_hasta_la_fase_de_diagnostico(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);
        $orden->update(['mecanico_responsable_id' => $this->mecanico->id]);

        $this->actingAs($this->mecanico)->get(route('ordenes.show', $orden))
            ->assertSee('<textarea id="diagnostico" name="diagnostico" disabled>', false)
            ->assertSee('Disponible a partir de la fase de diagnóstico.');

        $orden->cambiarEstado(EstadoOrden::Diagnostico, $this->mecanico);

        $this->get(route('ordenes.show', $orden))
            ->assertSee('<textarea id="diagnostico" name="diagnostico" >', false)
            ->assertDontSee('Disponible a partir de la fase de diagnóstico.');
    }

    public function test_en_diagnostico_el_formulario_va_arriba_y_se_ocultan_recepcion_y_entrega(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);
        $this->actingAs($this->admin);

        // Recibido: recepción y entrega arriba, como siempre
        $this->get(route('ordenes.show', $orden))
            ->assertSeeInOrder(['<h2>Recepción</h2>', '<h2>Entrega</h2>', 'Diagnóstico y datos de la orden'], false);

        // Diagnóstico: primero el formulario (con lo que reporta el cliente) y luego el siguiente paso
        $orden->cambiarEstado(EstadoOrden::Diagnostico, $this->mecanico);
        $html = $this->get(route('ordenes.show', $orden))
            ->assertSeeInOrder(['Diagnóstico y datos de la orden', 'Lo que reporta el cliente:', $orden->motivo_ingreso, 'Siguiente paso', 'Trabajos realizados'])
            ->assertDontSee('<h2>Recepción</h2>', false)
            ->assertDontSee('<h2>Entrega</h2>', false)
            ->getContent();
        $this->assertSame(1, substr_count($html, 'Diagnóstico y datos de la orden'), 'El formulario aparece una sola vez');

        // Al salir de diagnóstico vuelve el acomodo normal
        $orden->cambiarEstado(EstadoOrden::PorAutorizar, $this->admin);
        $this->get(route('ordenes.show', $orden))
            ->assertSeeInOrder(['<h2>Recepción</h2>', 'Diagnóstico y datos de la orden'], false)
            ->assertDontSee('Lo que reporta el cliente:');
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

    public function test_quitar_un_trabajo_quita_sus_refacciones_y_deja_las_sueltas(): void
    {
        $orden = $this->ordenEnReparacion($this->recepcion, $this->mecanico);
        $afinacion = $this->servicio(['nombre' => 'Afinación mayor']);
        $bujia = Refaccion::create(['descripcion' => 'Bujía', 'unidad' => 'pieza', 'precio_venta' => 100]);
        $filtro = Refaccion::create(['descripcion' => 'Filtro', 'unidad' => 'pieza', 'precio_venta' => 130]);
        $afinacion->refaccionesSugeridas()->attach([$bujia->id => ['cantidad' => 4], $filtro->id => ['cantidad' => 1]]);

        // Como administrador, para poder capturar el precio de la pieza suelta
        $this->actingAs($this->admin)->post(route('trabajos.store', $orden), ['servicio_id' => $afinacion->id, 'agregar_sugeridas' => '1']);
        // Una pieza suelta, sin trabajo: no debe borrarse
        $this->post(route('refacciones-usadas.store', $orden), ['refaccion_id' => 'otra', 'descripcion' => 'Limpiador', 'cantidad' => 1, 'precio_unitario' => 50]);
        $this->assertSame(3, $orden->refacciones()->count());

        $this->delete(route('trabajos.destroy', $orden->trabajos()->first()))
            ->assertSessionHas('ok', 'Trabajo eliminado junto con sus 2 refacciones.');

        $this->assertSame(['Limpiador'], $orden->refacciones()->pluck('descripcion')->all());
        $this->assertEquals(50.00, $orden->fresh()->total);
    }

    public function test_avisa_si_el_servicio_ya_esta_en_la_orden_pero_lo_permite(): void
    {
        $orden = $this->ordenEnReparacion($this->recepcion, $this->mecanico);
        $pintura = $this->servicio(['nombre' => 'Pintura de pieza']);

        $this->actingAs($this->mecanico)->post(route('trabajos.store', $orden), ['servicio_id' => $pintura->id, 'notas' => 'Puerta'])
            ->assertSessionMissing('aviso');
        $this->post(route('trabajos.store', $orden), ['servicio_id' => $pintura->id, 'notas' => 'Salpicadera'])
            ->assertSessionHas('aviso');

        $this->assertSame(2, $orden->trabajos()->count(), 'Se permite repetirlo');

        // La pantalla marca el servicio como "ya está en la orden"
        $this->get(route('ordenes.show', $orden))->assertSee('(ya está en la orden)');
    }

    public function test_la_misma_refaccion_se_suma_en_un_solo_renglon(): void
    {
        $orden = $this->ordenEnReparacion($this->recepcion, $this->mecanico);
        $afinacion = $this->servicio(['nombre' => 'Afinación mayor']);
        $bujia = Refaccion::create(['descripcion' => 'Bujía', 'unidad' => 'pieza', 'precio_venta' => 100]);
        $afinacion->refaccionesSugeridas()->attach($bujia->id, ['cantidad' => 4]);

        $this->actingAs($this->mecanico)->post(route('trabajos.store', $orden), ['servicio_id' => $afinacion->id, 'agregar_sugeridas' => '1']);
        $trabajo = $orden->trabajos()->firstOrFail();

        // Sin elegir trabajo: la bujía solo está en un renglón, se suma ahí
        $this->post(route('refacciones-usadas.store', $orden), ['refaccion_id' => $bujia->id, 'cantidad' => 1])
            ->assertSessionHas('ok', 'Se sumó a «Bujía», que ya estaba en la orden. Ahora son 5.');
        // Eligiendo el mismo trabajo: también se suma
        $this->post(route('refacciones-usadas.store', $orden), ['refaccion_id' => $bujia->id, 'cantidad' => 1, 'orden_servicio_item_id' => $trabajo->id]);

        $this->assertSame(1, $orden->refacciones()->count());
        $renglon = $orden->refacciones()->firstOrFail();
        $this->assertEquals(6, $renglon->cantidad);
        $this->assertEquals(600.00, $renglon->importe);
        $this->assertEquals(1450.00, $orden->fresh()->total); // 850 de la afinación + 600 de bujías
    }

    public function test_no_suma_si_la_pieza_es_distinta_en_origen_trabajo_o_precio(): void
    {
        $orden = $this->ordenEnReparacion($this->recepcion, $this->mecanico);
        $bujia = Refaccion::create(['descripcion' => 'Bujía', 'unidad' => 'pieza', 'precio_venta' => 100]);
        $this->actingAs($this->admin);
        $this->post(route('trabajos.store', $orden), ['servicio_id' => $this->servicio()->id]);
        $trabajo = $orden->trabajos()->firstOrFail();

        $this->post(route('refacciones-usadas.store', $orden), ['refaccion_id' => $bujia->id, 'cantidad' => 4]);
        // La trajo el cliente: renglón aparte (va sin costo)
        $this->post(route('refacciones-usadas.store', $orden), ['refaccion_id' => $bujia->id, 'cantidad' => 1, 'proporcionada_por_cliente' => '1']);
        // Para otro trabajo: renglón aparte
        $this->post(route('refacciones-usadas.store', $orden), ['refaccion_id' => $bujia->id, 'cantidad' => 1, 'orden_servicio_item_id' => $trabajo->id]);
        // Ahora está en dos renglones del taller y no eligieron trabajo: no adivina
        $this->post(route('refacciones-usadas.store', $orden), ['refaccion_id' => $bujia->id, 'cantidad' => 1]);
        // Precio distinto: renglón aparte
        $this->post(route('refacciones-usadas.store', $orden), ['refaccion_id' => $bujia->id, 'cantidad' => 1, 'orden_servicio_item_id' => $trabajo->id, 'precio_unitario' => 120]);

        $this->assertSame(5, $orden->refacciones()->count());
    }

    public function test_otra_refaccion_y_otro_trabajo_fuera_de_catalogo(): void
    {
        $orden = $this->ordenEnReparacion($this->recepcion, $this->mecanico);
        $this->actingAs($this->admin);

        // Elegir "Otra" sin escribir el nombre no se acepta
        $this->post(route('refacciones-usadas.store', $orden), ['refaccion_id' => 'otra', 'cantidad' => 1])->assertSessionHasErrors('descripcion');
        $this->post(route('trabajos.store', $orden), ['servicio_id' => 'otro'])->assertSessionHasErrors('descripcion');

        $this->post(route('refacciones-usadas.store', $orden), ['refaccion_id' => 'otra', 'descripcion' => 'Tornillo especial', 'numero_parte' => 'TS-10', 'cantidad' => 2, 'precio_unitario' => 35])
            ->assertSessionHas('ok', 'Refacción agregada.');
        $this->post(route('trabajos.store', $orden), ['servicio_id' => 'otro', 'descripcion' => 'Soldadura de escape', 'tipo_cobro' => 'fijo', 'precio_unitario' => 400]);

        $pieza = $orden->refacciones()->firstOrFail();
        $this->assertNull($pieza->refaccion_id);
        $this->assertSame('TS-10', $pieza->numero_parte);
        $this->assertEquals(470.00, $orden->fresh()->total); // 70 + 400
    }

    public function test_la_tabla_de_refacciones_muestra_para_que_trabajo_se_uso(): void
    {
        $orden = $this->ordenEnReparacion($this->recepcion, $this->mecanico);
        $afinacion = $this->servicio(['nombre' => 'Afinación mayor']);
        $bujia = Refaccion::create(['descripcion' => 'Bujía', 'unidad' => 'pieza']);
        $afinacion->refaccionesSugeridas()->attach($bujia->id, ['cantidad' => 4]);

        $this->actingAs($this->mecanico)->post(route('trabajos.store', $orden), ['servicio_id' => $afinacion->id, 'agregar_sugeridas' => '1']);

        $this->get(route('ordenes.show', $orden))
            ->assertSeeInOrder(['Para el trabajo', 'Bujía', 'Afinación mayor']);
    }

    public function test_un_trabajo_puede_quedar_esperando_refacciones(): void
    {
        $orden = $this->ordenEnReparacion($this->recepcion, $this->mecanico);
        $trabajo = $orden->trabajos()->create(['servicio_id' => $this->servicio()->id]);

        $this->actingAs($this->mecanico)->get(route('ordenes.show', $orden))->assertSee('Esperando refacciones');
        $this->patch(route('trabajos.update', $trabajo), ['estado' => 'espera_refacciones'])->assertSessionHas('ok');

        $this->assertSame(EstadoTrabajo::EsperaRefacciones, $trabajo->fresh()->estado);
    }

    public function test_si_se_desactivan_las_horas_el_mecanico_no_las_registra(): void
    {
        PreferenciaOperacion::where('clave', 'mecanicos_registran_horas')->update(['valor' => '0']);
        PreferenciaOperacion::olvidar();

        $orden = $this->ordenEnReparacion($this->recepcion, $this->mecanico);
        $this->actingAs($this->mecanico)->post(route('trabajos.store', $orden), ['descripcion' => 'Diagnóstico eléctrico', 'tipo_cobro' => 'por_hora', 'cantidad' => 2]);
        $trabajo = $orden->trabajos()->firstOrFail();

        $this->get(route('ordenes.show', $orden))->assertDontSee('Mis horas');
        $this->patch(route('trabajos.update', $trabajo), ['estado' => 'terminado', 'horas' => 5])->assertSessionHas('ok');

        $trabajo->refresh();
        $this->assertSame(EstadoTrabajo::Terminado, $trabajo->estado, 'El avance sí se guarda');
        $this->assertEquals(2, $trabajo->cantidad, 'Se cobran las horas capturadas al agregar el trabajo');
        $this->assertNull($trabajo->mecanicos()->first()->pivot->horas);
    }
}
