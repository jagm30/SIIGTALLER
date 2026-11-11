<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\OrdenServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

/** Acta de recepción exportada a PDF: checklist y fotos en miniatura en dos hojas carta. */
class InspeccionPdfTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
        // Los adjuntos de prueba apuntan a este archivo: una foto real de 1600×1200.
        Storage::fake('local');
        Storage::disk('local')->put('pruebas/x.jpg', UploadedFile::fake()->image('x.jpg', 1600, 1200)->getContent());
    }

    public function test_exporta_la_recepcion_completada_en_dos_hojas_carta(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = $this->ordenConRecepcion($recepcion);

        $respuesta = $this->actingAs($recepcion)->get(route('inspecciones.pdf', $orden->recepcion));

        $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('recepcion-' . $orden->folio . '.pdf', $respuesta->headers->get('Content-Disposition'));

        $pdf = $respuesta->getContent();
        $this->assertSame(2, preg_match_all('#/Type\s*/Page\b(?!s)#', $pdf), 'El acta debe ocupar exactamente dos hojas');
        $this->assertMatchesRegularExpression('#/MediaBox\s*\[\s*0\.?0*\s+0\.?0*\s+612\.?0*\s+792\.?0*\s*\]#', $pdf, 'Tamaño carta');
    }

    public function test_el_acta_trae_el_checklist_agrupado_las_miniaturas_y_la_firma(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = $this->ordenConRecepcion($recepcion);
        Storage::disk('local')->put('pruebas/firma.png', UploadedFile::fake()->image('firma.png', 600, 200)->getContent());
        $orden->recepcion->firma->update(['ruta' => 'pruebas/firma.png', 'mime' => 'image/png']);
        $inspeccion = $orden->recepcion->load(['fotos.anguloFoto', 'danios', 'firma', 'realizadaPor']);
        $primerPunto = $inspeccion->checklistPorGrupo()->first()->first();

        $html = view('inspecciones.pdf', [
            'inspeccion' => $inspeccion,
            'orden' => $orden,
            'checklist' => $inspeccion->checklistPorGrupo(),
            'terminos' => 'El cliente acepta los términos.',
        ])->render();

        $this->assertStringContainsString('Acta de recepción del vehículo', $html);
        $this->assertStringContainsString('Accesorios', $html);
        $this->assertStringContainsString(e($primerPunto->item_nombre), $html);
        $this->assertSame($inspeccion->fotos->count(), substr_count($html, 'src="data:image/jpeg'), 'Una miniatura por foto');
        $this->assertSame(1, substr_count($html, 'src="data:image/png'), 'La firma va completa, con su transparencia');
        $this->assertStringContainsString('Firma del cliente', $html);
        $this->assertStringContainsString('El cliente acepta los términos.', $html);
    }

    public function test_la_miniatura_es_un_jpeg_chico_recortado_a_4_3(): void
    {
        $orden = $this->ordenConRecepcion($this->usuario(Rol::Recepcionista));
        Storage::disk('local')->put('pruebas/x.jpg', UploadedFile::fake()->image('x.jpg', 1200, 1600)->getContent()); // vertical

        $uri = $orden->recepcion->fotos->first()->dataUri(360);

        $this->assertStringStartsWith('data:image/jpeg;base64,', $uri);
        [$ancho, $alto] = getimagesizefromstring(base64_decode(substr($uri, strlen('data:image/jpeg;base64,'))));
        $this->assertSame([360, 270], [$ancho, $alto]);
    }

    public function test_no_exporta_una_recepcion_sin_completar(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = OrdenServicio::abrir($this->vehiculo(), $recepcion, 'Servicio general');

        $this->actingAs($recepcion)->from(route('ordenes.show', $orden))
            ->get(route('inspecciones.pdf', $orden->recepcion))
            ->assertRedirect(route('ordenes.show', $orden))
            ->assertSessionHas('error', 'El PDF se genera cuando la inspección está completada.');
    }

    public function test_un_mecanico_no_exporta_el_acta_de_una_orden_que_no_es_suya(): void
    {
        $orden = $this->ordenConRecepcion($this->usuario(Rol::Recepcionista));

        $this->actingAs($this->usuario(Rol::Mecanico))->get(route('inspecciones.pdf', $orden->recepcion))->assertForbidden();
    }

    public function test_la_orden_tiene_el_boton_para_exportar_el_acta(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = $this->ordenConRecepcion($recepcion);

        $this->actingAs($recepcion)->get(route('ordenes.show', $orden))
            ->assertSee('Exportar acta en PDF')
            ->assertSee(route('inspecciones.pdf', $orden->recepcion));
    }
}
