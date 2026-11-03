<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Usuarios del sistema. No se borran porque aparecen en el historial de las órdenes:
 * para quitarle el acceso a alguien se desactiva.
 */
class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', ['usuarios' => User::with('sucursal')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('usuarios.create', ['usuario' => new User(['activo' => true]), 'sucursales' => Sucursal::orderBy('nombre')->get()]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        // La contraseña se guarda cifrada automáticamente (cast "hashed" del modelo User).
        User::create($request->safe()->except('password_confirmation') + ['activo' => $request->boolean('activo', true)]);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario creado.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', ['usuario' => $usuario, 'sucursales' => Sucursal::orderBy('nombre')->get()]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->safe()->except(['password', 'password_confirmation']) + ['activo' => $request->boolean('activo')];

        if ($request->filled('password')) {
            $datos['password'] = $request->input('password');
        }

        // Evita que el administrador se quite su propio acceso por accidente.
        if ($usuario->is($request->user()) && (! $datos['activo'] || $datos['rol'] !== $usuario->rol->value)) {
            return back()->withInput()->with('error', 'No puedes desactivarte ni cambiar tu propio rol.');
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado.');
    }
}
