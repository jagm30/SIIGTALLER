@extends('layouts.app')

@section('titulo', 'Recibir vehículo')

@section('contenido')
    <div class="encabezado">
        <h1>Recibir vehículo</h1>
        <a href="{{ route('ordenes.index') }}">← Órdenes</a>
    </div>

    @if (! $vehiculo)
        {{-- Paso 1: encontrar el vehículo --}}
        <form method="GET" class="tarjeta">
            <h2>1. Busca el vehículo</h2>
            <div class="fila">
                <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Placas o número de serie" style="flex: 1;" autofocus>
                <button class="boton">Buscar</button>
            </div>
        </form>

        @if ($buscar !== '')
            <div class="tarjeta">
                @forelse ($encontrados as $v)
                    <p class="fila" style="justify-content: space-between;">
                        <span><strong>{{ $v->marca->nombre }} {{ $v->modelo->nombre }} {{ $v->anio }}</strong> · {{ $v->placas ?? 'Sin placas' }}<br>
                            <span class="tenue chico">{{ $v->cliente->nombre_completo }} · {{ $v->cliente->telefono }}</span></span>
                        <a href="{{ route('ordenes.create', ['vehiculo_id' => $v->id]) }}" class="boton chico">Elegir</a>
                    </p>
                @empty
                    <p>No encontramos ese vehículo.</p>
                @endforelse
                <p class="tenue chico">¿Es un vehículo nuevo? <a href="{{ route('clientes.create') }}">Registra al cliente</a> o <a href="{{ route('vehiculos.create') }}">registra el vehículo</a> de un cliente existente.</p>
            </div>
        @endif
    @elseif ($vehiculo->ordenAbierta)
        <div class="aviso alerta">
            Este vehículo ya está en el taller con la orden
            <a href="{{ route('ordenes.show', $vehiculo->ordenAbierta) }}">{{ $vehiculo->ordenAbierta->folio }}</a>.
        </div>
    @else
        {{-- Paso 2: abrir la orden --}}
        <form method="POST" action="{{ route('ordenes.store') }}" class="tarjeta">
            @csrf
            <input type="hidden" name="vehiculo_id" value="{{ $vehiculo->id }}">
            <h2>2. Motivo de ingreso</h2>
            <p><strong>{{ $vehiculo->marca->nombre }} {{ $vehiculo->modelo->nombre }} {{ $vehiculo->anio }}</strong> · {{ $vehiculo->placas ?? 'Sin placas' }}<br>
                <span class="tenue">{{ $vehiculo->cliente->nombre_completo }} · {{ $vehiculo->cliente->telefono }}</span>
                · <a href="{{ route('ordenes.create') }}" class="chico">cambiar</a></p>

            <div class="campo">
                <label for="motivo_ingreso">¿Qué reporta el cliente? <span class="tenue">(con sus palabras)</span></label>
                <textarea id="motivo_ingreso" name="motivo_ingreso" required>{{ old('motivo_ingreso') }}</textarea>
            </div>
            <div class="rejilla">
                <div class="campo">
                    <label for="tipo">Tipo de orden</label>
                    <select id="tipo" name="tipo">
                        @foreach (\App\Enums\TipoOrden::opciones() as $valor => $texto)
                            <option value="{{ $valor }}" @selected(old('tipo', 'servicio') === $valor)>{{ $texto }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="campo">
                    <label for="orden_garantia_de_id">Orden original <span class="tenue">(solo garantía)</span></label>
                    <select id="orden_garantia_de_id" name="orden_garantia_de_id">
                        <option value="">—</option>
                        @foreach ($anteriores as $anterior)
                            <option value="{{ $anterior->id }}" @selected((int) old('orden_garantia_de_id') === $anterior->id)>{{ $anterior->folio }} · {{ $anterior->fecha_ingreso->format('d/m/Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="campo">
                    <label for="fecha_promesa">Fecha promesa de entrega</label>
                    <input type="date" id="fecha_promesa" name="fecha_promesa" value="{{ old('fecha_promesa') }}">
                </div>
            </div>
            <button class="boton">Abrir orden y capturar recepción →</button>
        </form>
    @endif
@endsection
