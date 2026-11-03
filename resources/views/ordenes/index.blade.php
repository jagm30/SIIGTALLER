@extends('layouts.app')

@section('titulo', 'Órdenes')

@section('contenido')
    <div class="encabezado">
        <h1>Órdenes de servicio</h1>
        @can('recepcion.realizar')
            <a href="{{ route('ordenes.create') }}" class="boton">+ Recibir vehículo</a>
        @endcan
    </div>

    <form method="GET" class="tarjeta fila">
        <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Folio o placas" style="flex: 1; min-width: 180px;">
        <select name="estado" style="width: auto;">
            <option value="">Todos los estados</option>
            @foreach (\App\Enums\EstadoOrden::cases() as $e)
                <option value="{{ $e->value }}" @selected($estado === $e)>{{ $e->label() }}</option>
            @endforeach
        </select>
        <button class="boton secundario">Filtrar</button>
    </form>

    <div class="tarjeta tabla-scroll">
        <table>
            <thead><tr><th>Folio</th><th>Ingreso</th><th>Vehículo</th><th class="ocultar-movil">Cliente</th><th>Estado</th><th class="ocultar-movil">Mecánico</th></tr></thead>
            <tbody>
            @forelse ($ordenes as $orden)
                <tr>
                    <td><a href="{{ route('ordenes.show', $orden) }}">{{ $orden->folio }}</a></td>
                    <td>{{ $orden->fecha_ingreso->format('d/m/Y') }}</td>
                    <td>{{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo->nombre }}<br><span class="tenue chico">{{ $orden->vehiculo->placas ?? 'Sin placas' }}</span></td>
                    <td class="ocultar-movil">{{ $orden->cliente->nombre_completo }}</td>
                    <td><span class="etiqueta {{ $orden->estado->value }}">{{ $orden->estado->label() }}</span></td>
                    <td class="ocultar-movil">{{ $orden->mecanicoResponsable?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="tenue">No hay órdenes con esos filtros.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="paginacion">{{ $ordenes->links('pagination::default') }}</div>
    </div>
@endsection
