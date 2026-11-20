<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\PreferenciaOperacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

/** Nombre y logo del taller: se editan en Preferencias y se muestran en la barra, el login, el QR y los PDF. */
class IdentidadTallerTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
        Storage::fake('local');
        $this->admin = $this->usuario(Rol::Administrador);
    }

    public function test_sin_configurar_se_usa_el_nombre_de_la_aplicacion(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(config('app.name'));
    }

    public function test_el_administrador_cambia_el_nombre_y_aparece_en_la_barra_y_el_login(): void
    {
        $this->actingAs($this->admin)->put(route('preferencias.taller'), ['nombre' => 'Taller Hércules'])
            ->assertSessionHas('ok');

        $this->assertSame('Taller Hércules', PreferenciaOperacion::nombreTaller());
        $this->get(route('dashboard'))->assertSee('<title>Inicio · Taller Hércules</title>', false);

        auth()->logout();
        $this->get(route('login'))->assertSee('Taller Hércules');
    }

    public function test_sube_el_logo_y_se_sirve_sin_iniciar_sesion(): void
    {
        $this->actingAs($this->admin)->put(route('preferencias.taller'), [
            'nombre' => 'Taller Hércules',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
        ])->assertSessionHas('ok');

        $ruta = PreferenciaOperacion::rutaLogoTaller();
        Storage::disk('local')->assertExists($ruta);

        $this->get(route('dashboard'))->assertSee(PreferenciaOperacion::urlLogoTaller(), false);

        auth()->logout();
        $this->get(route('taller.logo'))->assertOk();
    }

    public function test_al_reemplazar_o_quitar_el_logo_se_borra_el_archivo_anterior(): void
    {
        $this->actingAs($this->admin);
        $this->put(route('preferencias.taller'), ['nombre' => 'T', 'logo' => UploadedFile::fake()->image('a.png')]);
        $primero = PreferenciaOperacion::rutaLogoTaller();

        $this->put(route('preferencias.taller'), ['nombre' => 'T', 'logo' => UploadedFile::fake()->image('b.png')]);
        $segundo = PreferenciaOperacion::rutaLogoTaller();
        Storage::disk('local')->assertMissing($primero);
        Storage::disk('local')->assertExists($segundo);

        $this->put(route('preferencias.taller'), ['nombre' => 'T', 'quitar_logo' => '1']);
        $this->assertNull(PreferenciaOperacion::rutaLogoTaller());
        Storage::disk('local')->assertMissing($segundo);
        $this->get(route('taller.logo'))->assertNotFound();
    }

    public function test_guardar_solo_el_nombre_conserva_el_logo(): void
    {
        $this->actingAs($this->admin);
        $this->put(route('preferencias.taller'), ['nombre' => 'T', 'logo' => UploadedFile::fake()->image('a.png')]);
        $ruta = PreferenciaOperacion::rutaLogoTaller();

        $this->put(route('preferencias.taller'), ['nombre' => 'Otro nombre']);

        $this->assertSame($ruta, PreferenciaOperacion::rutaLogoTaller());
    }

    public function test_rechaza_archivos_que_no_son_imagen_y_el_nombre_vacio(): void
    {
        $this->actingAs($this->admin)->put(route('preferencias.taller'), [
            'nombre' => '',
            'logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors(['nombre', 'logo']);

        $this->assertNull(PreferenciaOperacion::rutaLogoTaller());
    }

    public function test_el_formulario_general_de_preferencias_no_borra_los_datos_del_taller(): void
    {
        $this->actingAs($this->admin);
        $this->put(route('preferencias.taller'), ['nombre' => 'Taller Hércules', 'logo' => UploadedFile::fake()->image('a.png')]);

        $this->put(route('preferencias.update'), ['valores' => ['usar_precios' => '1']])->assertSessionHas('ok');

        $this->assertSame('Taller Hércules', PreferenciaOperacion::nombreTaller());
        $this->assertNotNull(PreferenciaOperacion::rutaLogoTaller());
    }

    public function test_recepcion_y_mecanico_no_pueden_cambiar_los_datos_del_taller(): void
    {
        foreach ([Rol::Recepcionista, Rol::Mecanico] as $rol) {
            $this->actingAs($this->usuario($rol))->put(route('preferencias.taller'), ['nombre' => 'Hackeado'])
                ->assertForbidden();
        }

        $this->assertSame(config('app.name'), PreferenciaOperacion::nombreTaller());
    }

    public function test_el_acta_pdf_lleva_el_nombre_y_el_logo_sin_agregar_hojas(): void
    {
        $this->actingAs($this->admin)->put(route('preferencias.taller'), [
            'nombre' => 'Taller Hércules',
            'logo' => UploadedFile::fake()->image('logo.png', 300, 120),
        ]);

        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = $this->ordenConRecepcion($recepcion);
        $inspeccion = $orden->recepcion->load(['fotos.anguloFoto', 'danios', 'firma', 'realizadaPor']);

        $html = view('inspecciones.pdf', [
            'inspeccion' => $inspeccion,
            'orden' => $orden,
            'checklist' => $inspeccion->checklistPorGrupo(),
            'terminos' => 'Términos.',
        ])->render();
        $this->assertStringContainsString('Taller Hércules', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);

        $pdf = $this->actingAs($recepcion)->get(route('inspecciones.pdf', $orden->recepcion))->assertOk()->getContent();
        $this->assertSame(2, preg_match_all('#/Type\s*/Page\b(?!s)#', $pdf), 'El logo no debe agregar hojas');
    }
}
