<?php

namespace Tests\Feature;

use App\Enums\EstadoOrden;
use App\Enums\Rol;
use App\Enums\TipoAdjunto;
use App\Models\OrdenServicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('local');
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
            ->assertSeeInOrder(['Reporte de recepción', 'Acta PDF', 'Lentes en la guantera', 'Checklist', $punto->item_nombre,
                'Observaciones finales', 'Firma de conformidad', 'Completar entrega'])
            ->assertSee(route('inspecciones.pdf', $this->orden->recepcion))
            // Nada de volver a capturar el checklist ni el kilometraje
            ->assertDontSee('name="respuestas[', false)
            ->assertDontSee('Marcar todos')
            ->assertDontSee('name="km"', false);
    }

    public function test_la_entrega_requiere_firma_y_completa_la_orden_automaticamente(): void
    {
        $entrega = $this->orden->iniciarEntrega($this->recepcion);

        // Sin firma ni firmante: no se puede completar
        $this->actingAs($this->recepcion)
            ->put(route('inspecciones.update', $entrega), ['completar' => '1'])
            ->assertSessionHasErrors();
        $this->assertFalse($entrega->fresh()->estaCompletada());

        // Con firma y firmante: se completa y la orden queda entregada automáticamente
        $this->put(route('inspecciones.update', $entrega), [
            'observaciones'   => 'Se explicó el cambio de balatas; regresar en 10,000 km.',
            'firmante_nombre' => 'Juan Pérez',
            'firma'           => $this->firmaPng(),
            'km'              => 999999, // campo de recepción: se ignora en la entrega
            'completar'       => '1',
        ])
        ->assertRedirect(route('ordenes.show', $this->orden))
        ->assertSessionHas('ok', 'Entrega completada. La orden ha sido marcada como entregada.');

        $entrega->refresh();
        $this->assertTrue($entrega->estaCompletada());
        $this->assertSame('Se explicó el cambio de balatas; regresar en 10,000 km.', $entrega->observaciones);
        $this->assertSame('Juan Pérez', $entrega->firmante_nombre);
        $this->assertNull($entrega->km, 'El km de la entrega no se guarda');
        $this->assertSame(0, $entrega->respuestas()->count(), 'La entrega no repite el checklist');
        $this->assertNull($entrega->firmado_at, 'firmado_at es exclusivo de la recepción');
        $this->assertSame(EstadoOrden::Entregado, $this->orden->fresh()->estado);
    }

    public function test_las_observaciones_son_opcionales_y_se_puede_guardar_avance_sin_completar(): void
    {
        $entrega = $this->orden->iniciarEntrega($this->recepcion);

        // Guardar avance sin completar: solo guarda observaciones, no cambia estado
        $this->actingAs($this->recepcion)->from(route('inspecciones.edit', $entrega))
            ->put(route('inspecciones.update', $entrega), ['observaciones' => 'Borrador', 'completar' => '0'])
            ->assertRedirect(route('inspecciones.edit', $entrega))
            ->assertSessionHas('ok', 'Entrega guardada.');
        $this->assertFalse($entrega->fresh()->estaCompletada());

        // Completar con firma sí funciona aunque las observaciones estén vacías
        $this->put(route('inspecciones.update', $entrega), [
            'observaciones'   => '',
            'firmante_nombre' => 'Juan Pérez',
            'firma'           => $this->firmaPng(),
            'completar'       => '1',
        ])
        ->assertRedirect(route('ordenes.show', $this->orden));

        $this->assertTrue($entrega->fresh()->estaCompletada());
    }

    public function test_la_entrega_completada_genera_acta_en_pdf(): void
    {
        $entrega = $this->orden->iniciarEntrega($this->recepcion);
        $entrega->update(['firmante_nombre' => 'Juan Pérez']);
        $this->adjuntoFalso($entrega, TipoAdjunto::Firma);
        $entrega->completar();

        $this->actingAs($this->recepcion)
            ->get(route('inspecciones.pdf', $entrega))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
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
