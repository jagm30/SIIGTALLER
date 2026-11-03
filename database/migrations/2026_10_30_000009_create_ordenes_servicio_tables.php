<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->string('folio', 20);
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->string('tipo', 20)->default('servicio');               // App\Enums\TipoOrden
            $table->foreignId('orden_garantia_de_id')->nullable()
                ->constrained('ordenes_servicio')->nullOnDelete();
            $table->string('estado', 30)->default('recibido');             // App\Enums\EstadoOrden
            // Copia de la preferencia usar_precios al crear la orden; nunca cambia después.
            $table->boolean('con_precios');
            $table->foreignId('recibido_por_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('mecanico_responsable_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('entregado_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha_ingreso')->index();
            $table->dateTime('fecha_promesa')->nullable();
            $table->dateTime('fecha_entrega')->nullable();
            $table->text('motivo_ingreso');
            $table->text('diagnostico')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('motivo_cancelacion')->nullable();
            // IVA incluido: subtotal = suma de importes; total = subtotal - descuento;
            // iva = IVA contenido en el total (solo reportes internos). NULL si con_precios = false.
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('descuento', 12, 2)->nullable();
            $table->decimal('iva', 12, 2)->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sucursal_id', 'folio']);
            $table->index(['sucursal_id', 'estado']);
            $table->index(['vehiculo_id', 'fecha_ingreso']);
        });

        // Bitácora inmutable de cambios de estado.
        Schema::create('orden_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('comentario')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_estados');
        Schema::dropIfExists('ordenes_servicio');
    }
};
