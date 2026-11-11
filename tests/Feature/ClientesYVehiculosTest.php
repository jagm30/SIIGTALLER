<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Cliente;
use App\Models\ModeloVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

class ClientesYVehiculosTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    private User $recepcion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
        $this->recepcion = $this->usuario(Rol::Recepcionista);
    }

    public function test_registra_un_cliente_y_manda_a_registrar_su_vehiculo(): void
    {
        $respuesta = $this->actingAs($this->recepcion)->post('/clientes', [
            'tipo_persona' => 'fisica', 'nombre' => 'María', 'apellidos' => 'Gómez', 'telefono' => '9612345678', 'rfc' => 'gomm850312ab1',
        ]);

        $cliente = Cliente::firstOrFail();
        $respuesta->assertRedirect(route('vehiculos.create', ['cliente_id' => $cliente->id]));
        $this->assertSame('GOMM850312AB1', $cliente->rfc);
    }

    public function test_valida_telefono_rfc_y_razon_social(): void
    {
        $this->actingAs($this->recepcion)
            ->post('/clientes', ['tipo_persona' => 'moral', 'nombre' => 'Flotilla', 'rfc' => 'NO-VALIDO'])
            ->assertSessionHasErrors(['telefono', 'rfc', 'razon_social']);

        $this->assertSame(0, Cliente::count());
    }

    public function test_busca_clientes_por_telefono(): void
    {
        Cliente::create(['nombre' => 'Ana', 'telefono' => '9610000001']);
        Cliente::create(['nombre' => 'Beto', 'telefono' => '9610000002']);

        $this->actingAs($this->recepcion)->get('/clientes?buscar=0001')
            ->assertSee('Ana')->assertDontSee('Beto');
    }

    public function test_no_borra_un_cliente_con_vehiculos(): void
    {
        $vehiculo = $this->vehiculo();

        $this->actingAs($this->recepcion)->delete(route('clientes.destroy', $vehiculo->cliente))
            ->assertSessionHas('error');

        $this->assertNotNull($vehiculo->cliente->fresh());
    }

    public function test_registra_vehiculo_normalizando_placas_y_vin(): void
    {
        $cliente = Cliente::create(['nombre' => 'Juan', 'telefono' => '961']);
        $aveo = ModeloVehiculo::where('nombre', 'Aveo')->firstOrFail();

        $this->actingAs($this->recepcion)->post('/vehiculos', [
            'cliente_id' => $cliente->id, 'modelo_vehiculo_id' => $aveo->id,
            'placas' => 'txz-45 67', 'vin' => '3g1ta5af4hl 234567', 'km_actual' => 112000,
        ])->assertRedirect();

        $v = Vehiculo::firstOrFail();
        $this->assertSame(0, $v->km_actual, 'El kilometraje se captura en la recepción, no al registrar');
        $this->assertSame('TXZ4567', $v->placas_normalizadas);
        $this->assertSame('3G1TA5AF4HL234567', $v->vin);
        $this->assertSame($aveo->marca_id, $v->marca_id, 'La marca debe salir del modelo');
        $this->assertSame(1, $v->propietarios()->whereNull('hasta')->count());

        // Se encuentra escribiendo las placas de otra forma
        $this->get('/vehiculos?buscar=TXZ4567')->assertSee('Aveo');
    }

    public function test_el_registro_de_vehiculo_no_pide_kilometraje(): void
    {
        $this->actingAs($this->recepcion)->get(route('vehiculos.create'))
            ->assertOk()
            ->assertDontSee('name="km_actual"', false);
    }

    public function test_placas_no_encontradas_en_recepcion_se_prellenan_al_registrar(): void
    {
        $this->actingAs($this->recepcion)->get(route('ordenes.create', ['buscar' => 'abc-12-34']))
            ->assertSee('No encontramos ese vehículo.')
            ->assertSee(route('vehiculos.create', ['placas' => 'abc-12-34']), false);

        $this->get(route('vehiculos.create', ['placas' => 'abc-12-34']))
            ->assertSee('name="placas" value="ABC-12-34"', false);
    }

    public function test_un_vin_no_encontrado_en_recepcion_se_prellena_como_numero_de_serie(): void
    {
        $this->actingAs($this->recepcion)->get(route('ordenes.create', ['buscar' => '3N1CN7AD5KL123456']))
            ->assertSee(route('vehiculos.create', ['vin' => '3N1CN7AD5KL123456']), false);

        $this->get(route('vehiculos.create', ['vin' => '3n1cn7ad5kl123456']))
            ->assertSee('name="vin" value="3N1CN7AD5KL123456"', false)
            ->assertSee('name="placas" value=""', false);
    }

    public function test_rechaza_vin_repetido_o_con_letras_invalidas(): void
    {
        $existente = $this->vehiculo(['vin' => '3N1CN7AD5KL123456']);

        $base = ['cliente_id' => $existente->cliente_id, 'modelo_vehiculo_id' => $existente->modelo_vehiculo_id];

        $this->actingAs($this->recepcion)->post('/vehiculos', $base + ['vin' => '3n1cn7ad5kl123456'])
            ->assertSessionHasErrors(['vin' => 'Ya existe un vehículo con ese número de serie.']);

        $this->post('/vehiculos', $base + ['vin' => '3N1CN7AD5KL12345O']) // la O no existe en un VIN
            ->assertSessionHasErrors('vin');
    }

    public function test_alta_rapida_de_un_modelo_que_no_esta_en_el_catalogo(): void
    {
        $cliente = Cliente::create(['nombre' => 'Juan', 'telefono' => '961']);

        $this->actingAs($this->recepcion)->post('/vehiculos', [
            'cliente_id' => $cliente->id,
            'nueva_marca' => 'BYD', 'nuevo_modelo' => 'Dolphin', 'tipo_carroceria' => 'hatchback',
        ])->assertRedirect();

        $modelo = ModeloVehiculo::where('nombre', 'Dolphin')->firstOrFail();
        $this->assertFalse($modelo->verificado, 'Debe quedar por verificar');
        $this->assertFalse($modelo->marca->verificado);
        $this->assertSame($modelo->id, Vehiculo::firstOrFail()->modelo_vehiculo_id);

        // El administrador lo ve en pendientes
        $this->actingAs($this->usuario(Rol::Administrador))->get('/marcas')->assertSee('Por verificar')->assertSee('Dolphin');
    }

    public function test_registra_vehiculo_con_cliente_nuevo_desde_el_formulario(): void
    {
        $aveo = ModeloVehiculo::where('nombre', 'Aveo')->firstOrFail();

        $this->actingAs($this->recepcion)->post('/vehiculos', [
            'modelo_vehiculo_id' => $aveo->id,
            'nuevo_cliente_nombre' => 'Laura',
            'nuevo_cliente_apellidos' => 'Torres',
            'nuevo_cliente_telefono' => '9610000099',
            'placas' => 'ABC-12-34',
        ])->assertRedirect();

        $this->assertSame(1, Cliente::count(), 'Debe crearse exactamente un cliente');
        $cliente = Cliente::firstOrFail();
        $this->assertSame('Laura', $cliente->nombre);
        $this->assertSame('Torres', $cliente->apellidos);
        $this->assertSame('9610000099', $cliente->telefono);

        $vehiculo = Vehiculo::firstOrFail();
        $this->assertSame($cliente->id, $vehiculo->cliente_id);
    }

    public function test_cliente_nuevo_requiere_telefono(): void
    {
        $aveo = ModeloVehiculo::where('nombre', 'Aveo')->firstOrFail();

        $this->actingAs($this->recepcion)->post('/vehiculos', [
            'modelo_vehiculo_id' => $aveo->id,
            'nuevo_cliente_nombre' => 'Sin Telefono',
        ])->assertSessionHasErrors('nuevo_cliente_telefono');

        $this->assertSame(0, Cliente::count());
    }

    public function test_sin_cliente_ni_cliente_nuevo_falla_la_validacion(): void
    {
        $aveo = ModeloVehiculo::where('nombre', 'Aveo')->firstOrFail();

        $this->actingAs($this->recepcion)->post('/vehiculos', [
            'modelo_vehiculo_id' => $aveo->id,
            'placas' => 'XYZ-00-00',
        ])->assertSessionHasErrors('cliente_id');

        $this->assertSame(0, Vehiculo::count());
    }

    public function test_cambiar_de_dueno_conserva_el_historial(): void
    {
        $vehiculo = $this->vehiculo();
        $orden = $this->ordenConRecepcion($this->recepcion, $vehiculo);
        $nuevo = Cliente::create(['nombre' => 'Sofía', 'telefono' => '962']);

        $this->actingAs($this->recepcion)->put(route('vehiculos.update', $vehiculo), [
            'cliente_id' => $nuevo->id, 'modelo_vehiculo_id' => $vehiculo->modelo_vehiculo_id, 'placas' => $vehiculo->placas,
            'km_actual' => 0, // no debe bajar el kilometraje desde aquí
        ])->assertRedirect();

        $vehiculo->refresh();
        $this->assertSame($nuevo->id, $vehiculo->cliente_id);
        $this->assertSame(1, $vehiculo->ordenes()->count(), 'Las órdenes siguen con el vehículo');
        $this->assertSame($orden->recepcion->km, $vehiculo->km_actual);
    }
}
