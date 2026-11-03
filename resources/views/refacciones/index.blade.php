@extends('layouts.app')

@section('titulo', 'Refacciones')

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'catalogos'])
    <div class="encabezado">
        <h1>Refacciones</h1>
        <a href="{{ route('refacciones.create') }}" class="boton">+ Nueva refacción</a>
    </div>

    <form method="GET" class="tarjeta fila">
        <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Descripción o número de parte" style="flex: 1;">
        <button class="boton secundario">Buscar</button>
    </form>

    <div class="tarjeta tabla-scroll">
        <table>
            <thead><tr><th>Refacción</th><th class="ocultar-movil">Marca</th><th>Unidad</th>
                @can('costos.ver')<th class="derecha ocultar-movil">Costo</th>@endcan
                @can('precios.ver')<th class="derecha">Precio</th>@endcan<th></th></tr></thead>
            <tbody>
            @forelse ($refacciones as $r)
                <tr @if (! $r->activo) class="tenue" @endif>
                    <td>{{ $r->descripcion }} @if (! $r->activo)<span class="etiqueta">inactiva</span>@endif<br><span class="tenue chico">{{ $r->numero_parte }}</span></td>
                    <td class="ocultar-movil">{{ $r->marca ?? '—' }}</td>
                    <td>{{ $r->unidad->label() }}</td>
                    @can('costos.ver')<td class="derecha ocultar-movil">{{ $r->costo !== null ? '$' . number_format($r->costo, 2) : '—' }}</td>@endcan
                    @can('precios.ver')<td class="derecha">{{ $r->precio_venta !== null ? '$' . number_format($r->precio_venta, 2) : '—' }}</td>@endcan
                    <td class="derecha"><a href="{{ route('refacciones.edit', $r) }}">Editar</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="tenue">Sin refacciones.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="paginacion">{{ $refacciones->links('pagination::default') }}</div>
    </div>
@endsection
