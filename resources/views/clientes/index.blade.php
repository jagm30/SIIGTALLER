@extends('layouts.app')

@section('titulo', 'Clientes')

@section('contenido')
    <div class="encabezado">
        <h1>Clientes</h1>
        <a href="{{ route('clientes.create') }}" class="boton">+ Nuevo cliente</a>
    </div>

    <form method="GET" class="tarjeta fila">
        <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Nombre, teléfono o RFC" style="flex: 1;">
        <button class="boton secundario">Buscar</button>
    </form>

    <div class="tarjeta tabla-scroll">
        <table>
            <thead><tr><th>Nombre</th><th>Teléfono</th><th class="ocultar-movil">RFC</th><th>Vehículos</th></tr></thead>
            <tbody>
            @forelse ($clientes as $cliente)
                <tr>
                    <td><a href="{{ route('clientes.show', $cliente) }}">{{ $cliente->nombre_completo }}</a></td>
                    <td>{{ $cliente->telefono }}</td>
                    <td class="ocultar-movil">{{ $cliente->rfc ?? '—' }}</td>
                    <td>{{ $cliente->vehiculos_count }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="tenue">No se encontraron clientes.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="paginacion">{{ $clientes->links('pagination::default') }}</div>
    </div>
@endsection
