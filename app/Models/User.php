<?php

namespace App\Models;

use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Reemplaza el App\Models\User que trae Laravel.
 * Cada usuario tiene un rol (administrador, recepcionista o mecanico); ver App\Enums\Rol.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'sucursal_id',
        'name',
        'email',
        'rol',
        'telefono',
        'password',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Mismos valores por defecto que la migración, para que un usuario recién creado ya los tenga. */
    protected $attributes = [
        'rol' => 'recepcionista',
        'activo' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
            'activo' => 'boolean',
        ];
    }

    // ── Relaciones ───────────────────────────────────────────────

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function ordenesRecibidas(): HasMany
    {
        return $this->hasMany(OrdenServicio::class, 'recibido_por_id');
    }

    public function ordenesAsignadas(): HasMany
    {
        return $this->hasMany(OrdenServicio::class, 'mecanico_responsable_id');
    }

    /** Trabajos en los que participó como mecánico, con sus horas. */
    public function trabajos(): BelongsToMany
    {
        return $this->belongsToMany(OrdenServicioItem::class, 'orden_servicio_mecanico', 'user_id', 'orden_servicio_item_id')
            ->withPivot('horas')
            ->withTimestamps();
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeConRol(Builder $query, Rol $rol): Builder
    {
        return $query->where('rol', $rol->value);
    }

    // ── Roles y permisos ─────────────────────────────────────────

    public function esAdministrador(): bool
    {
        return $this->rol === Rol::Administrador;
    }

    public function esRecepcionista(): bool
    {
        return $this->rol === Rol::Recepcionista;
    }

    public function esMecanico(): bool
    {
        return $this->rol === Rol::Mecanico;
    }

    /** ¿Tiene alguno de estos roles? */
    public function tieneRol(Rol ...$roles): bool
    {
        return $this->rol !== null && in_array($this->rol, $roles, true);
    }

    /** ¿Su rol tiene este permiso? (usuarios desactivados no pueden nada) */
    public function tienePermiso(string $permiso): bool
    {
        return $this->activo && $this->rol?->puede($permiso) === true;
    }

    // ── Reglas de visibilidad de dinero ─────────────────────────

    /**
     * ¿Puede ver importes? Combina la preferencia del taller, el modo de la orden y el rol.
     * Si el administrador bloquea usar_precios, nadie ve precios.
     */
    public function puedeVerPrecios(?OrdenServicio $orden = null): bool
    {
        if ($orden !== null && ! $orden->con_precios) {
            return false;
        }

        if (! PreferenciaOperacion::bool('usar_precios', $this->sucursal_id, true)) {
            return false;
        }

        if ($this->esMecanico()) {
            return PreferenciaOperacion::bool('mostrar_precios_a_mecanicos', $this->sucursal_id);
        }

        return $this->tienePermiso('precios.ver');
    }

    /** Costo de compra y utilidad. */
    public function puedeVerCostos(): bool
    {
        return PreferenciaOperacion::bool('usar_precios', $this->sucursal_id, true)
            && $this->tienePermiso('costos.ver');
    }
}
