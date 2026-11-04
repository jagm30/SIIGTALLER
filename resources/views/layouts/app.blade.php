<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Taller') · {{ config('app.name') }}</title>
    @include('layouts.estilos')
</head>
<body>
@unless($publico ?? false)
    @auth
        <header class="barra">
            <a href="{{ route('dashboard') }}" class="marca">🔧 {{ config('app.name') }}</a>
            <nav>
                <a href="{{ route('dashboard') }}" @class(['activo' => request()->routeIs('dashboard')])>Inicio</a>
                <a href="{{ route('ordenes.index') }}" @class(['activo' => request()->routeIs('ordenes.*', 'inspecciones.*')])>Órdenes</a>
                @can('clientes.gestionar')
                    <a href="{{ route('vehiculos.index') }}" @class(['activo' => request()->routeIs('vehiculos.*')])>Vehículos</a>
                    <a href="{{ route('clientes.index') }}" @class(['activo' => request()->routeIs('clientes.*')])>Clientes</a>
                @endcan
                @can('catalogos.gestionar')
                    <a href="{{ route('servicios.index') }}" @class(['activo' => request()->routeIs('servicios.*', 'refacciones.*', 'marcas.*')])>Catálogos</a>
                @endcan
                @can('usuarios.gestionar')
                    <a href="{{ route('usuarios.index') }}" @class(['activo' => request()->routeIs('usuarios.*', 'preferencias.*')])>Administración</a>
                @endcan
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="salir">
                @csrf
                <span class="tenue">{{ auth()->user()->name }} · {{ auth()->user()->rol->label() }}</span>
                <button class="boton chico secundario">Salir</button>
            </form>
        </header>
    @endauth
@endunless

<main class="contenido">
    {{-- Mensajes después de guardar --}}
    @if (session('ok'))
        <div class="aviso exito">{{ session('ok') }}</div>
    @endif
    @if (session('aviso'))
        <div class="aviso alerta">{{ session('aviso') }}</div>
    @endif
    @if (session('error'))
        <div class="aviso error">{{ session('error') }}</div>
    @endif

    {{-- Errores de validación y de reglas del negocio.
         Los faltantes de una inspección (clave "inspeccion") los muestra su propia pantalla. --}}
    @php($mensajes = collect($errors->messages())->except('inspeccion')->flatten())
    @if ($mensajes->isNotEmpty())
        <div class="aviso error">
            <strong>Revisa lo siguiente:</strong>
            <ul>
                @foreach ($mensajes as $mensaje)
                    <li>{{ $mensaje }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('contenido')
</main>

@stack('scripts')
</body>
</html>
