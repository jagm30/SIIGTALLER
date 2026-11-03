<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Acta de cómo llegó (recepción) o salió (entrega) el vehículo.
         * Los campos marcados "al completar" aceptan NULL mientras la inspección es
         * borrador, pero Inspeccion::completar() los exige para completarla.
         */
        Schema::create('inspecciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->string('tipo', 10);                         // App\Enums\TipoInspeccion
            $table->string('estado', 12)->default('borrador')->index(); // App\Enums\EstadoInspeccion
            $table->foreignId('realizada_por_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('iniciada_at');
            $table->dateTime('completada_at')->nullable();
            $table->string('tipo_carroceria', 20);              // snapshot: diagrama y zonas usados

            $table->unsignedInteger('km')->nullable();                  // al completar
            $table->string('km_justificacion')->nullable();             // si km < vehiculos.km_actual
            $table->unsignedTinyInteger('nivel_combustible')->nullable(); // al completar; octavos 0-8
            $table->string('placas', 15)->nullable();
            $table->boolean('sin_placas')->default(false);

            $table->string('persona_nombre', 150)->nullable();          // al completar
            $table->string('persona_telefono', 20)->nullable();
            $table->boolean('persona_es_cliente')->default(true);

            // Declaraciones explícitas: un vacío nunca significa "no hay".
            $table->boolean('sin_danios')->nullable();                  // al completar
            $table->text('objetos_personales')->nullable();
            $table->boolean('cliente_retiro_valores')->nullable();      // al completar
            $table->text('observaciones')->nullable();

            $table->boolean('acepta_terminos')->nullable();             // al completar (si firma obligatoria)
            $table->unsignedSmallInteger('terminos_version')->nullable();
            $table->string('firmante_nombre', 150)->nullable();
            $table->dateTime('firmado_at')->nullable();
            $table->string('dispositivo', 120)->nullable();
            $table->timestamps();

            $table->unique(['orden_servicio_id', 'tipo']);
        });

        Schema::create('inspeccion_respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspeccion_id')->constrained('inspecciones')->cascadeOnDelete();
            // NULL = punto libre agregado en el momento
            $table->foreignId('checklist_item_id')->nullable()->constrained('checklist_items')->restrictOnDelete();
            $table->string('item_nombre', 100);   // snapshot del nombre del punto
            $table->string('valor', 30);          // si, no, bueno, regular, malo, no_aplica, número o texto
            $table->string('comentario')->nullable();
            $table->timestamps();

            $table->unique(['inspeccion_id', 'checklist_item_id']);
        });

        Schema::create('inspeccion_danios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspeccion_id')->constrained('inspecciones')->cascadeOnDelete();
            $table->foreignId('zona_vehiculo_id')->nullable()->constrained('zonas_vehiculo')->restrictOnDelete();
            $table->string('vista', 15);                     // App\Enums\VistaVehiculo
            $table->decimal('pos_x', 5, 2);                  // 0-100 % del diagrama
            $table->decimal('pos_y', 5, 2);
            $table->string('tipo', 20);                      // App\Enums\TipoDanio
            $table->string('severidad', 10);                 // App\Enums\SeveridadDanio
            $table->unsignedSmallInteger('medida_cm')->nullable();
            $table->string('descripcion')->nullable();
            // Solo en inspección de entrega: el mismo daño registrado al recibir
            $table->foreignId('danio_origen_id')->nullable()->constrained('inspeccion_danios')->nullOnDelete();
            $table->boolean('es_nuevo')->default(false);     // en la entrega: no existía al recibir
            $table->timestamps();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE inspecciones ADD CONSTRAINT chk_inspecciones_combustible CHECK (nivel_combustible IS NULL OR nivel_combustible <= 8)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inspeccion_danios');
        Schema::dropIfExists('inspeccion_respuestas');
        Schema::dropIfExists('inspecciones');
    }
};
