@extends('layouts.app')

@section('titulo', 'Vehículos')

@section('contenido')
    <div class="encabezado">
        <h1>Vehículos</h1>
        <a href="{{ route('vehiculos.create') }}" class="boton">+ Nuevo vehículo</a>
    </div>

    <form method="GET" class="tarjeta fila">
        <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Placas, número de serie o cliente" style="flex: 1;">
        <button class="boton secundario">Buscar</button>
    </form>

    <div class="tarjeta tabla-scroll">
        <table>
            <thead><tr><th>Vehículo</th><th>Placas</th><th class="ocultar-movil">VIN</th><th>Dueño</th><th class="ocultar-movil derecha">Km</th></tr></thead>
            <tbody>
            @forelse ($vehiculos as $v)
                <tr>
                    <td><a href="{{ route('vehiculos.show', $v) }}">{{ $v->marca->nombre }} {{ $v->modelo->nombre }} {{ $v->anio }}</a></td>
                    <td>{{ $v->placas ?? '—' }}</td>
                    <td class="ocultar-movil chico">{{ $v->vin ?? '—' }}</td>
                    <td>{{ $v->cliente->nombre_completo }}</td>
                    <td class="ocultar-movil derecha">{{ number_format($v->km_actual) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="tenue">No se encontraron vehículos.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="paginacion">{{ $vehiculos->links('pagination::default') }}</div>
    </div>
@endsection
