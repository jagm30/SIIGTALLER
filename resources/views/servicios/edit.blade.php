@extends('layouts.app')

@section('titulo', 'Editar servicio')

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'catalogos'])
    <h1>Editar servicio</h1>
    <form method="POST" action="{{ route('servicios.update', $servicio) }}" class="tarjeta">
        @method('PUT')
        @include('servicios._form')
    </form>

    {{-- Refacciones que se proponen al agregar este servicio a una orden --}}
    <div class="tarjeta">
        <h2>Refacciones que lleva</h2>
        <p class="tenue chico">Al agregar "{{ $servicio->nombre }}" a una orden se proponen estas piezas. El mecánico puede quitarlas o cambiar la cantidad en la orden.</p>

        @if ($servicio->refaccionesSugeridas->isNotEmpty())
            <div class="tabla-scroll">
                <table>
                    <thead><tr><th>Refacción</th><th class="derecha">Cantidad</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($servicio->refaccionesSugeridas as $r)
                        <tr>
                            <td>{{ $r->descripcion }}<br><span class="tenue chico">{{ $r->numero_parte }} {{ $r->marca }}</span></td>
                            <td class="derecha">{{ rtrim(rtrim(number_format($r->pivot->cantidad, 3), '0'), '.') }} {{ $r->unidad?->label() }}</td>
                            <td class="derecha">
                                <form method="POST" action="{{ route('servicios.refacciones.destroy', [$servicio, $r]) }}">
                                    @csrf @method('DELETE')
                                    <button class="boton-link">Quitar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="tenue">Todavía no tiene refacciones.</p>
        @endif

        @if ($refacciones->isNotEmpty())
            <form method="POST" action="{{ route('servicios.refacciones.store', $servicio) }}" class="fila">
                @csrf
                <select name="refaccion_id" required style="flex: 1;">
                    <option value="">Elige una refacción…</option>
                    @foreach ($refacciones as $r)
                        <option value="{{ $r->id }}">{{ $r->descripcion }} {{ $r->numero_parte ? '· ' . $r->numero_parte : '' }}</option>
                    @endforeach
                </select>
                <input type="number" name="cantidad" value="1" min="0.001" step="any" style="width: 6rem;" aria-label="Cantidad">
                <button class="boton secundario">Agregar</button>
            </form>
        @else
            <p class="tenue chico">No hay más refacciones activas. Da de alta la que falte en <a href="{{ route('refacciones.create') }}">Refacciones</a>.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('servicios.destroy', $servicio) }}" onsubmit="return confirm('¿Eliminar este servicio?')">
        @csrf @method('DELETE')
        <button class="boton-link">Eliminar servicio</button>
        <span class="tenue chico">Si ya se usó en órdenes, solo se desactiva.</span>
    </form>
@endsection
