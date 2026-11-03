<?php

namespace App\Models;

use App\Enums\TipoPersona;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'tipo_persona',
        'nombre',
        'apellidos',
        'razon_social',
        'rfc',
        'telefono',
        'telefono_alt',
        'email',
        'direccion',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'tipo_persona' => TipoPersona::class,
        ];
    }

    /** Vehículos de los que es dueño actualmente. */
    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenServicio::class);
    }

    /** Historial de propiedad (incluye vehículos que ya vendió). */
    public function propiedades(): HasMany
    {
        return $this->hasMany(VehiculoPropietario::class);
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => $this->tipo_persona === TipoPersona::Moral
            ? ($this->razon_social ?: $this->nombre)
            : trim($this->nombre . ' ' . $this->apellidos));
    }

    protected function rfc(): Attribute
    {
        return Attribute::set(fn (?string $v) => $v ? strtoupper(trim($v)) : null);
    }
}
