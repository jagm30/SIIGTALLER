<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que el descuento de la orden sea un porcentaje.
 * Si descuento_porcentaje tiene valor, "descuento" se calcula a partir de él;
 * si es NULL, "descuento" es una cantidad fija.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->decimal('descuento_porcentaje', 5, 2)->nullable()->after('descuento');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropColumn('descuento_porcentaje');
        });
    }
};
