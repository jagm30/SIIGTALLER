<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\ModeloVehiculo;
use App\Models\PreferenciaOperacion;
use App\Models\Refaccion;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

/** Catálogos, usuarios y preferencias: pantallas del administrador. */
class AdministracionTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
        $this->admin = $this->usuario(Rol::Administrador);
        $this->actingAs($this->admin);
    }

    public function test_crea_y_edita_un_servicio(): void
    {
        $this->post('/servicios', ['nombre' => 'Afinación mayor', 'tipo_cobro' => 'fijo', 'precio' => 2200, 'intervalo_meses' => 12, 'activo' => '1'])
            ->assertRedirect('/servicios');

        $servicio = Servicio::firstOrFail();
        $this->get('/servicios')->assertSee('Afinación mayor')->assertSee('$2,200.00');

        $this->put(route('servicios.update', $servicio), ['nombre' => 'Afinación mayor', 'tipo_cobro' => 'fijo', 'precio' => 2400])
            ->assertRedirect('/servicios');
        $this->assertEquals(2400, $servicio->fresh()->precio);
        $this->assertFalse($servicio->fresh()->activo, 'Sin la casilla marcada queda inactivo');
    }

    public function test_configura_las_refacciones_que_lleva_un_servicio(): void
    {
        $servicio = $this->servicio(['nombre' => 'Afinación mayor']);
        $bujia = Refaccion::create(['descripcion' => 'Bujía de iridio', 'unidad' => 'pieza', 'activo' => true]);
        $filtro = Refaccion::create(['descripcion' => 'Filtro de aire', 'unidad' => 'pieza', 'activo' => true]);

        $this->post(route('servicios.refacciones.store', $servicio), ['refaccion_id' => $bujia->id, 'cantidad' => 4])->assertSessionHas('ok');
        $this->post(route('servicios.refacciones.store', $servicio), ['refaccion_id' => $filtro->id, 'cantidad' => 1]);
        // Volver a agregar la misma no la duplica: actualiza la cantidad
        $this->post(route('servicios.refacciones.store', $servicio), ['refaccion_id' => $bujia->id, 'cantidad' => 6]);

        $this->assertSame(2, $servicio->refaccionesSugeridas()->count());
        $this->assertEquals(6, $servicio->refaccionesSugeridas()->find($bujia->id)->pivot->cantidad);

        // La pantalla de edición muestra la lista
        $this->get(route('servicios.edit', $servicio))->assertSee('Refacciones que lleva')->assertSee('Bujía de iridio');

        $this->delete(route('servicios.refacciones.destroy', [$servicio, $filtro]))->assertSessionHas('ok');
        $this->assertSame(1, $servicio->refaccionesSugeridas()->count());

        // Sin cantidad o sin refacción no se guarda
        $this->post(route('servicios.refacciones.store', $servicio), ['cantidad' => 0])->assertSessionHasErrors(['refaccion_id', 'cantidad']);
    }

    public function test_solo_el_administrador_configura_las_refacciones_de_un_servicio(): void
    {
        $servicio = $this->servicio();
        $bujia = Refaccion::create(['descripcion' => 'Bujía', 'unidad' => 'pieza', 'activo' => true]);

        $this->actingAs($this->usuario(Rol::Mecanico))
            ->post(route('servicios.refacciones.store', $servicio), ['refaccion_id' => $bujia->id, 'cantidad' => 4])
            ->assertForbidden();

        $this->assertSame(0, $servicio->refaccionesSugeridas()->count());
    }

    public function test_un_servicio_usado_se_desactiva_en_lugar_de_borrarse(): void
    {
        $orden = $this->ordenEnReparacion($this->usuario(Rol::Recepcionista), $this->usuario(Rol::Mecanico));
        $servicio = $this->servicio();
        $orden->trabajos()->create(['servicio_id' => $servicio->id]);

        $this->delete(route('servicios.destroy', $servicio))->assertSessionHas('ok');

        $this->assertNotNull($servicio->fresh());
        $this->assertFalse($servicio->fresh()->activo);
    }

    public function test_fusiona_un_modelo_duplicado(): void
    {
        $versa = ModeloVehiculo::where('nombre', 'Versa')->firstOrFail();
        $duplicado = ModeloVehiculo::create(['marca_id' => $versa->marca_id, 'nombre' => 'Bersa', 'tipo_carroceria' => 'sedan', 'verificado' => false]);
        $vehiculo = $this->vehiculo(['modelo_vehiculo_id' => $duplicado->id]);

        $this->post(route('modelos.fusionar', $duplicado), ['destino_id' => $duplicado->id])->assertSessionHasErrors('destino_id');
        $this->post(route('modelos.fusionar', $duplicado), ['destino_id' => $versa->id])->assertSessionHas('ok');

        $this->assertSame($versa->id, $vehiculo->fresh()->modelo_vehiculo_id);
        $this->assertNull(ModeloVehiculo::withTrashed()->find($duplicado->id));
    }

    public function test_crea_usuarios_con_contrasena_cifrada(): void
    {
        $this->post('/usuarios', [
            'name' => 'Luis', 'email' => 'luis@taller.test', 'rol' => 'mecanico', 'sucursal_id' => $this->sucursal->id,
            'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'activo' => '1',
        ])->assertRedirect('/usuarios');

        $luis = User::where('email', 'luis@taller.test')->firstOrFail();
        $this->assertTrue(Hash::check('secreto123', $luis->password));
        $this->assertTrue($luis->esMecanico());
    }

    public function test_un_mecanico_necesita_sucursal_y_la_contrasena_se_confirma(): void
    {
        $this->post('/usuarios', [
            'name' => 'Luis', 'email' => 'luis@taller.test', 'rol' => 'mecanico',
            'password' => 'secreto123', 'password_confirmation' => 'otra',
        ])->assertSessionHasErrors(['sucursal_id', 'password']);
    }

    public function test_editar_sin_contrasena_no_la_cambia(): void
    {
        $user = $this->usuario(Rol::Recepcionista);
        $hash = $user->password;

        $this->put(route('usuarios.update', $user), ['name' => 'Ana López', 'email' => $user->email, 'rol' => 'recepcionista', 'sucursal_id' => $this->sucursal->id, 'activo' => '1'])
            ->assertRedirect('/usuarios');

        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame('Ana López', $user->fresh()->name);
    }

    public function test_el_administrador_no_puede_desactivarse_a_si_mismo(): void
    {
        $this->put(route('usuarios.update', $this->admin), ['name' => $this->admin->name, 'email' => $this->admin->email, 'rol' => 'administrador'])
            ->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->activo);
    }

    public function test_guarda_preferencias_y_sube_la_version_de_los_terminos(): void
    {
        $this->get('/preferencias')->assertOk()->assertSee('usar_precios');

        // Solo enviamos "usar_precios" marcado: las demás casillas quedan desmarcadas
        $this->put('/preferencias', ['valores' => [
            'usar_precios' => '1',
            'iva_porcentaje' => '16',
            'recepcion_terminos' => 'Texto nuevo de términos',
            'recepcion_terminos_version' => '1',
            'recordatorio_meses_default' => '6',
            'recordatorio_km_default' => '10000',
        ]])->assertSessionHas('ok');

        $this->assertTrue(PreferenciaOperacion::bool('usar_precios'));
        $this->assertFalse(PreferenciaOperacion::bool('entrega_inspeccion_obligatoria', null, true));
        $this->assertSame(2, PreferenciaOperacion::int('recepcion_terminos_version'));
        $this->assertSame($this->admin->id, PreferenciaOperacion::where('clave', 'recepcion_terminos')->value('actualizado_por_id'));
    }

    public function test_rechaza_valores_no_numericos_en_preferencias(): void
    {
        $this->put('/preferencias', ['valores' => ['iva_porcentaje' => 'dieciséis']])->assertSessionHasErrors('valores.iva_porcentaje');

        $this->assertSame(16.0, PreferenciaOperacion::decimal('iva_porcentaje'));
    }
}
