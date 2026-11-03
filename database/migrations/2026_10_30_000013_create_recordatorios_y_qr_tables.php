<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordatorios_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->restrictOnDelete();
            $table->foreignId('orden_origen_id')->nullable()->constrained('ordenes_servicio')->nullOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
            $table->string('descripcion', 200);
            $table->date('fecha_programada')->nullable();
            $table->unsignedInteger('km_programado')->nullable();
            $table->string('estado', 20)->default('pendiente'); // App\Enums\EstadoRecordatorio
            $table->foreignId('orden_cumplimiento_id')->nullable()->constrained('ordenes_servicio')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehiculo_id', 'estado']);
            $table->index(['estado', 'fecha_programada']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE recordatorios_servicio ADD CONSTRAINT chk_recordatorio_fecha_o_km CHECK (fecha_programada IS NOT NULL OR km_programado IS NOT NULL)');
        }

        // Escaneos de la página pública del QR (solo created_at).
        Schema::create('qr_consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_consultas');
        Schema::dropIfExists('recordatorios_servicio');
    }
};
