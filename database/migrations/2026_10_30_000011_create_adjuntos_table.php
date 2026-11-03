<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fotos, firmas y documentos de inspecciones, daños y órdenes (polimórfica).
        Schema::create('adjuntos', function (Blueprint $table) {
            $table->id();
            $table->morphs('adjuntable');
            $table->string('tipo', 20);  // App\Enums\TipoAdjunto
            $table->foreignId('angulo_foto_id')->nullable()->constrained('angulos_foto')->restrictOnDelete();
            $table->string('disk', 20)->default('local');
            $table->string('ruta');
            $table->string('mime', 80);
            $table->unsignedInteger('tamano_bytes');
            $table->char('hash_sha256', 64); // prueba de que la evidencia no se alteró
            $table->dateTime('tomada_at')->nullable();
            $table->string('descripcion')->nullable();
            $table->foreignId('subido_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjuntos');
    }
};
