<?php

namespace Tests\Feature;

use App\Enums\EstadoOrden;
use App\Enums\Rol;
use App\Models\OrdenServicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

/** La entrega no repite el checklist: se revisa el reporte de recepción y se anotan observaciones finales. */
class EntregaTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    private User $recepcion;

    private OrdenServicio $orden;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
        $this->recepcion = $this->usuario(Rol::Recepcionista);
        $mecanico = $this->usuario(Rol::Mecanico);
        $this->orden = $this->ordenEnReparacion($this->recepcion, $mecanico);
        $this->orden->recepcion->update(['objetos_personales' => 'Lentes en la guantera']);
        $this->orden->cambiarEstado(EstadoOrden::Terminado, $mecanico);
    }

    public function test_la_pantalla_de_entrega_muestra_el_reporte_de_recepcion_sin_volver_a_pedir_el_checklist(): void
    {
        $entrega = $this->orden->iniciarEntrega($this->recepcion);
        $punto = $this->orden->recepcion->respuestas()->first();

        $this->actingAs($this->recepcion)->get(route('inspecciones.edit', $entrega))
            ->assertOk()
            ->assertSeeInOrder(['Reporte de recepción', 'Acta de recepción en PDF', 'Lentes en la guantera', 'Checklist', $punto->item_nombre,
                'Observaciones finales', 'Completar entrega'])
            ->assertSee(route('inspecciones.pdf', $this->orden->recepcion))
            // Nada de volver a capturar
            ->assertDontSee('name="respuestas[', false)
            ->assertDontSee('Marcar todos')
            ->assertDontSee('name="km"', false)
            ->assertDontSee('name="firma"', false);
    }

    public function test_la_entrega_se_completa_solo_con_observaciones_y_la_orden_se_puede_entregar(): void
    {
        $entrega = $this->orden->iniciarEntrega($this->recepcion);

        $this->actingAs($this->recepcion)
            ->put(route('inspecciones.update', $entrega), [
                'observaciones' => 'Se explicó el cambio de balatas; regresar en 10,000 km.',
                'km' => 999999, // campos de la recepción: se ignoran en la entrega
                'completar' => '1',
            ])
            ->assertRedirect(route('ordenes.show', $this->orden))
            ->assertSessionHas('ok', 'Entrega completada. Ya puedes marcar la orden como entregada.');

        $entrega->refresh();
        $this->assertTrue($entrega->estaCompletada());
        $this->assertSame('Se explicó el cambio de balatas; regresar en 10,000 km.', $entrega->observaciones);
        $this->assertNull($entrega->km);
        $this->assertSame(0, $entrega->respuestas()->count());
        $this->assertNull($entrega->firmado_at, 'La entrega no lleva firma');

        $this->post(route('ordenes.estado', $this->orden), ['estado' => 'entregado'])->assertSessionHas('ok');
        $this->assertSame(EstadoOrden::Entregado, $this->orden->fresh()->estado);
        $this->get(route('ordenes.show', $this->orden))->assertSee('Observaciones finales:')->assertSee('regresar en 10,000 km');
    }

    public function test_las_observaciones_son_opcionales_y_se_pueden_guardar_sin_completar(): void
    {
        $entrega = $this->orden->iniciarEntrega($this->recepcion);

        $this->actingAs($this->recepcion)->from(route('inspecciones.edit', $entrega))
            ->put(route('inspecciones.update', $entrega), ['observaciones' => 'Borrador', 'completar' => '0'])
            ->assertRedirect(route('inspecciones.edit', $entrega))
            ->assertSessionHas('ok', 'Observaciones guardadas.');
        $this->assertFalse($entrega->fresh()->estaCompletada());

        $this->put(route('inspecciones.update', $entrega), ['observaciones' => '', 'completar' => '1'])
            ->assertRedirect(route('ordenes.show', $this->orden));
        $this->assertTrue($entrega->fresh()->estaCompletada());
    }

    public function test_la_entrega_no_tiene_acta_en_pdf_propia(): void
    {
        $entrega = $this->orden->iniciarEntrega($this->recepcion);
        $entrega->completar();

        $this->actingAs($this->recepcion)->get(route('inspecciones.pdf', $entrega))->assertNotFound();
    }

    public function test_un_mecanico_no_hace_la_entrega(): void
    {
        $entrega = $this->orden->iniciarEntrega($this->recepcion);
        $mecanico = $this->orden->mecanicoResponsable;

        $this->actingAs($mecanico)->get(route('inspecciones.edit', $entrega))->assertForbidden();
        $this->actingAs($mecanico)->put(route('inspecciones.update', $entrega), ['observaciones' => 'x', 'completar' => '1'])->assertForbidden();
        $this->assertFalse($entrega->fresh()->estaCompletada());
    }
}
