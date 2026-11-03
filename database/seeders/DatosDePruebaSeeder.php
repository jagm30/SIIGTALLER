<?php

namespace Database\Seeders;

use App\Enums\EstadoOrden;
use App\Enums\Rol;
use App\Enums\TipoAdjunto;
use App\Enums\TipoInspeccion;
use App\Enums\TipoOrden;
use App\Models\AnguloFoto;
use App\Models\ChecklistItem;
use App\Models\Cliente;
use App\Models\Inspeccion;
use App\Models\ModeloVehiculo;
use App\Models\OrdenServicio;
use App\Models\Refaccion;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\ZonaVehiculo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Datos de PRUEBA para desarrollo. No lo corras en producción.
 *
 *   php artisan db:seed --class=DatosDePruebaSeeder
 *
 * Usuarios (contraseña: password):
 *   admin@taller.test       administrador
 *   recepcion@taller.test   recepcionista
 *   mecanico@taller.test    mecanico
 *   mecanico2@taller.test   mecanico
 *
 * Crea 4 órdenes en distintas etapas: recepción en captura, en reparación,
 * entregada (con próximo servicio programado) y cancelada.
 * Las fotos y firmas son registros con rutas ficticias (no hay archivos reales).
 *
 * Requiere los catálogos: Preferencias, Marcas y modelos, Checklist, Ángulos y Zonas.
 */
class DatosDePruebaSeeder extends Seeder
{
    private User $recepcion;

    public function run(): void
    {
        if (OrdenServicio::withTrashed()->exists()) {
            $this->command?->warn('Ya hay órdenes en la base de datos; no se crean datos de prueba. Usa migrate:fresh --seed para empezar de cero.');

            return;
        }

        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Matriz'], ['prefijo_folio' => 'MTZ']);

        // ── Usuarios ────────────────────────────────────────────
        $this->usuario('Administrador', 'admin@taller.test', Rol::Administrador, null);
        $this->recepcion = $this->usuario('Ana López', 'recepcion@taller.test', Rol::Recepcionista, $sucursal->id);
        $mecanico = $this->usuario('Luis Hernández', 'mecanico@taller.test', Rol::Mecanico, $sucursal->id);
        $mecanico2 = $this->usuario('Pedro Gómez', 'mecanico2@taller.test', Rol::Mecanico, $sucursal->id);

        // ── Catálogo de servicios y refacciones ─────────────────
        $s = $this->servicios();
        $r = $this->refacciones();

        $s['aceite']->refaccionesSugeridas()->syncWithoutDetaching([
            $r['filtro_aceite']->id => ['cantidad' => 1],
            $r['aceite']->id => ['cantidad' => 4.5],
        ]);
        $s['afinacion']->refaccionesSugeridas()->syncWithoutDetaching([
            $r['bujia']->id => ['cantidad' => 4],
            $r['filtro_aire']->id => ['cantidad' => 1],
            $r['filtro_gasolina']->id => ['cantidad' => 1],
        ]);

        // ── Clientes y vehículos ────────────────────────────────
        $juan = Cliente::create(['nombre' => 'Juan', 'apellidos' => 'Pérez Ruiz', 'telefono' => '9611234567', 'email' => 'juan.perez@example.com']);
        $maria = Cliente::create(['nombre' => 'María', 'apellidos' => 'Gómez Díaz', 'telefono' => '9612345678']);
        $carlos = Cliente::create(['nombre' => 'Carlos', 'apellidos' => 'Martínez', 'telefono' => '9613456789', 'rfc' => 'MACC850312AB1']);
        $sofia = Cliente::create(['nombre' => 'Sofía', 'apellidos' => 'Ramírez', 'telefono' => '9614567890']);
        $flotilla = Cliente::create([
            'tipo_persona' => 'moral', 'nombre' => 'Distribuidora del Sureste',
            'razon_social' => 'Distribuidora del Sureste SA de CV', 'rfc' => 'DSU0101011A2',
            'telefono' => '9616000000', 'email' => 'flotilla@example.com',
        ]);

        $versa = $this->vehiculo($juan, 'Nissan', 'Versa', 2019, 'Gris', 'DHX-12-34', '3N1CN7AD5KL123456', 85000);
        $aveo = $this->vehiculo($maria, 'Chevrolet', 'Aveo', 2017, 'Rojo', 'TXZ-45-67', '3G1TA5AF4HL234567', 112300);
        $jetta = $this->vehiculo($carlos, 'Volkswagen', 'Jetta', 2021, 'Blanco', 'DPL-89-01', '3VWDB7BU2MM345678', 41200);
        $this->vehiculo($sofia, 'Italika', 'FT150', 2022, 'Negro', 'B4K-22', null, 9800);
        $np300 = $this->vehiculo($flotilla, 'Nissan', 'NP300', 2020, 'Blanco', 'CS-1234-A', '3N6AD33A1LK456789', 158000);
        $this->vehiculo($flotilla, 'Toyota', 'Hilux', 2023, 'Plata', 'CS-5678-B', 'MR0EX3DD4P0567890', 36500);

        // ── Orden 1: recepción a medio capturar (borrador) ──────
        $o1 = OrdenServicio::abrir($versa, $this->recepcion, 'Ruido al frenar y revisión de aceite');
        $o1->recepcion->update(['km' => 85320, 'nivel_combustible' => 3, 'persona_nombre' => 'Juan Pérez']);

        // ── Orden 2: en reparación, con trabajos y refacciones ──
        $o2 = OrdenServicio::abrir($aveo, $this->recepcion, 'Servicio de afinación, tiembla en ralentí');
        $this->completarInspeccion($o2->recepcion, 112450, 2, 'María Gómez', conDanio: true);
        $o2->update(['mecanico_responsable_id' => $mecanico->id]);
        $o2->cambiarEstado(EstadoOrden::Diagnostico, $mecanico, 'Bujías desgastadas y filtro de aire saturado');
        $o2->update(['diagnostico' => 'Bujías desgastadas, filtro de aire saturado. Se recomienda afinación mayor.']);
        $o2->cambiarEstado(EstadoOrden::PorAutorizar, $this->recepcion);
        $o2->cambiarEstado(EstadoOrden::EnReparacion, $this->recepcion, 'Cliente autorizó por teléfono');
        $this->agregarTrabajo($o2, $s['afinacion'], $mecanico);
        $this->agregarTrabajo($o2, $s['mano_obra'], $mecanico2, horas: 1.5, descripcion: 'Limpieza de cuerpo de aceleración');
        $o2->recalcularTotales();

        // ── Orden 3: entregada, con próximo servicio programado ─
        $o3 = OrdenServicio::abrir($jetta, $this->recepcion, 'Cambio de aceite');
        $this->completarInspeccion($o3->recepcion, 41250, 5, 'Carlos Martínez');
        $o3->update(['mecanico_responsable_id' => $mecanico->id]);
        $o3->cambiarEstado(EstadoOrden::Diagnostico, $mecanico);
        $o3->cambiarEstado(EstadoOrden::PorAutorizar, $this->recepcion);
        $o3->cambiarEstado(EstadoOrden::EnReparacion, $this->recepcion);
        $this->agregarTrabajo($o3, $s['aceite'], $mecanico);
        $this->agregarTrabajo($o3, $s['frenos'], $mecanico, horas: 2);
        $o3->recalcularTotales();
        $o3->cambiarEstado(EstadoOrden::Terminado, $mecanico);
        $this->completarInspeccion($o3->iniciarEntrega($this->recepcion), 41255, 5, 'Carlos Martínez');
        $o3->cambiarEstado(EstadoOrden::Entregado, $this->recepcion); // genera los recordatorios

        // ── Orden 4: cancelada, el cliente no autorizó ──────────
        $o4 = OrdenServicio::abrir($np300, $this->recepcion, 'Revisión de suspensión', TipoOrden::Diagnostico);
        $this->completarInspeccion($o4->recepcion, 158240, 4, 'Roberto Chofer', esCliente: false);
        $o4->cambiarEstado(EstadoOrden::Diagnostico, $mecanico2, 'Bujes de horquilla dañados');
        $o4->cambiarEstado(EstadoOrden::PorAutorizar, $this->recepcion);
        $o4->cambiarEstado(EstadoOrden::Cancelado, $this->recepcion, 'El cliente no autorizó el presupuesto');

        // Fechas de ingreso escalonadas para que se vean realistas en listados.
        foreach ([[$o3, 6], [$o4, 3], [$o2, 1], [$o1, 0]] as [$orden, $dias]) {
            $orden->forceFill(['fecha_ingreso' => now()->subDays($dias)->setTime(9, 30)])->save();
        }

        $this->command?->info('Datos de prueba creados. Usuarios: admin@, recepcion@, mecanico@, mecanico2@taller.test (contraseña: password)');
    }

    // ── Ayudantes ────────────────────────────────────────────────

    private function usuario(string $nombre, string $email, Rol $rol, ?int $sucursalId): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $nombre, 'password' => 'password', 'rol' => $rol, 'sucursal_id' => $sucursalId, 'activo' => true],
        );
    }

    /** @return array<string, Servicio> */
    private function servicios(): array
    {
        $datos = [
            'aceite' => ['SRV-001', 'Cambio de aceite y filtro', 'Motor', 'fijo', 850, 30, 6, 10000],
            'afinacion' => ['SRV-002', 'Afinación mayor', 'Motor', 'fijo', 2200, 120, 12, 20000],
            'frenos' => ['SRV-003', 'Revisión y cambio de frenos', 'Frenos', 'por_hora', 450, 90, null, null],
            'alineacion' => ['SRV-004', 'Alineación y balanceo', 'Suspensión', 'fijo', 600, 60, 6, 10000],
            'escaner' => ['SRV-005', 'Diagnóstico por escáner', 'Eléctrico', 'fijo', 450, 30, null, null],
            'mano_obra' => ['SRV-006', 'Mano de obra general', 'General', 'por_hora', 400, null, null, null],
        ];

        return array_map(fn ($d) => Servicio::firstOrCreate(['clave' => $d[0]], [
            'nombre' => $d[1], 'categoria' => $d[2], 'tipo_cobro' => $d[3], 'precio' => $d[4],
            'tiempo_estimado_min' => $d[5], 'intervalo_meses' => $d[6], 'intervalo_km' => $d[7],
        ]), $datos);
    }

    /** @return array<string, Refaccion> */
    private function refacciones(): array
    {
        $datos = [
            'filtro_aceite' => ['PH-3614', 'Filtro de aceite', 'Fram', 'Filtros', 'pieza', 75, 130],
            'aceite' => ['5W30-SIN', 'Aceite sintético 5W-30', 'Mobil', 'Aceites', 'litro', 115, 185],
            'bujia' => ['BKR5E-11', 'Bujía de níquel', 'NGK', 'Encendido', 'pieza', 45, 85],
            'filtro_aire' => ['CA-10242', 'Filtro de aire', 'Fram', 'Filtros', 'pieza', 140, 240],
            'filtro_gasolina' => ['G-3727', 'Filtro de gasolina', 'Fram', 'Filtros', 'pieza', 95, 170],
            'balatas' => ['D1092', 'Balatas delanteras cerámicas', 'Brembo', 'Frenos', 'juego', 520, 890],
            'liquido_frenos' => ['DOT4-500', 'Líquido de frenos DOT 4 (500 ml)', 'Bosch', 'Frenos', 'pieza', 90, 160],
            'anticongelante' => ['ANT-50', 'Anticongelante 50/50', 'Prestone', 'Enfriamiento', 'litro', 70, 120],
        ];

        return array_map(fn ($d) => Refaccion::firstOrCreate(['numero_parte' => $d[0], 'marca' => $d[2]], [
            'descripcion' => $d[1], 'categoria' => $d[3], 'unidad' => $d[4], 'costo' => $d[5], 'precio_venta' => $d[6],
        ]), $datos);
    }

    private function vehiculo(Cliente $dueno, string $marca, string $modelo, int $anio, string $color, ?string $placas, ?string $vin, int $km): Vehiculo
    {
        $m = ModeloVehiculo::whereHas('marca', fn ($q) => $q->where('nombre', $marca))->where('nombre', $modelo)->firstOrFail();

        $vehiculo = Vehiculo::create([
            'cliente_id' => $dueno->id, 'marca_id' => $m->marca_id, 'modelo_vehiculo_id' => $m->id,
            'anio' => $anio, 'color' => $color, 'placas' => $placas, 'vin' => $vin, 'km_actual' => $km,
            'combustible' => 'gasolina', 'transmision' => 'manual',
        ]);
        $vehiculo->propietarios()->create(['cliente_id' => $dueno->id, 'desde' => now()->subYears(2)->toDateString()]);

        return $vehiculo;
    }

    /** Llena todo lo obligatorio de una inspección y la completa (usa las mismas validaciones del sistema). */
    private function completarInspeccion(Inspeccion $insp, int $km, int $gasolina, string $persona, bool $conDanio = false, bool $esCliente = true): void
    {
        $insp->update([
            'km' => $km,
            'nivel_combustible' => $gasolina,
            'persona_nombre' => $persona,
            'persona_es_cliente' => $esCliente,
            'sin_danios' => ! $conDanio,
            'cliente_retiro_valores' => true,
            'acepta_terminos' => true,
            'firmante_nombre' => $persona,
            'dispositivo' => 'Tableta de recepción (datos de prueba)',
        ]);

        foreach (ChecklistItem::aplicables($insp->tipo, $insp->tipo_carroceria) as $item) {
            $insp->respuestas()->create([
                'checklist_item_id' => $item->id,
                'valor' => match ($item->tipo_respuesta->value) {
                    // Accesorios y documentos presentes; en el tablero, ningún testigo encendido.
                    'si_no' => match (true) {
                        $item->nombre === 'El vehículo enciende' => 'si',
                        $item->grupo->value === 'tablero' => 'no',
                        default => 'si',
                    },
                    'estado' => 'bueno',
                    'nivel' => 'lleno',
                    'numero' => '2',
                    'texto' => 'Ninguno',
                },
            ]);
        }

        foreach (AnguloFoto::obligatoriosPara($insp->tipo_carroceria) as $angulo) {
            $this->adjunto($insp, TipoAdjunto::Foto, "{$angulo->clave}.jpg", $angulo->id);
        }

        if ($conDanio) {
            $danio = $insp->danios()->create([
                'zona_vehiculo_id' => ZonaVehiculo::where('clave', 'puerta_del_izq')->value('id'),
                'vista' => 'izquierda', 'pos_x' => 42, 'pos_y' => 55,
                'tipo' => 'rayon', 'severidad' => 'leve', 'medida_cm' => 15,
                'descripcion' => 'Rayón horizontal en puerta del conductor',
            ]);
            $this->adjunto($danio, TipoAdjunto::Foto, 'danio-puerta.jpg');

            // En la entrega, el mismo daño se liga al registrado en la recepción.
            if ($insp->tipo === TipoInspeccion::Entrega) {
                $origen = $insp->orden->recepcion->danios()->first();
                $danio->update(['danio_origen_id' => $origen?->id]);
            }
        }

        $this->adjunto($insp, TipoAdjunto::Firma, 'firma.png');

        $insp->completar();
    }

    private function adjunto(Model $dueno, TipoAdjunto $tipo, string $archivo, ?int $anguloId = null): void
    {
        $ruta = "pruebas/{$dueno->getTable()}-{$dueno->getKey()}/{$archivo}";

        $dueno->morphMany(\App\Models\Adjunto::class, 'adjuntable')->create([
            'tipo' => $tipo,
            'angulo_foto_id' => $anguloId,
            'disk' => 'local',
            'ruta' => $ruta,
            'mime' => str_ends_with($archivo, '.png') ? 'image/png' : 'image/jpeg',
            'tamano_bytes' => 0,
            'hash_sha256' => hash('sha256', $ruta),
            'tomada_at' => now(),
            'subido_por_id' => $this->recepcion->id,
        ]);
    }

    private function agregarTrabajo(OrdenServicio $orden, Servicio $servicio, User $mecanico, ?float $horas = null, ?string $descripcion = null): void
    {
        $trabajo = $orden->trabajos()->create([
            'servicio_id' => $servicio->id,
            'descripcion' => $descripcion,
            'cantidad' => $horas ?? 1,
            'estado' => 'terminado',
        ]);
        $trabajo->mecanicos()->attach($mecanico->id, ['horas' => $horas ?? round(($servicio->tiempo_estimado_min ?? 60) / 60, 2)]);

        // Refacciones sugeridas del servicio (ej. afinación → bujías y filtros)
        foreach ($servicio->refaccionesSugeridas as $refaccion) {
            $orden->refacciones()->create([
                'orden_servicio_item_id' => $trabajo->id,
                'refaccion_id' => $refaccion->id,
                'cantidad' => $refaccion->pivot->cantidad,
            ]);
        }
    }
}
