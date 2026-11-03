<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preferencias_operacion', function (Blueprint $table) {
            $table->id();
            // NULL = valor general; con valor = excepción para esa sucursal
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->restrictOnDelete();
            $table->string('grupo', 30);       // precios | recepcion | entrega | recordatorios | general
            $table->string('clave', 60);
            $table->text('valor')->nullable();
            $table->string('tipo', 10);        // App\Enums\TipoPreferencia
            $table->string('descripcion')->nullable();
            $table->foreignId('actualizado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // MySQL permite varios NULL en un índice único: la unicidad del valor
            // general (sucursal_id NULL) se valida además en la aplicación.
            $table->unique(['sucursal_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferencias_operacion');
    }
};
