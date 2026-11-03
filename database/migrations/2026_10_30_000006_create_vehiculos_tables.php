<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            // Dueño actual (denormalizado de vehiculo_propietarios para consultas rápidas)
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('marca_id')->constrained('marcas')->restrictOnDelete();
            $table->foreignId('modelo_vehiculo_id')->constrained('modelos_vehiculo')->restrictOnDelete();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->string('version', 80)->nullable();
            $table->string('color', 40)->nullable();
            $table->string('placas', 15)->nullable();
            $table->string('placas_normalizadas', 15)->nullable()->index(); // mayúsculas, sin espacios ni guiones
            $table->char('vin', 17)->nullable()->unique();
            $table->string('numero_motor', 30)->nullable();
            $table->string('combustible', 15)->nullable();  // App\Enums\Combustible
            $table->string('transmision', 15)->nullable();  // App\Enums\Transmision
            $table->unsignedInteger('km_actual')->default(0);
            $table->char('qr_token', 26)->unique();          // ULID; nunca exponer el id
            $table->boolean('qr_activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehiculo_propietarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->date('desde');
            $table->date('hasta')->nullable(); // NULL = dueño actual (solo uno abierto por vehículo)
            $table->timestamps();

            $table->index(['vehiculo_id', 'hasta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_propietarios');
        Schema::dropIfExists('vehiculos');
    }
};
