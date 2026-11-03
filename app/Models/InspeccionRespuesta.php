<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspeccionRespuesta extends Model
{
    protected $table = 'inspeccion_respuestas';

    protected $fillable = [
        'inspeccion_id',
        'checklist_item_id',
        'item_nombre',
        'valor',
        'comentario',
    ];

    protected static function booted(): void
    {
        // Guarda el nombre del punto al momento, por si después se renombra en el catálogo.
        static::creating(function (InspeccionRespuesta $r) {
            if (blank($r->item_nombre) && $r->checklist_item_id) {
                $r->item_nombre = ChecklistItem::withTrashed()->find($r->checklist_item_id)?->nombre;
            }
        });
    }

    public function inspeccion(): BelongsTo
    {
        return $this->belongsTo(Inspeccion::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class)->withTrashed();
    }
}
