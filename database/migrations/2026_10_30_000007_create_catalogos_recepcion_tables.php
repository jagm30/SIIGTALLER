<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Puntos a revisar al recibir y entregar el vehículo (configurable sin migraciones).
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('grupo', 20);            // App\Enums\GrupoChecklist
            $table->string('nombre', 100);
            $table->string('tipo_respuesta', 15);   // App\Enums\TipoRespuesta
            $table->boolean('obligatorio')->default(true);
            $table->string('aplica_en', 10)->default('ambas'); // App\Enums\AplicaEn
            $table->json('tipos_carroceria')->nullable();     // NULL = todas
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['activo', 'grupo', 'orden']);
        });

        // Fotos que se deben tomar en la inspección.
        Schema::create('angulos_foto', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 30)->unique();
            $table->string('nombre', 80);
            $table->string('descripcion')->nullable(); // qué debe verse en la foto
            $table->boolean('obligatorio')->default(false);
            $table->json('tipos_carroceria')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Zonas del diagrama de carrocería para ubicar daños.
        Schema::create('zonas_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 40)->unique();
            $table->string('nombre', 80);
            $table->string('vista', 15);                 // App\Enums\VistaVehiculo
            $table->json('tipos_carroceria')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas_vehiculo');
        Schema::dropIfExists('angulos_foto');
        Schema::dropIfExists('checklist_items');
    }
};
