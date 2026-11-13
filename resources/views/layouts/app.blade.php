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

{{-- Lightbox compartido (lo activa JS cuando se hace clic en .foto-lightbox) --}}
<div id="lightbox" hidden role="dialog" aria-modal="true" aria-label="Visor de fotos">
    <button id="lb-cerrar" aria-label="Cerrar">✕</button>
    <button id="lb-prev" class="lb-nav" aria-label="Foto anterior">‹</button>
    <img id="lb-img" src="" alt="">
    <button id="lb-next" class="lb-nav" aria-label="Foto siguiente">›</button>
    <div id="lb-pie"></div>
</div>

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

    {{-- Errores de validación y de reglas del negocio. Una pantalla que los muestra en otro
         lugar (junto a su botón de guardar) define la sección "errores_en_pagina". --}}
    @php($mensajes = collect($errors->messages())->except('inspeccion')->flatten())
    @if ($mensajes->isNotEmpty() && ! View::hasSection('errores_en_pagina'))
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
<script>
(function () {
    const lb      = document.getElementById('lightbox');
    const lbImg   = document.getElementById('lb-img');
    const lbPie   = document.getElementById('lb-pie');
    const lbPrev  = document.getElementById('lb-prev');
    const lbNext  = document.getElementById('lb-next');
    const lbCerrar = document.getElementById('lb-cerrar');
    if (!lb) return;

    let fotos = [];
    let idx   = 0;

    function abrir(grupo, posicion) {
        fotos = Array.from(document.querySelectorAll(`.foto-lightbox[data-grupo="${grupo}"]`));
        idx   = posicion;
        mostrar();
        lb.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        lb.focus();
    }

    function cerrar() {
        lb.setAttribute('hidden', '');
        document.body.style.overflow = '';
        lbImg.src = '';
    }

    function mostrar() {
        const enlace = fotos[idx];
        lbImg.src = enlace.dataset.src;
        lbImg.alt = enlace.dataset.caption;
        lbPie.textContent = enlace.dataset.caption + (fotos.length > 1 ? ` — ${idx + 1} / ${fotos.length}` : '');
        lbPrev.style.display = fotos.length > 1 ? '' : 'none';
        lbNext.style.display = fotos.length > 1 ? '' : 'none';
    }

    function prev() { idx = (idx - 1 + fotos.length) % fotos.length; mostrar(); }
    function next() { idx = (idx + 1) % fotos.length; mostrar(); }

    // Delegación: captura clics en cualquier .foto-lightbox, aunque se agreguen dinámicamente
    document.addEventListener('click', function (e) {
        const enlace = e.target.closest('.foto-lightbox');
        if (!enlace) return;
        e.preventDefault();
        const grupo = enlace.dataset.grupo;
        const todos = Array.from(document.querySelectorAll(`.foto-lightbox[data-grupo="${grupo}"]`));
        abrir(grupo, todos.indexOf(enlace));
    });

    lbCerrar.addEventListener('click', cerrar);
    lbPrev.addEventListener('click', prev);
    lbNext.addEventListener('click', next);

    // Cierra al hacer clic en el fondo (no en la imagen)
    lb.addEventListener('click', function (e) {
        if (e.target === lb) cerrar();
    });

    // Swipe en móvil
    let touchX = null;
    lb.addEventListener('touchstart', function (e) {
        touchX = e.touches[0].clientX;
    }, { passive: true });
    lb.addEventListener('touchend', function (e) {
        if (touchX === null) return;
        const dx = e.changedTouches[0].clientX - touchX;
        touchX = null;
        if (Math.abs(dx) < 40) return;
        dx < 0 ? next() : prev();
    }, { passive: true });

    document.addEventListener('keydown', function (e) {
        if (lb.hasAttribute('hidden')) return;
        if (e.key === 'Escape')      cerrar();
        if (e.key === 'ArrowLeft')   prev();
        if (e.key === 'ArrowRight')  next();
    });
})();
</script>
</body>
</html>
