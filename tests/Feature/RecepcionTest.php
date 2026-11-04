<?php

namespace Tests\Feature;

use App\Enums\EstadoInspeccion;
use App\Enums\Rol;
use App\Enums\TipoInspeccion;
use App\Models\AnguloFoto;
use App\Models\ChecklistItem;
use App\Models\OrdenServicio;
use App\Models\User;
use App\Models\ZonaVehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

/** La recepción completa en la tableta, paso a paso, como la haría recepción. */
class RecepcionTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    private User $recepcion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
        Storage::fake('local');
        $this->recepcion = $this->usuario(Rol::Recepcionista);
    }

    public function test_recepcion_completa_desde_la_tableta(): void
    {
        $vehiculo = $this->vehiculo();

        // 1. Busca por placas escritas distinto y abre la orden
        $this->actingAs($this->recepcion)->get('/ordenes/nueva?buscar=dhx1234')->assertSee('Versa');

        $respuesta = $this->post('/ordenes', ['vehiculo_id' => $vehiculo->id, 'motivo_ingreso' => 'Ruido al frenar', 'tipo' => 'servicio']);
        $orden = OrdenServicio::firstOrFail();
        $respuesta->assertRedirect(route('inspecciones.edit', $orden->recepcion));
        $this->assertSame('MTZ-000001', $orden->folio);

        // 2. La pantalla de captura muestra checklist, fotos y lo que falta
        $this->get(route('inspecciones.edit', $orden->recepcion))
            ->assertOk()->assertSee('Revisión del vehículo')->assertSee('Llanta de refacción')->assertSee('Odómetro')->assertSee('Faltan');

        // 3. Guarda avance parcial: se puede salir y regresar
        $this->put(route('inspecciones.update', $orden->recepcion), ['km' => 85200, 'nivel_combustible' => 3])
            ->assertSessionHas('ok', 'Avance guardado.');
        $this->assertSame(85200, $orden->recepcion->fresh()->km);

        // 4. Intentar completar a medias regresa la lista exacta de lo que falta
        $this->post(route('inspecciones.completar', $orden->recepcion))
            ->assertSessionHasErrors('inspeccion');
        $this->assertSame(EstadoInspeccion::Borrador, $orden->recepcion->fresh()->estado);

        // 5. Checklist + declaraciones + términos
        $respuestas = ChecklistItem::aplicables(TipoInspeccion::Recepcion, 'sedan')
            ->mapWithKeys(fn ($i) => [$i->id => ['valor' => $this->respuestaValida($i)]])->all();

        $this->put(route('inspecciones.update', $orden->recepcion), [
            'km' => 85200, 'nivel_combustible' => 3, 'placas' => 'DHX-12-34',
            'persona_nombre' => 'Juan Pérez', 'persona_es_cliente' => '1',
            'sin_danios' => '0', 'cliente_retiro_valores' => '1', 'objetos_personales' => 'Silla de bebé',
            'acepta_terminos' => '1', 'firmante_nombre' => 'Juan Pérez',
            'respuestas' => $respuestas,
        ])->assertSessionHasNoErrors();

        // 6. Fotos por ángulo (se suben reales y se guardan con su huella)
        $fotos = AnguloFoto::obligatoriosPara('sedan')->mapWithKeys(fn ($a) => [$a->id => UploadedFile::fake()->image("{$a->clave}.jpg", 800, 600)])->all();
        $this->post(route('inspecciones.fotos', $orden->recepcion), ['fotos' => $fotos])->assertSessionHas('ok');

        $foto = $orden->recepcion->fotos()->firstOrFail();
        Storage::disk('local')->assertExists($foto->ruta);
        $this->assertSame(64, strlen($foto->hash_sha256));
        $this->assertSame('inspeccion', $foto->adjuntable_type, 'Usa el alias corto del morph map');

        // 7. Un rayón con su foto
        $zona = ZonaVehiculo::where('clave', 'puerta_del_izq')->firstOrFail();
        $this->post(route('danios.store', $orden->recepcion), [
            'zona_vehiculo_id' => $zona->id, 'vista' => 'izquierda', 'tipo' => 'rayon', 'severidad' => 'leve',
            'medida_cm' => 15, 'foto' => UploadedFile::fake()->image('rayon.jpg'),
        ])->assertSessionHas('ok', 'Daño registrado.');

        // 8. Firma dibujada en el canvas
        $this->post(route('inspecciones.firma', $orden->recepcion), ['firma' => $this->firmaPng()])->assertSessionHas('ok');

        // 9. Completar: ya no falta nada
        $this->post(route('inspecciones.completar', $orden->recepcion))
            ->assertRedirect(route('ordenes.show', $orden))
            ->assertSessionHas('ok');

        $recepcion = $orden->recepcion->fresh();
        $this->assertTrue($recepcion->estaCompletada());
        $this->assertSame(85200, $vehiculo->fresh()->km_actual);
        $this->assertSame(1, $recepcion->danios()->count());

        // La orden muestra el resumen con las fotos
        $this->get(route('ordenes.show', $orden))->assertOk()->assertSee('Completada')->assertSee('1 registrados');
    }

    public function test_el_guardado_automatico_responde_json_sin_redirigir(): void
    {
        // La pantalla guarda el avance así antes de subir fotos, para no perder respuestas.
        $orden = OrdenServicio::abrir($this->vehiculo(), $this->recepcion, 'Revisión');
        $gato = ChecklistItem::where('nombre', 'Gato')->firstOrFail();

        $this->actingAs($this->recepcion)
            ->putJson(route('inspecciones.update', $orden->recepcion), ['respuestas' => [$gato->id => ['valor' => 'no', 'comentario' => 'no trae']]])
            ->assertOk()->assertJson(['guardado' => true]);

        $respuesta = $orden->recepcion->respuestas()->where('checklist_item_id', $gato->id)->firstOrFail();
        $this->assertSame('no', $respuesta->valor);
        $this->assertSame('Gato', $respuesta->item_nombre);
    }

    public function test_el_checklist_compacto_trae_marcar_todos_por_grupo(): void
    {
        $orden = OrdenServicio::abrir($this->vehiculo(), $this->recepcion, 'Revisión');

        $pagina = $this->actingAs($this->recepcion)->get(route('inspecciones.edit', $orden->recepcion))->assertOk();

        // Botones para marcar toda una columna (ej. "Bueno" en Exterior) y para limpiar el grupo
        $pagina->assertSee('data-marcar="bueno"', false)->assertSee('data-marcar="no"', false)->assertSee('data-limpiar', false);
        // Cada punto de opciones lleva un campo vacío antes, para que "Limpiar" sí borre al guardar
        $gato = ChecklistItem::where('nombre', 'Gato')->firstOrFail();
        $pagina->assertSee('<input type="hidden" name="respuestas[' . $gato->id . '][valor]" value="">', false);
    }

    public function test_las_preguntas_de_si_no_de_funcionamiento_no_se_marcan_en_masa(): void
    {
        // "El vehículo enciende" y "Llegó en grúa" van en Funcionamiento, fuera del Tablero:
        // "Marcar todos: No" del Tablero (ningún testigo encendido) no debe decir que el auto no enciende.
        $this->assertSame('funcionamiento', ChecklistItem::where('nombre', 'El vehículo enciende')->firstOrFail()->grupo->value);
        $this->assertFalse(ChecklistItem::where('grupo', 'tablero')->where('nombre', 'Llegó en grúa')->exists());

        // Y como en Funcionamiento son solo dos de Sí/No, su bloque no lleva botón de marcar todos.
        $orden = OrdenServicio::abrir($this->vehiculo(), $this->recepcion, 'Revisión');
        $this->actingAs($this->recepcion)->get(route('inspecciones.edit', $orden->recepcion))
            ->assertSeeInOrder(['Funcionamiento', '<span class="titulo-columna">Sí</span>', 'El vehículo enciende', 'data-marcar="bueno"'], false);
    }

    public function test_limpiar_un_punto_borra_su_respuesta_al_guardar(): void
    {
        $orden = OrdenServicio::abrir($this->vehiculo(), $this->recepcion, 'Revisión');
        $gato = ChecklistItem::where('nombre', 'Gato')->firstOrFail();

        $this->actingAs($this->recepcion)->put(route('inspecciones.update', $orden->recepcion), ['respuestas' => [$gato->id => ['valor' => 'si']]]);
        $this->assertSame(1, $orden->recepcion->respuestas()->count());

        // Así llega del formulario cuando se limpió: solo el campo vacío, sin opción marcada
        $this->put(route('inspecciones.update', $orden->recepcion), ['respuestas' => [$gato->id => ['valor' => '', 'comentario' => '']]]);
        $this->assertSame(0, $orden->recepcion->respuestas()->count());
    }

    public function test_ignora_respuestas_de_puntos_que_no_aplican(): void
    {
        // Un punto exclusivo de motocicleta no se guarda en la recepción de un sedán.
        $orden = OrdenServicio::abrir($this->vehiculo(), $this->recepcion, 'Revisión');
        $soloMoto = ChecklistItem::where('nombre', 'Delantera')->firstOrFail();

        $this->actingAs($this->recepcion)->put(route('inspecciones.update', $orden->recepcion), ['respuestas' => [$soloMoto->id => ['valor' => 'bueno'], 99999 => ['valor' => 'si']]]);

        $this->assertSame(0, $orden->recepcion->respuestas()->count());
    }

    public function test_una_inspeccion_completada_ya_no_se_puede_modificar(): void
    {
        $orden = $this->ordenConRecepcion($this->recepcion);
        $insp = $orden->recepcion;

        $this->actingAs($this->recepcion);
        $this->put(route('inspecciones.update', $insp), ['km' => 1])->assertStatus(409);
        $this->post(route('inspecciones.fotos', $insp), ['fotos' => [1 => UploadedFile::fake()->image('x.jpg')]])->assertStatus(409);
        $this->post(route('danios.store', $insp), ['vista' => 'frontal', 'tipo' => 'golpe', 'severidad' => 'leve'])->assertStatus(409);
        $this->post(route('inspecciones.firma', $insp), ['firma' => $this->firmaPng()])->assertStatus(409);

        // Abrir la captura de nuevo solo redirige a la orden
        $this->get(route('inspecciones.edit', $insp))->assertRedirect(route('ordenes.show', $orden));
        $this->assertNotSame(1, $insp->fresh()->km);
    }

    public function test_marcar_sin_danios_y_registrar_uno_no_se_contradice(): void
    {
        $orden = OrdenServicio::abrir($this->vehiculo(), $this->recepcion, 'Revisión');
        $orden->recepcion->update(['sin_danios' => true]);

        $this->actingAs($this->recepcion)->post(route('danios.store', $orden->recepcion), ['vista' => 'frontal', 'tipo' => 'golpe', 'severidad' => 'moderado']);

        $this->assertFalse($orden->recepcion->fresh()->sin_danios, 'Al registrar un daño deja de estar "sin daños"');
    }

    public function test_km_menor_al_anterior_exige_justificacion(): void
    {
        $orden = OrdenServicio::abrir($this->vehiculo(['km_actual' => 90000]), $this->recepcion, 'Revisión');

        $this->actingAs($this->recepcion)->put(route('inspecciones.update', $orden->recepcion), ['km' => 1200]);

        $this->assertContains('El kilometraje es menor al anterior (90000); escribe la justificación.', $orden->recepcion->fresh()->faltantes());
    }

    public function test_rechaza_archivos_que_no_son_imagenes_y_firmas_falsas(): void
    {
        $orden = OrdenServicio::abrir($this->vehiculo(), $this->recepcion, 'Revisión');
        $angulo = AnguloFoto::firstOrFail();

        $this->actingAs($this->recepcion)
            ->post(route('inspecciones.fotos', $orden->recepcion), ['fotos' => [$angulo->id => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('fotos.' . $angulo->id);

        $this->post(route('inspecciones.firma', $orden->recepcion), ['firma' => 'data:image/png;base64,' . base64_encode('no soy png')])
            ->assertSessionHasErrors('firma');

        $this->assertSame(0, $orden->recepcion->adjuntos()->count());
    }

    public function test_volver_a_firmar_reemplaza_la_firma_anterior(): void
    {
        $orden = OrdenServicio::abrir($this->vehiculo(), $this->recepcion, 'Revisión');
        $this->actingAs($this->recepcion);

        $this->post(route('inspecciones.firma', $orden->recepcion), ['firma' => $this->firmaPng()]);
        $primera = $orden->recepcion->firma()->first()->ruta;
        $this->post(route('inspecciones.firma', $orden->recepcion), ['firma' => $this->firmaPng()]);

        $this->assertSame(1, $orden->recepcion->adjuntos()->where('tipo', 'firma')->count());
        Storage::disk('local')->assertMissing($primera);
    }

    public function test_las_fotos_solo_las_ve_quien_puede_ver_la_orden(): void
    {
        $orden = OrdenServicio::abrir($this->vehiculo(), $this->recepcion, 'Revisión');
        $angulo = AnguloFoto::firstOrFail();
        $this->actingAs($this->recepcion)->post(route('inspecciones.fotos', $orden->recepcion), ['fotos' => [$angulo->id => UploadedFile::fake()->image('f.jpg')]]);
        $foto = $orden->recepcion->fotos()->firstOrFail();

        $this->get(route('adjuntos.show', $foto))->assertOk();
        $this->actingAs($this->usuario(Rol::Mecanico))->get(route('adjuntos.show', $foto))->assertForbidden();
        auth()->logout();
        $this->get(route('adjuntos.show', $foto))->assertRedirect('/login');
    }
}
