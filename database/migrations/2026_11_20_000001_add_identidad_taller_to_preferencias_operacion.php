<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CLAVES = [
        'taller_nombre' => 'Nombre del taller que aparece en la barra, el inicio de sesión, la página del QR y los documentos.',
        'taller_logo' => 'Logo del taller (ruta del archivo guardado).',
    ];

    public function up(): void
    {
        foreach (self::CLAVES as $clave => $descripcion) {
            $existe = DB::table('preferencias_operacion')->whereNull('sucursal_id')->where('clave', $clave)->exists();

            if (! $existe) {
                DB::table('preferencias_operacion')->insert([
                    'sucursal_id' => null,
                    'grupo' => 'taller',
                    'clave' => $clave,
                    'valor' => null,
                    'tipo' => 'string',
                    'descripcion' => $descripcion,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('preferencias_operacion')->whereIn('clave', array_keys(self::CLAVES))->delete();
    }
};
