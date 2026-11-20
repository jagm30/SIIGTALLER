<?php

namespace App\Providers;

use App\Enums\Rol;
use App\Models\Inspeccion;
use App\Models\InspeccionDanio;
use App\Models\OrdenServicio;
use App\Models\PreferenciaOperacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Alias cortos para la columna adjuntable_type de la tabla adjuntos.
        // Nunca renombres un alias que ya esté en uso.
        Relation::enforceMorphMap([
            'inspeccion' => Inspeccion::class,
            'danio' => InspeccionDanio::class,
            'orden' => OrdenServicio::class,
            'user' => User::class,
        ]);

        // Cada permiso de App\Enums\Rol se vuelve un Gate de Laravel.
        // Así funcionan @can('clientes.gestionar') en Blade y ->middleware('can:clientes.gestionar') en rutas.
        foreach (Rol::PERMISOS as $permiso) {
            Gate::define($permiso, fn (User $user) => $user->tienePermiso($permiso));
        }

        // Los precios dependen además de la preferencia usar_precios y del modo de la orden.
        Gate::define('precios.ver', fn (User $user, ?OrdenServicio $orden = null) => $user->puedeVerPrecios($orden));
        Gate::define('costos.ver', fn (User $user) => $user->puedeVerCostos());

        // Recepción y administrador ven todas las órdenes; el mecánico solo las que tiene asignadas.
        Gate::define('ver-orden', function (User $user, OrdenServicio $orden) {
            return $user->tienePermiso('ordenes.ver_todas')
                || OrdenServicio::asignadasA($user)->whereKey($orden->id)->exists();
        });

        // Nombre y logo del taller (se editan en Preferencias) para las vistas que los muestran.
        View::composer(['layouts.app', 'auth.login', 'qr.show', 'qr.imprimir', 'preferencias.edit'], fn ($view) => $view->with([
            'nombreTaller' => PreferenciaOperacion::nombreTaller(),
            'logoTaller' => PreferenciaOperacion::urlLogoTaller(),
        ]));
        View::composer(['inspecciones.pdf', 'inspecciones.pdf_entrega'], fn ($view) => $view->with([
            'nombreTaller' => PreferenciaOperacion::nombreTaller(),
            'logoTaller' => PreferenciaOperacion::logoTallerDataUri(),
        ]));
    }
}
