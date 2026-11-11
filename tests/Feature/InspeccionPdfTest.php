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

    public function test_la_miniatura_muestra_la_foto_completa_en_un_recuadro_4_3(): void
    {
        $orden = $this->ordenConRecepcion($this->usuario(Rol::Recepcionista));
        // Foto vertical toda roja, de punta a punta: si se recortara, no habría márgenes blancos.
        $vertical = imagecreatetruecolor(1200, 1600);
        imagefill($vertical, 0, 0, imagecolorallocate($vertical, 255, 0, 0));
        ob_start();
        imagejpeg($vertical, null, 95);
        Storage::disk('local')->put('pruebas/x.jpg', ob_get_clean());

        $uri = $orden->recepcion->fotos->first()->dataUri(360);

        $this->assertStringStartsWith('data:image/jpeg;base64,', $uri);
        $miniatura = imagecreatefromstring(base64_decode(substr($uri, strlen('data:image/jpeg;base64,'))));
        $this->assertSame([360, 270], [imagesx($miniatura), imagesy($miniatura)]);

        // 1200×1600 cabe en 270 de alto → 203 de ancho, centrada: márgenes blancos a los lados.
        $color = fn ($x, $y) => imagecolorsforindex($miniatura, imagecolorat($miniatura, $x, $y));
        $this->assertGreaterThan(240, $color(10, 135)['green'], 'Margen izquierdo blanco');
        $this->assertGreaterThan(240, $color(350, 135)['green'], 'Margen derecho blanco');
        $this->assertLessThan(30, $color(180, 135)['green'], 'La foto al centro');
        $this->assertLessThan(30, $color(180, 2)['green'], 'Arriba no se recortó: la foto llega al borde');
    }

    public function test_una_foto_png_con_metadatos_imperfectos_no_rompe_el_pdf(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = $this->ordenConRecepcion($recepcion);
        $orden->recepcion->fotos()->update(['ruta' => 'pruebas/aviso.png', 'mime' => 'image/png']);
        Storage::disk('local')->put('pruebas/aviso.png', $this->pngConAvisoDeLibpng());

        $this->assertStringStartsWith('data:image/jpeg;base64,', $orden->recepcion->fotos()->first()->dataUri(360));
        $this->actingAs($recepcion)->get(route('inspecciones.pdf', $orden->recepcion))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_una_foto_danada_sale_como_no_disponible_sin_romper_el_pdf(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = $this->ordenConRecepcion($recepcion);
        Storage::disk('local')->put('pruebas/x.jpg', 'esto no es una imagen');

        $this->assertNull($orden->recepcion->fotos()->first()->dataUri(360));
        $this->actingAs($recepcion)->get(route('inspecciones.pdf', $orden->recepcion))->assertOk();
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

    /**
     * PNG válido con un bloque de metadatos cuyo CRC no cuadra: libpng lo descarta y avisa con
     * un warning, igual que con "iCCP: known incorrect sRGB profile" de algunas cámaras.
     */
    private function pngConAvisoDeLibpng(): string
    {
        ob_start();
        imagepng(imagecreatetruecolor(800, 600));
        $png = ob_get_clean();
        $datos = "Comment\0x";
        $bloque = pack('N', strlen($datos)) . 'tEXt' . $datos . pack('N', 12345);

        return substr($png, 0, 33) . $bloque . substr($png, 33); // 33 = firma PNG + bloque IHDR
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
