<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->string('logo_path')->nullable();
            // false = la creó recepción con alta rápida; el administrador la revisa
            $table->boolean('verificado')->default(true)->index();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('modelos_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marca_id')->constrained('marcas')->restrictOnDelete();
            $table->string('nombre', 80);
            $table->string('tipo_carroceria', 20); // App\Enums\TipoCarroceria: define el diagrama de daños
            $table->unsignedSmallInteger('anio_inicio')->nullable();
            $table->unsignedSmallInteger('anio_fin')->nullable(); // NULL = sigue vigente
            $table->boolean('verificado')->default(true)->index();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marca_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos_vehiculo');
        Schema::dropIfExists('marcas');
    }
};
