@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>Vehículos en el taller</h1>
            <p class="tenue">{{ $ordenes->count() }} {{ $ordenes->count() === 1 ? 'orden abierta' : 'órdenes abiertas' }}</p>
        </div>
        @can('recepcion.realizar')
            <a href="{{ route('ordenes.create') }}" class="boton">+ Recibir vehículo</a>
        @endcan
    </div>

    <div class="rejilla" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));">
        @foreach ($porEstado as $estado => $total)
            <div class="tarjeta contador"><strong>{{ $total }}</strong><span class="tenue chico">{{ $estado }}</span></div>
        @endforeach
    </div>

    <div class="tarjeta">
        <h2>Órdenes abiertas</h2>
        @if ($ordenes->isEmpty())
            <p class="tenue">No hay vehículos en el taller.</p>
        @else
            <div class="tabla-scroll">
                <table>
                    <thead><tr><th>Folio</th><th>Vehículo</th><th class="ocultar-movil">Cliente</th><th>Estado</th><th class="ocultar-movil">Mecánico</th><th class="ocultar-movil">Ingreso</th></tr></thead>
                    <tbody>
                    @foreach ($ordenes as $orden)
                        <tr>
                            <td><a href="{{ route('ordenes.show', $orden) }}">{{ $orden->folio }}</a></td>
                            <td>{{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo->nombre }}<br><span class="tenue chico">{{ $orden->vehiculo->placas ?? 'Sin placas' }}</span></td>
                            <td class="ocultar-movil">{{ $orden->cliente->nombre_completo }}</td>
                            <td><span class="etiqueta {{ $orden->estado->value }}">{{ $orden->estado->label() }}</span></td>
                            <td class="ocultar-movil">{{ $orden->mecanicoResponsable?->name ?? '—' }}</td>
                            <td class="ocultar-movil">{{ $orden->fecha_ingreso->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($recordatorios->isNotEmpty())
        <div class="tarjeta">
            <h2>Servicios por vencer (próximos 15 días)</h2>
            <table>
                <thead><tr><th>Fecha</th><th>Vehículo</th><th>Servicio</th><th class="ocultar-movil">Cliente</th></tr></thead>
                <tbody>
                @foreach ($recordatorios as $r)
                    <tr>
                        <td>{{ $r->fecha_programada->format('d/m/Y') }}</td>
                        <td><a href="{{ route('vehiculos.show', $r->vehiculo) }}">{{ $r->vehiculo->modelo->nombre }} · {{ $r->vehiculo->placas }}</a></td>
                        <td>{{ $r->descripcion }}</td>
                        <td class="ocultar-movil">{{ $r->vehiculo->cliente->nombre_completo }} · {{ $r->vehiculo->cliente->telefono }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
