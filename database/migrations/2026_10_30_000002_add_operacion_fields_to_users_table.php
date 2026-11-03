<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extiende la tabla users que trae Laravel 12
 * (0001_01_01_000000_create_users_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // NULL = acceso a todas las sucursales (administrador)
            $table->foreignId('sucursal_id')->nullable()->after('id')
                ->constrained('sucursales')->restrictOnDelete();
            $table->string('rol', 20)->default('recepcionista')->after('email')->index(); // App\Enums\Rol
            $table->string('telefono', 20)->nullable()->after('rol');
            // Los usuarios se desactivan, nunca se borran: tienen historial en las órdenes.
            $table->boolean('activo')->default(true)->after('password');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sucursal_id');
            $table->dropIndex(['rol']);
            $table->dropColumn(['rol', 'telefono', 'activo', 'deleted_at']);
        });
    }
};
