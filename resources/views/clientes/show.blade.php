@extends('layouts.app')

@section('titulo', $cliente->nombre_completo)

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>{{ $cliente->nombre_completo }}</h1>
            <p class="tenue">{{ $cliente->tipo_persona->label() }} · {{ $cliente->telefono }} @if ($cliente->email) · {{ $cliente->email }} @endif</p>
        </div>
        <div class="fila">
            <a href="{{ route('vehiculos.create', ['cliente_id' => $cliente->id]) }}" class="boton">+ Vehículo</a>
            <a href="{{ route('clientes.edit', $cliente) }}" class="boton secundario">Editar</a>
        </div>
    </div>

    <div class="rejilla-2">
        <div class="tarjeta">
            <h2>Vehículos</h2>
            @forelse ($cliente->vehiculos as $v)
                <p><a href="{{ route('vehiculos.show', $v) }}">{{ $v->marca->nombre }} {{ $v->modelo->nombre }} {{ $v->anio }}</a>
                    <span class="tenue">· {{ $v->placas ?? 'Sin placas' }}</span></p>
            @empty
                <p class="tenue">Sin vehículos registrados.</p>
            @endforelse
        </div>

        <div class="tarjeta">
            <h2>Datos</h2>
            <p><strong>RFC:</strong> {{ $cliente->rfc ?? '—' }}<br>
               <strong>Teléfono alterno:</strong> {{ $cliente->telefono_alt ?? '—' }}<br>
               <strong>Dirección:</strong> {{ $cliente->direccion ?? '—' }}</p>
            @if ($cliente->notas)<p class="tenue">{{ $cliente->notas }}</p>@endif
        </div>
    </div>

    <div class="tarjeta">
        <h2>Últimas órdenes</h2>
        <table>
            <thead><tr><th>Folio</th><th>Fecha</th><th>Vehículo</th><th>Estado</th></tr></thead>
            <tbody>
            @forelse ($ordenes as $orden)
                <tr>
                    <td><a href="{{ route('ordenes.show', $orden) }}">{{ $orden->folio }}</a></td>
                    <td>{{ $orden->fecha_ingreso->format('d/m/Y') }}</td>
                    <td>{{ $orden->vehiculo->modelo->nombre }}</td>
                    <td><span class="etiqueta {{ $orden->estado->value }}">{{ $orden->estado->label() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" class="tenue">Sin órdenes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($cliente->vehiculos->isEmpty() && $ordenes->isEmpty())
        <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" onsubmit="return confirm('¿Eliminar este cliente?')">
            @csrf @method('DELETE')
            <button class="boton-link">Eliminar cliente</button>
        </form>
    @endif
@endsection
