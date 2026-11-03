<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\OrdenServicio;
use App\Models\PreferenciaOperacion;
use App\Models\Refaccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

/** Quién ve precios y qué pasa cuando el taller trabaja sin precios. */
class PreciosTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
    }

    public function test_el_mecanico_no_ve_importes_pero_recepcion_si(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $mecanico = $this->usuario(Rol::Mecanico);
        $orden = $this->ordenEnReparacion($recepcion, $mecanico);
        $orden->trabajos()->create(['servicio_id' => $this->servicio(['precio' => 1234.5])->id]);
        $orden->recalcularTotales();

        $this->actingAs($mecanico)->get(route('ordenes.show', $orden))->assertOk()->assertDontSee('1,234.50')->assertDontSee('IVA incluido');
        $this->actingAs($recepcion)->get(route('ordenes.show', $orden))->assertSee('$1,234.50')->assertSee('IVA incluido');
    }

    public function test_el_mecanico_no_puede_cambiar_precios_aunque_los_mande(): void
    {
        $mecanico = $this->usuario(Rol::Mecanico);
        $orden = $this->ordenEnReparacion($this->usuario(Rol::Recepcionista), $mecanico);

        $this->actingAs($mecanico)->post(route('trabajos.store', $orden), ['servicio_id' => $this->servicio()->id, 'precio_unitario' => 1]);
        $this->post(route('refacciones-usadas.store', $orden), ['descripcion' => 'Tornillo', 'cantidad' => 1, 'precio_unitario' => 999]);

        $this->assertEquals(850.00, $orden->trabajos()->first()->precio_unitario, 'Usa el precio del catálogo');
        $this->assertNull($orden->refacciones()->first()->precio_unitario);
    }

    public function test_taller_sin_precios(): void
    {
        PreferenciaOperacion::where('clave', 'usar_precios')->update(['valor' => '0']);
        PreferenciaOperacion::olvidar();

        $admin = $this->usuario(Rol::Administrador);
        $recepcion = $this->usuario(Rol::Recepcionista);
        $mecanico = $this->usuario(Rol::Mecanico);
        $servicio = $this->servicio(['precio' => 850]);
        $refaccion = Refaccion::create(['descripcion' => 'Filtro', 'unidad' => 'pieza', 'precio_venta' => 130]);

        $orden = $this->ordenEnReparacion($recepcion, $mecanico);
        $this->assertFalse($orden->con_precios, 'La orden se congela sin precios');

        $this->actingAs($mecanico)->post(route('trabajos.store', $orden), ['servicio_id' => $servicio->id]);
        $this->post(route('refacciones-usadas.store', $orden), ['refaccion_id' => $refaccion->id, 'cantidad' => 2]);

        $orden->refresh();
        $this->assertSame('Cambio de aceite', $orden->trabajos()->first()->descripcion, 'El servicio sí se registra');
        $this->assertNull($orden->trabajos()->first()->importe);
        $this->assertNull($orden->refacciones()->first()->importe);
        $this->assertNull($orden->total);

        // Ni el administrador ve importes, y los catálogos no muestran el campo de precio
        $this->actingAs($admin)->get(route('ordenes.show', $orden))->assertOk()->assertDontSee('IVA incluido')->assertSee('Filtro');
        $this->get(route('servicios.create'))->assertDontSee('Precio con IVA');
    }

    public function test_las_ordenes_anteriores_conservan_sus_importes_al_quitar_precios(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = $this->ordenEnReparacion($recepcion, $this->usuario(Rol::Mecanico));
        $orden->trabajos()->create(['servicio_id' => $this->servicio()->id]);
        $orden->recalcularTotales();

        PreferenciaOperacion::where('clave', 'usar_precios')->update(['valor' => '0']);
        PreferenciaOperacion::olvidar();

        $this->assertEquals(850.00, $orden->fresh()->total, 'El dato se conserva');
        $this->assertFalse($recepcion->puedeVerPrecios($orden->fresh()), 'Pero nadie lo ve mientras esté bloqueado');
    }

    public function test_iva_incluido_se_calcula_sin_desglosarlo(): void
    {
        $orden = $this->ordenEnReparacion($this->usuario(Rol::Recepcionista), $this->usuario(Rol::Mecanico));
        $orden->trabajos()->create(['servicio_id' => $this->servicio(['precio' => 1160])->id]);
        $orden->recalcularTotales();

        $orden->refresh();
        $this->assertEquals(1160.00, $orden->total);
        $this->assertEquals(160.00, $orden->iva, 'IVA contenido en el total, solo para reportes');
    }

    public function test_descuento_de_recepcion_recalcula_el_total(): void
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $orden = $this->ordenEnReparacion($recepcion, $this->usuario(Rol::Mecanico));
        $orden->trabajos()->create(['servicio_id' => $this->servicio()->id]);
        $orden->recalcularTotales();

        $this->actingAs($recepcion)->patch(route('ordenes.update', $orden), ['descuento' => 50]);

        $this->assertEquals(800.00, OrdenServicio::find($orden->id)->total);
    }
}
