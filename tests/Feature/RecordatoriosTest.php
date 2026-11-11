<?php

namespace Tests\Feature;

use App\Enums\EstadoOrden;
use App\Enums\Rol;
use App\Models\OrdenServicio;
use App\Models\PreferenciaOperacion;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

/** Próximo servicio al entregar: intervalo del servicio o, si no tiene, el general de Preferencias. */
class RecordatoriosTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
    }

    public function test_un_servicio_con_intervalo_propio_usa_el_suyo(): void
    {
        $orden = $this->entregarCon($this->servicio(['nombre' => 'Afinación mayor', 'intervalo_meses' => 12, 'intervalo_km' => 20000]));

        $proximo = $orden->vehiculo->recordatorios()->pendientes()->sole();
        $this->assertTrue($proximo->fecha_programada->isSameDay(now()->addMonths(12)));
        $this->assertSame($orden->recepcion->km + 20000, $proximo->km_programado);
    }

    public function test_un_servicio_sin_intervalo_usa_el_general_de_preferencias(): void
    {
        $orden = $this->entregarCon($this->servicio(['nombre' => 'Revisión general', 'intervalo_meses' => null, 'intervalo_km' => null]));

        // Los valores del seeder de Preferencias: 6 meses y 10,000 km
        $proximo = $orden->vehiculo->recordatorios()->pendientes()->sole();
        $this->assertSame('Revisión general', $proximo->descripcion);
        $this->assertTrue($proximo->fecha_programada->isSameDay(now()->addMonths(6)));
        $this->assertSame($orden->recepcion->km + 10000, $proximo->km_programado);
    }

    public function test_al_cambiar_las_preferencias_cambia_el_intervalo_general(): void
    {
        PreferenciaOperacion::where('clave', 'recordatorio_meses_default')->update(['valor' => '3']);
        PreferenciaOperacion::where('clave', 'recordatorio_km_default')->update(['valor' => '5000']);
        PreferenciaOperacion::olvidar();

        $orden = $this->entregarCon($this->servicio(['intervalo_meses' => null, 'intervalo_km' => null]));

        $proximo = $orden->vehiculo->recordatorios()->pendientes()->sole();
        $this->assertTrue($proximo->fecha_programada->isSameDay(now()->addMonths(3)));
        $this->assertSame($orden->recepcion->km + 5000, $proximo->km_programado);
    }

    public function test_con_el_intervalo_general_en_cero_un_servicio_sin_intervalo_no_programa_nada(): void
    {
        PreferenciaOperacion::whereIn('clave', ['recordatorio_meses_default', 'recordatorio_km_default'])->update(['valor' => '0']);
        PreferenciaOperacion::olvidar();

        $orden = $this->entregarCon($this->servicio(['intervalo_meses' => null, 'intervalo_km' => null]));

        $this->assertSame(0, $orden->vehiculo->recordatorios()->count());
    }

    public function test_el_catalogo_indica_cuando_un_servicio_usa_el_intervalo_general(): void
    {
        $this->servicio(['nombre' => 'Revisión general', 'intervalo_meses' => null, 'intervalo_km' => null]);

        $this->actingAs($this->usuario(Rol::Administrador))->get(route('servicios.index'))
            ->assertSeeInOrder(['Revisión general', '6 meses o 10,000 km', '(general)']);
    }

    private function entregarCon(Servicio $servicio): OrdenServicio
    {
        $recepcion = $this->usuario(Rol::Recepcionista);
        $mecanico = $this->usuario(Rol::Mecanico);
        $orden = $this->ordenEnReparacion($recepcion, $mecanico);
        $orden->trabajos()->create(['servicio_id' => $servicio->id]);
        $orden->cambiarEstado(EstadoOrden::Terminado, $mecanico);
        $orden->iniciarEntrega($recepcion)->completar();
        $orden->cambiarEstado(EstadoOrden::Entregado, $recepcion);

        return $orden->refresh();
    }
}
