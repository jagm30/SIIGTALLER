<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mano de obra. Precios con IVA incluido; NULL cuando el taller opera sin precios.
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 20)->nullable()->unique();
            $table->string('nombre', 120);
            $table->text('descripcion')->nullable();
            $table->string('categoria', 40)->nullable();
            $table->string('tipo_cobro', 10)->default('fijo');     // App\Enums\TipoCobro
            $table->decimal('precio', 12, 2)->nullable();          // precio fijo o tarifa por hora
            $table->unsignedSmallInteger('tiempo_estimado_min')->nullable();
            $table->unsignedTinyInteger('intervalo_meses')->nullable(); // próximo servicio
            $table->unsignedInteger('intervalo_km')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Piezas y consumibles.
        Schema::create('refacciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero_parte', 50)->nullable()->index();
            $table->string('descripcion', 200);
            $table->string('marca', 60)->nullable();
            $table->string('categoria', 40)->nullable();
            $table->string('unidad', 10)->default('pieza');        // App\Enums\UnidadRefaccion
            $table->decimal('costo', 12, 2)->nullable();           // solo visible con permiso costos.ver
            $table->decimal('precio_venta', 12, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['numero_parte', 'marca']);
        });

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            Schema::table('refacciones', function (Blueprint $table) {
                $table->fullText('descripcion');
            });
        }

        // Refacciones que se sugieren al agregar un servicio a la orden.
        Schema::create('servicio_refacciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->foreignId('refaccion_id')->constrained('refacciones')->cascadeOnDelete();
            $table->decimal('cantidad', 10, 3)->default(1);
            $table->timestamps();

            $table->unique(['servicio_id', 'refaccion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_refacciones');
        Schema::dropIfExists('refacciones');
        Schema::dropIfExists('servicios');
    }
};
