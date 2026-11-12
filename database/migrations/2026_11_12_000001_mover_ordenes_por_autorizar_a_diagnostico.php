<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El estado «Por autorizar» se retiró. Las órdenes que siguen en él regresan a «Diagnóstico»
 * para no desaparecer de las órdenes abiertas. La bitácora (orden_estados) no se toca: es historial.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('ordenes_servicio')->where('estado', 'por_autorizar')->update(['estado' => 'diagnostico']);
    }

    /** Regresa las órdenes cuyo último movimiento en la bitácora fue a «Por autorizar». */
    public function down(): void
    {
        $ultimos = DB::table('orden_estados')
            ->selectRaw('MAX(id)')
            ->groupBy('orden_servicio_id');

        $ordenes = DB::table('orden_estados')
            ->whereIn('id', $ultimos)
            ->where('estado_nuevo', 'por_autorizar')
            ->pluck('orden_servicio_id');

        DB::table('ordenes_servicio')
            ->whereIn('id', $ordenes)
            ->where('estado', 'diagnostico')
            ->update(['estado' => 'por_autorizar']);
    }
};
