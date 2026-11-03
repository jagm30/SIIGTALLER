<?php

namespace Tests\Feature;

use App\Enums\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

/** Cada rol entra solo a lo que le corresponde (ver App\Enums\Rol). */
class PermisosTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
    }

    public static function pantallas(): array
    {
        // [ruta, administrador, recepcionista, mecánico]
        return [
            'clientes' => ['/clientes', 200, 200, 403],
            'vehiculos' => ['/vehiculos', 200, 200, 403],
            'recibir vehículo' => ['/ordenes/nueva', 200, 200, 403],
            'órdenes' => ['/ordenes', 200, 200, 200],
            'servicios' => ['/servicios', 200, 403, 403],
            'refacciones' => ['/refacciones', 200, 403, 403],
            'marcas' => ['/marcas', 200, 403, 403],
            'usuarios' => ['/usuarios', 200, 403, 403],
            'preferencias' => ['/preferencias', 200, 403, 403],
        ];
    }

    #[DataProvider('pantallas')]
    public function test_acceso_por_rol(string $ruta, int $admin, int $recepcion, int $mecanico): void
    {
        $this->actingAs($this->usuario(Rol::Administrador))->get($ruta)->assertStatus($admin);
        $this->actingAs($this->usuario(Rol::Recepcionista))->get($ruta)->assertStatus($recepcion);
        $this->actingAs($this->usuario(Rol::Mecanico))->get($ruta)->assertStatus($mecanico);
    }

    public function test_el_mecanico_solo_ve_sus_ordenes(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $luis = $this->usuario(Rol::Mecanico);
        $pedro = $this->usuario(Rol::Mecanico);

        $orden = $this->ordenEnReparacion($recepcion, $luis);

        $this->actingAs($luis)->get(route('ordenes.show', $orden))->assertOk();
        $this->actingAs($pedro)->get(route('ordenes.show', $orden))->assertForbidden();

        // Tampoco aparece en su listado
        $this->actingAs($pedro)->get('/ordenes')->assertDontSee($orden->folio);
        $this->actingAs($luis)->get('/ordenes')->assertSee($orden->folio);
    }

    public function test_el_mecanico_no_puede_agregar_trabajos_a_ordenes_ajenas(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = $this->ordenEnReparacion($recepcion, $this->usuario(Rol::Mecanico));

        $this->actingAs($this->usuario(Rol::Mecanico))
            ->post(route('trabajos.store', $orden), ['descripcion' => 'Algo'])
            ->assertForbidden();

        $this->assertSame(0, $orden->trabajos()->count());
    }

    public function test_la_recepcionista_no_puede_registrar_trabajos(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = $this->ordenEnReparacion($recepcion, $this->usuario(Rol::Mecanico));

        $this->actingAs($recepcion)
            ->post(route('trabajos.store', $orden), ['descripcion' => 'Algo'])
            ->assertForbidden();
    }

    public function test_solo_el_administrador_regenera_el_qr(): void
    {
        $vehiculo = $this->vehiculo();
        $token = $vehiculo->qr_token;

        $this->actingAs($this->usuario(Rol::Recepcionista))->post(route('vehiculos.qr', $vehiculo))->assertForbidden();
        $this->assertSame($token, $vehiculo->fresh()->qr_token);

        $this->actingAs($this->usuario(Rol::Administrador))->post(route('vehiculos.qr', $vehiculo))->assertRedirect();
        $this->assertNotSame($token, $vehiculo->fresh()->qr_token);
    }
}
