<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'prefijo_folio',
        'ultimo_folio',
        'direccion',
        'telefono',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_folio' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenServicio::class);
    }

    public function preferencias(): HasMany
    {
        return $this->hasMany(PreferenciaOperacion::class);
    }

    /**
     * Siguiente folio consecutivo de la sucursal (ej. MTZ-000123).
     * El bloqueo de fila evita folios duplicados si dos recepcionistas crean órdenes a la vez.
     */
    public static function siguienteFolio(int $sucursalId): string
    {
        return DB::transaction(function () use ($sucursalId) {
            $s = static::query()->lockForUpdate()->findOrFail($sucursalId);
            $s->increment('ultimo_folio');

            return sprintf('%s-%06d', $s->prefijo_folio, $s->ultimo_folio);
        });
    }
}
