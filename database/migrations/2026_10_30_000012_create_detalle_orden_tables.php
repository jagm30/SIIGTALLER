<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Trabajos realizados en la orden. Modelo: App\Models\OrdenServicioItem.
        Schema::create('orden_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->restrictOnDelete();
            $table->string('descripcion', 200);                 // snapshot o texto libre
            $table->string('tipo_cobro', 10)->default('fijo');  // snapshot
            $table->decimal('cantidad', 10, 3)->default(1);     // 1 en fijo; horas en por_hora
            $table->decimal('precio_unitario', 12, 2)->nullable();
            $table->decimal('descuento', 12, 2)->nullable();
            $table->decimal('importe', 12, 2)->nullable();
            $table->string('estado', 20)->default('pendiente'); // App\Enums\EstadoTrabajo
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        // Quién hizo cada trabajo y cuántas horas.
        Schema::create('orden_servicio_mecanico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_item_id')->constrained('orden_servicios')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('horas', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['orden_servicio_item_id', 'user_id']);
        });

        // Piezas usadas en la orden.
        Schema::create('orden_refacciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('orden_servicio_item_id')->nullable()->constrained('orden_servicios')->nullOnDelete();
            $table->foreignId('refaccion_id')->nullable()->constrained('refacciones')->restrictOnDelete();
            $table->string('numero_parte', 50)->nullable();     // snapshot
            $table->string('descripcion', 200);                 // snapshot
            $table->decimal('cantidad', 10, 3);
            $table->decimal('costo_unitario', 12, 2)->nullable();
            $table->decimal('precio_unitario', 12, 2)->nullable();
            $table->decimal('importe', 12, 2)->nullable();
            $table->boolean('proporcionada_por_cliente')->default(false);
            $table->unsignedSmallInteger('garantia_dias')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_refacciones');
        Schema::dropIfExists('orden_servicio_mecanico');
        Schema::dropIfExists('orden_servicios');
    }
};
