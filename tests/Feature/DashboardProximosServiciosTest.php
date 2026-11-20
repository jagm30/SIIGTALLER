<?php

namespace Tests\Feature;

use App\Enums\EstadoRecordatorio;
use App\Enums\Rol;
use App\Models\RecordatorioServicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

class DashboardProximosServiciosTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
    }

    public function test_recepcion_ve_placas_fecha_y_telefono_de_los_proximos_servicios(): void
    {
        $vehiculo = $this->vehiculo();
        $this->recordatorio($vehiculo, now()->addMonths(3));

        $this->actingAs($this->usuario(Rol::Recepcionista))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Próximos servicios')
            ->assertSee('DHX-12-34')
            ->assertSee(now()->addMonths(3)->format('d/m/Y'))
            ->assertSee('tel:9611234567', false);
    }

    public function test_los_servicios_vencidos_aparecen_primero_y_marcados(): void
    {
        $this->recordatorio($this->vehiculo(['placas' => 'LEJ-00-01']), now()->addMonths(2));
        $this->recordatorio($this->vehiculo(['placas' => 'VEN-00-02']), now()->subDays(3));

        $this->actingAs($this->usuario(Rol::Administrador))->get(route('dashboard'))
            ->assertSeeInOrder(['VEN-00-02', 'vencido', 'LEJ-00-01']);
    }

    public function test_los_recordatorios_ya_realizados_no_aparecen(): void
    {
        $this->recordatorio($this->vehiculo(['placas' => 'HEC-00-03']), now()->addDays(5))
            ->update(['estado' => EstadoRecordatorio::Realizado]);

        $this->actingAs($this->usuario(Rol::Recepcionista))->get(route('dashboard'))
            ->assertSee('No hay servicios programados.')
            ->assertDontSee('HEC-00-03');
    }

    public function test_el_mecanico_no_ve_los_proximos_servicios(): void
    {
        $this->recordatorio($this->vehiculo(), now()->addDays(5));

        $this->actingAs($this->usuario(Rol::Mecanico))->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Próximos servicios')
            ->assertDontSee('9611234567');
    }

    private function recordatorio(Vehiculo $vehiculo, $fecha): RecordatorioServicio
    {
        return $vehiculo->recordatorios()->create([
            'descripcion' => 'Cambio de aceite',
            'fecha_programada' => $fecha,
        ]);
    }
}
