@extends('layouts.app')

@section('titulo', 'QR ' . $orden->folio)

{{-- Hoja que se imprime y se entrega al cliente. Sin datos personales: cualquiera puede escanearla. --}}
@section('contenido')
    <div class="encabezado no-imprimir">
        <h1>QR para el cliente · Orden {{ $orden->folio }}</h1>
        <div class="fila">
            <button type="button" class="boton" onclick="window.print()">Imprimir QR</button>
            <a href="{{ route('ordenes.show', $orden) }}" class="boton secundario">Ir a la orden</a>
        </div>
    </div>

    @if ($vehiculo->qr_activo)
        <div class="tarjeta hoja-qr">
            <p class="marca-qr">🔧 {{ config('app.name') }}</p>
            <div class="imagen-qr">{!! $vehiculo->qrSvg() !!}</div>
            <p class="instruccion-qr">Escanea este código para ver el estado de tu vehículo y su historial de servicio.</p>
            <p><strong>{{ $vehiculo->marca->nombre }} {{ $vehiculo->modelo->nombre }} {{ $vehiculo->anio }}</strong><br>
                {{ $vehiculo->placas ?? 'Sin placas' }} · Orden {{ $orden->folio }} · {{ $orden->fecha_ingreso->format('d/m/Y') }}</p>
            <p class="chico tenue">{{ $vehiculo->urlPublica() }}</p>
        </div>
    @else
        <div class="aviso alerta">El QR de este vehículo está desactivado, así que no hay nada que imprimir.</div>
    @endif
@endsection
