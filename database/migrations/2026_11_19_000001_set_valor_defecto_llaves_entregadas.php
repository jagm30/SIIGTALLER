<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('checklist_items')
            ->where('nombre', 'Llaves entregadas (cantidad)')
            ->update(['valor_defecto' => '1']);
    }

    public function down(): void
    {
        DB::table('checklist_items')
            ->where('nombre', 'Llaves entregadas (cantidad)')
            ->update(['valor_defecto' => null]);
    }
};
