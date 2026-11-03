{{-- Pestañas de las secciones de administración. Uso: @include('layouts._subnav', ['seccion' => 'catalogos']) --}}
<div class="fila" style="margin-bottom: 1rem;">
    @if ($seccion === 'catalogos')
        <a href="{{ route('servicios.index') }}" @class(['boton', 'chico', 'secundario' => ! request()->routeIs('servicios.*')])>Servicios</a>
        <a href="{{ route('refacciones.index') }}" @class(['boton', 'chico', 'secundario' => ! request()->routeIs('refacciones.*')])>Refacciones</a>
        <a href="{{ route('marcas.index') }}" @class(['boton', 'chico', 'secundario' => ! request()->routeIs('marcas.*')])>Marcas y modelos</a>
    @else
        <a href="{{ route('usuarios.index') }}" @class(['boton', 'chico', 'secundario' => ! request()->routeIs('usuarios.*')])>Usuarios</a>
        <a href="{{ route('preferencias.edit') }}" @class(['boton', 'chico', 'secundario' => ! request()->routeIs('preferencias.*')])>Preferencias</a>
    @endif
</div>
