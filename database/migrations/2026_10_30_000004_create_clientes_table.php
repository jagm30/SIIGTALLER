<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_persona', 10)->default('fisica'); // App\Enums\TipoPersona
            $table->string('nombre', 150);
            $table->string('apellidos', 150)->nullable();
            $table->string('razon_social')->nullable();
            $table->string('rfc', 13)->nullable()->index(); // no único: el RFC genérico se repite
            $table->string('telefono', 20)->index();
            $table->string('telefono_alt', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->fullText(['nombre', 'apellidos', 'razon_social']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
