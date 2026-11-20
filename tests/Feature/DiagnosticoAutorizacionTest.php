<?php

namespace Tests\Feature;

use App\Enums\EstadoOrden;
use App\Enums\EstadoTrabajo;
use App\Enums\Rol;
use App\Models\OrdenServicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

/**
 * Flujo de diagnóstico y autorización del cliente.
 *
 * Recibido ──(ordenes.diagnostico)──► PorAutorizar ──(ordenes.estado: en_reparacion)──► EnReparacion
 *
 * Durante PorAutorizar se registran los trabajos y refacciones del presupuesto;
 * el cliente los revisa y autoriza (o cancela) antes de que el taller empiece a trabajar.
 */
class DiagnosticoAutorizacionTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    private User $admin;
    private User $recepcion;
    private User $mecanico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
        $this->admin     = $this->usuario(Rol::Administrador);
        $this->recepcion = $this->usuario(Rol::Recepcionista);
        $this->mecanico  = $this->usuario(Rol::Mecanico);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Orden con recepción completa y ya en PorAutorizar, sin trabajos todavía. */
    private function ordenEnPorAutorizar(?string $diagnostico = null): OrdenServicio
    {
        $orden = $this->ordenConRecepcion($this->recepcion);
        $orden->update(['mecanico_responsable_id' => $this->mecanico->id]);
        $orden->cambiarEstado(EstadoOrden::PorAutorizar, $this->mecanico);
        if ($diagnostico) {
            $orden->update(['diagnostico' => $diagnostico]);
        }
        return $orden->refresh();
    }

    // ── Diagnóstico ──────────────────────────────────────────────────────────

    public function test_el_mecanico_registra_diagnostico_y_la_orden_pasa_a_espera_de_autorizacion(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);
        // El mecánico necesita estar asignado para tener acceso a la orden
        $orden->update(['mecanico_responsable_id' => $this->mecanico->id]);

        $this->actingAs($this->mecanico)
            ->post(route('ordenes.diagnostico', $orden), [
                'mecanico_responsable_id' => $this->mecanico->id,
                'diagnostico'             => 'Aceite degradado y filtro obstruido.',
            ])
            ->assertRedirect()
            ->assertSessionHas('ok');

        $orden->refresh();
        $this->assertSame(EstadoOrden::PorAutorizar, $orden->estado);
        $this->assertSame($this->mecanico->id, $orden->mecanico_responsable_id);
        $this->assertSame('Aceite degradado y filtro obstruido.', $orden->diagnostico);
    }

    public function test_el_diagnostico_y_el_mecanico_son_opcionales(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);
        $orden->update(['mecanico_responsable_id' => $this->mecanico->id]);

        $this->actingAs($this->mecanico)
            ->post(route('ordenes.diagnostico', $orden), [])
            ->assertRedirect()
            ->assertSessionHas('ok');

        $this->assertSame(EstadoOrden::PorAutorizar, $orden->fresh()->estado);
    }

    public function test_el_recepcionista_no_puede_registrar_el_diagnostico(): void
    {
        // El admin puede ver la orden; el intento de cambio de estado falla por rol
        $orden = $this->ordenConRecepcion($this->recepcion);

        $this->actingAs($this->recepcion)
            ->post(route('ordenes.diagnostico', $orden), ['diagnostico' => 'Falla en motor'])
            ->assertSessionHasErrors('estado');

        $this->assertSame(EstadoOrden::Recibido, $orden->fresh()->estado);
    }

    public function test_el_diagnostico_exige_recepcion_completada(): void
    {
        // Admin puede ver cualquier orden; el modelo rechaza el cambio de estado
        $orden = OrdenServicio::abrir($this->vehiculo(), $this->recepcion, 'Revisión');

        $this->actingAs($this->admin)
            ->post(route('ordenes.diagnostico', $orden), ['diagnostico' => 'Falla en motor'])
            ->assertSessionHasErrors('estado');

        $this->assertSame(EstadoOrden::Recibido, $orden->fresh()->estado);
    }

    public function test_el_mecanico_responsable_debe_tener_rol_mecanico(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);

        $this->actingAs($this->admin)
            ->post(route('ordenes.diagnostico', $orden), [
                'mecanico_responsable_id' => $this->recepcion->id, // recepcionista, no mecánico
            ])
            ->assertSessionHasErrors('mecanico_responsable_id');

        $this->assertSame(EstadoOrden::Recibido, $orden->fresh()->estado);
    }

    // ── Vista durante diagnóstico ─────────────────────────────────────────────

    public function test_la_vista_muestra_boton_ir_al_diagnostico_tras_completar_recepcion(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);

        $this->actingAs($this->admin)
            ->get(route('ordenes.show', $orden))
            ->assertSee('Ir al diagnóstico');
    }

    public function test_la_vista_oculta_datos_de_la_orden_mientras_esta_en_recibido(): void
    {
        // El formulario de trabajos no debe mostrarse hasta que haya diagnóstico
        $orden = OrdenServicio::abrir($this->vehiculo(), $this->recepcion, 'Revisión');

        $this->actingAs($this->admin)
            ->get(route('ordenes.show', $orden))
            ->assertDontSee('Datos de la orden');
    }

    // ── Estado PorAutorizar ───────────────────────────────────────────────────

    public function test_se_pueden_agregar_trabajos_mientras_se_espera_autorizacion(): void
    {
        $orden   = $this->ordenEnPorAutorizar();
        $servicio = $this->servicio();

        $this->actingAs($this->mecanico)
            ->post(route('trabajos.store', $orden), ['servicio_id' => $servicio->id])
            ->assertSessionHas('ok');

        $this->assertSame(1, $orden->trabajos()->count());
        $this->assertSame(EstadoTrabajo::Pendiente, $orden->trabajos()->first()->estado);
    }

    public function test_se_pueden_agregar_refacciones_mientras_se_espera_autorizacion(): void
    {
        $orden = $this->ordenEnPorAutorizar();

        $this->actingAs($this->mecanico)
            ->post(route('refacciones-usadas.store', $orden), [
                'refaccion_id' => 'otra',
                'descripcion'  => 'Filtro de aceite',
                'cantidad'     => 1,
            ])
            ->assertSessionHas('ok');

        $this->assertSame(1, $orden->refacciones()->count());
    }

    public function test_los_trabajos_no_pueden_cambiar_de_estado_en_por_autorizar(): void
    {
        $orden = $this->ordenEnPorAutorizar();
        $this->actingAs($this->mecanico)->post(route('trabajos.store', $orden), ['servicio_id' => $this->servicio()->id]);
        $trabajo = $orden->trabajos()->firstOrFail();

        $this->patch(route('trabajos.update', $trabajo), ['estado' => 'en_proceso'])
            ->assertSessionHas('error');

        $this->assertSame(EstadoTrabajo::Pendiente, $trabajo->fresh()->estado);
    }

    public function test_la_vista_muestra_avance_como_texto_no_como_selector_en_por_autorizar(): void
    {
        $orden = $this->ordenEnPorAutorizar();
        $this->actingAs($this->mecanico)->post(route('trabajos.store', $orden), ['servicio_id' => $this->servicio()->id]);

        $this->actingAs($this->mecanico)
            ->get(route('ordenes.show', $orden))
            ->assertDontSee('onchange="this.form.submit()"', false)
            ->assertSee('Pendiente');
    }

    public function test_la_vista_muestra_boton_cliente_autorizo_deshabilitado_sin_presupuesto(): void
    {
        $orden = $this->ordenEnPorAutorizar();

        $html = $this->actingAs($this->recepcion)
            ->get(route('ordenes.show', $orden))
            ->assertSee('Cliente autorizó')
            ->getContent();

        $this->assertStringContainsString('disabled', $html);
    }

    public function test_la_vista_habilita_boton_cliente_autorizo_cuando_hay_trabajos(): void
    {
        $orden = $this->ordenEnPorAutorizar();
        $this->actingAs($this->mecanico)->post(route('trabajos.store', $orden), ['servicio_id' => $this->servicio()->id]);

        $html = $this->actingAs($this->recepcion)
            ->get(route('ordenes.show', $orden))
            ->getContent();

        $this->assertStringNotContainsString('disabled title="Registra al menos un trabajo', $html);
    }

    // ── Autorización ─────────────────────────────────────────────────────────

    public function test_no_se_puede_autorizar_sin_trabajos_ni_refacciones(): void
    {
        $orden = $this->ordenEnPorAutorizar();

        $this->actingAs($this->recepcion)
            ->post(route('ordenes.estado', $orden), ['estado' => 'en_reparacion'])
            ->assertSessionHasErrors('estado');

        $this->assertSame(EstadoOrden::PorAutorizar, $orden->fresh()->estado);
    }

    public function test_la_autorizacion_del_cliente_pasa_la_orden_a_en_reparacion(): void
    {
        $orden = $this->ordenEnPorAutorizar();
        $this->actingAs($this->mecanico)->post(route('trabajos.store', $orden), ['servicio_id' => $this->servicio()->id]);

        $this->actingAs($this->recepcion)
            ->post(route('ordenes.estado', $orden), ['estado' => 'en_reparacion', 'comentario' => 'Autorizó por teléfono'])
            ->assertSessionHas('ok');

        $this->assertSame(EstadoOrden::EnReparacion, $orden->fresh()->estado);
    }

    public function test_se_puede_cancelar_en_espera_de_autorizacion_sin_trabajos(): void
    {
        $orden = $this->ordenEnPorAutorizar();

        $this->actingAs($this->recepcion)
            ->post(route('ordenes.estado', $orden), [
                'estado'     => 'cancelado',
                'comentario' => 'El cliente no autorizó el presupuesto.',
            ])
            ->assertSessionHas('ok');

        $orden->refresh();
        $this->assertSame(EstadoOrden::Cancelado, $orden->estado);
        $this->assertSame('El cliente no autorizó el presupuesto.', $orden->motivo_cancelacion);
    }

    public function test_la_bitacora_registra_el_paso_por_por_autorizar(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);
        $orden->update(['mecanico_responsable_id' => $this->mecanico->id]);

        $this->actingAs($this->mecanico)
            ->post(route('ordenes.diagnostico', $orden), ['diagnostico' => 'Revisión general']);

        $this->actingAs($this->mecanico)
            ->post(route('trabajos.store', $orden), ['servicio_id' => $this->servicio()->id]);

        $this->actingAs($this->recepcion)
            ->post(route('ordenes.estado', $orden), ['estado' => 'en_reparacion']);

        // recibido → por_autorizar → en_reparacion
        $this->assertSame(3, $orden->bitacoraEstados()->count());
        $this->assertSame(
            [EstadoOrden::Recibido, EstadoOrden::PorAutorizar, EstadoOrden::EnReparacion],
            $orden->bitacoraEstados()->pluck('estado_nuevo')->all(),
        );
    }

    // ── Flujo completo ────────────────────────────────────────────────────────

    public function test_flujo_completo_diagnostico_autorizacion_reparacion(): void
    {
        // 1. Vehículo recibido
        $orden = $this->ordenConRecepcion($this->recepcion);
        $orden->update(['mecanico_responsable_id' => $this->mecanico->id]);

        // 2. Mecánico registra diagnóstico → queda en espera de autorización
        $this->actingAs($this->mecanico)
            ->post(route('ordenes.diagnostico', $orden), [
                'mecanico_responsable_id' => $this->mecanico->id,
                'diagnostico'             => 'Cambio de aceite y revisión de frenos necesarios.',
            ])
            ->assertSessionHas('ok');
        $this->assertSame(EstadoOrden::PorAutorizar, $orden->fresh()->estado);

        // 3. Mecánico agrega trabajos y refacciones del presupuesto
        $this->post(route('trabajos.store', $orden), ['servicio_id' => $this->servicio()->id])
            ->assertSessionHas('ok');
        $this->assertSame(EstadoTrabajo::Pendiente, $orden->trabajos()->first()->estado);

        // 4. Cliente autoriza → la orden pasa a en reparación y los trabajos se pueden avanzar
        $this->actingAs($this->recepcion)
            ->post(route('ordenes.estado', $orden), ['estado' => 'en_reparacion', 'comentario' => 'Autorizado'])
            ->assertSessionHas('ok');

        $orden->refresh();
        $this->assertSame(EstadoOrden::EnReparacion, $orden->estado);

        // 5. Ahora sí el mecánico puede avanzar el trabajo
        $trabajo = $orden->trabajos()->firstOrFail();
        $this->actingAs($this->mecanico)
            ->patch(route('trabajos.update', $trabajo), ['estado' => 'terminado'])
            ->assertSessionHas('ok');

        $this->assertSame(EstadoTrabajo::Terminado, $trabajo->fresh()->estado);
    }
}
