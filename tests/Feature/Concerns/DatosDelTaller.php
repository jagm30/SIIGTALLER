<?php

namespace Tests\Feature\Concerns;

use App\Enums\EstadoOrden;
use App\Enums\Rol;
use App\Enums\TipoAdjunto;
use App\Models\Adjunto;
use App\Models\AnguloFoto;
use App\Models\ChecklistItem;
use App\Models\Cliente;
use App\Models\Inspeccion;
use App\Models\ModeloVehiculo;
use App\Models\OrdenServicio;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Seeders\AngulosFotoSeeder;
use Database\Seeders\ChecklistRecepcionSeeder;
use Database\Seeders\MarcasYModelosSeeder;
use Database\Seeders\PreferenciasOperacionSeeder;
use Database\Seeders\ZonasVehiculoSeeder;

/**
 * Datos de apoyo para las pruebas: catálogos reales (los mismos seeders de producción)
 * y atajos para crear usuarios, vehículos y órdenes sin repetir código en cada prueba.
 */
trait DatosDelTaller
{
    protected Sucursal $sucursal;

    protected function cargarCatalogos(): void
    {
        $this->seed([
            PreferenciasOperacionSeeder::class,
            MarcasYModelosSeeder::class,
            ChecklistRecepcionSeeder::class,
            AngulosFotoSeeder::class,
            ZonasVehiculoSeeder::class,
        ]);

        $this->sucursal = Sucursal::create(['nombre' => 'Matriz', 'prefijo_folio' => 'MTZ']);
    }

    protected function usuario(Rol $rol, array $datos = []): User
    {
        static $n = 0;
        $n++;

        return User::create($datos + [
            'name' => ucfirst($rol->value) . " $n",
            'email' => "{$rol->value}{$n}@taller.test",
            'password' => 'password',
            'rol' => $rol,
            'sucursal_id' => $rol === Rol::Administrador ? null : $this->sucursal->id,
        ]);
    }

    protected function vehiculo(array $datos = [], string $modelo = 'Versa'): Vehiculo
    {
        $m = ModeloVehiculo::where('nombre', $modelo)->firstOrFail();
        $cliente = Cliente::create(['nombre' => 'Juan', 'apellidos' => 'Pérez', 'telefono' => '9611234567']);

        return Vehiculo::create($datos + [
            'cliente_id' => $cliente->id,
            'marca_id' => $m->marca_id,
            'modelo_vehiculo_id' => $m->id,
            'anio' => 2019,
            'placas' => 'DHX-12-34',
            'km_actual' => 85000,
        ]);
    }

    protected function servicio(array $datos = []): Servicio
    {
        return Servicio::create($datos + ['nombre' => 'Cambio de aceite', 'tipo_cobro' => 'fijo', 'precio' => 850, 'intervalo_meses' => 6, 'intervalo_km' => 10000]);
    }

    /** Abre una orden y llena su recepción con todo lo obligatorio (sin pasar por HTTP). */
    protected function ordenConRecepcion(User $recepcion, ?Vehiculo $vehiculo = null): OrdenServicio
    {
        $orden = OrdenServicio::abrir($vehiculo ?? $this->vehiculo(), $recepcion, 'Servicio general');
        $this->llenarInspeccion($orden->recepcion, $orden->vehiculo->km_actual + 100);
        $orden->recepcion->completar();

        return $orden->refresh();
    }

    /** Lleva una orden con recepción completa hasta "En reparación". */
    protected function ordenEnReparacion(User $recepcion, User $mecanico): OrdenServicio
    {
        $orden = $this->ordenConRecepcion($recepcion);
        $orden->update(['mecanico_responsable_id' => $mecanico->id]);
        $orden->cambiarEstado(EstadoOrden::Diagnostico, $mecanico);
        $orden->cambiarEstado(EstadoOrden::EnReparacion, $recepcion);

        return $orden->refresh();
    }

    protected function llenarInspeccion(Inspeccion $insp, int $km): void
    {
        $insp->update([
            'km' => $km, 'nivel_combustible' => 4, 'persona_nombre' => 'Juan Pérez', 'sin_danios' => true,
            'cliente_retiro_valores' => true, 'acepta_terminos' => true, 'firmante_nombre' => 'Juan Pérez',
        ]);

        foreach (ChecklistItem::aplicables($insp->tipo, $insp->tipo_carroceria) as $item) {
            $insp->respuestas()->create(['checklist_item_id' => $item->id, 'valor' => $this->respuestaValida($item)]);
        }
        foreach (AnguloFoto::obligatoriosPara($insp->tipo_carroceria) as $angulo) {
            $this->adjuntoFalso($insp, TipoAdjunto::Foto, $angulo->id);
        }
        $this->adjuntoFalso($insp, TipoAdjunto::Firma);
    }

    protected function respuestaValida(ChecklistItem $item): string
    {
        return match ($item->tipo_respuesta->value) {
            'si_no' => 'si', 'estado' => 'bueno', 'nivel' => 'lleno', 'numero' => '2', default => 'ninguno',
        };
    }

    /** Registro de adjunto sin archivo real: basta para las reglas de completitud. */
    protected function adjuntoFalso(Inspeccion $insp, TipoAdjunto $tipo, ?int $anguloId = null): Adjunto
    {
        return $insp->adjuntos()->create([
            'tipo' => $tipo, 'angulo_foto_id' => $anguloId, 'disk' => 'local', 'ruta' => 'pruebas/x.jpg',
            'mime' => 'image/jpeg', 'tamano_bytes' => 1, 'hash_sha256' => str_repeat('0', 64),
        ]);
    }

    /** Firma dibujada como la manda el canvas del navegador: data:image/png;base64,... */
    protected function firmaPng(): string
    {
        $img = imagecreatetruecolor(120, 40);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        imageline($img, 5, 30, 115, 10, imagecolorallocate($img, 0, 0, 0));
        ob_start();
        imagepng($img);

        return 'data:image/png;base64,' . base64_encode(ob_get_clean());
    }
}
