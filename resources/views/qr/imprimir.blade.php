@extends('layouts.app')

@section('titulo', 'QR ' . $orden->folio)

{{-- Hoja que se imprime y se entrega al cliente. Sin datos personales: cualquiera puede escanearla. --}}
@section('contenido')
<style>
/* ── Pantalla: barra de acciones ───────────────────────────────── */
.qr-imprimir-barra {
    max-width: 480px; margin: 0 auto 1.5rem;
    display: flex; flex-wrap: wrap; align-items: center;
    justify-content: space-between; gap: .75rem;
}
.qr-imprimir-barra h1 {
    font-size: 1.1rem; font-weight: 700; margin: 0;
}
.qr-imprimir-barra p {
    font-size: .875rem; color: var(--tenue); margin: .15rem 0 0;
}
.qr-imprimir-acciones {
    display: flex; flex-wrap: wrap; gap: .5rem;
}

/* ── Tarjeta imprimible ────────────────────────────────────────── */
.qr-ticket {
    max-width: 340px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 1.75rem 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,.09), 0 8px 32px rgba(0,0,0,.06);
    text-align: center;
    position: relative;
    overflow: hidden;
}

/* Acento de color en la parte superior */
.qr-ticket::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 5px;
    background: linear-gradient(90deg, #12305a, #1f5fbf, #1e7a46);
    border-radius: 16px 16px 0 0;
}

.qr-ticket-marca {
    font-size: .72rem; font-weight: 800; letter-spacing: .12em;
    text-transform: uppercase; color: var(--tenue); margin: 0 0 1.1rem;
}

.qr-ticket-qr {
    display: inline-block;
    padding: .6rem;
    background: #fff;
    border: 1.5px solid #e4e7eb;
    border-radius: 10px;
    margin-bottom: 1.1rem;
}
.qr-ticket-qr svg { width: 200px; height: 200px; display: block; }

.qr-ticket-vehiculo {
    font-size: 1.1rem; font-weight: 800;
    color: var(--texto); margin: 0 0 .2rem;
    line-height: 1.25;
}

.qr-ticket-meta {
    font-size: .82rem; color: var(--tenue);
    margin: 0 0 1.1rem;
    display: flex; flex-wrap: wrap; justify-content: center; gap: .2rem .6rem;
}
.qr-ticket-meta span + span::before { content: '·'; margin-right: .6rem; opacity: .5; }

.qr-ticket-instruccion {
    font-size: .875rem; color: #34414f; line-height: 1.5;
    background: #f4f5f7; border-radius: 8px;
    padding: .7rem .9rem; margin-bottom: 1rem;
}

.qr-ticket-url {
    font-size: .7rem; color: var(--tenue);
    overflow-wrap: anywhere; word-break: break-all;
    text-decoration: none; display: block;
    border-top: 1px dashed #e4e7eb; padding-top: .75rem; margin-top: .25rem;
}

/* ── Print ─────────────────────────────────────────────────────── */
@media print {
    .qr-imprimir-barra { display: none !important; }

    body { background: #fff; }
    .contenido { padding: 0; max-width: 100%; }

    .qr-ticket {
        max-width: 100%;
        border-radius: 0; padding: 2cm 2.5cm;
        box-shadow: none;
    }
    .qr-ticket::before { display: none; }
    .qr-ticket-qr svg { width: 220px; height: 220px; }
    .qr-ticket-url { color: #000; }
}
</style>

{{-- Barra de acciones (solo en pantalla) --}}
<div class="qr-imprimir-barra no-imprimir">
    <div>
        <h1>QR para el cliente</h1>
        <p>Orden {{ $orden->folio }} · {{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo->nombre }}</p>
    </div>
    <div class="qr-imprimir-acciones">
        <button type="button" class="boton" onclick="window.print()">Imprimir</button>
        @if ($orden->recepcion?->estaCompletada())
            <a href="{{ route('inspecciones.pdf', $orden->recepcion) }}" class="boton secundario" target="_blank">Acta PDF</a>
        @endif
        <a href="{{ route('ordenes.show', $orden) }}" class="boton secundario">← Orden</a>
    </div>
</div>

@if ($vehiculo->qr_activo)
    <div class="qr-ticket">
        <p class="qr-ticket-marca">🔧 {{ config('app.name') }}</p>

        <div class="qr-ticket-qr">{!! $vehiculo->qrSvg() !!}</div>

        <p class="qr-ticket-vehiculo">
            {{ $vehiculo->marca->nombre }} {{ $vehiculo->modelo->nombre }}
            @if ($vehiculo->anio) {{ $vehiculo->anio }}@endif
        </p>
        <div class="qr-ticket-meta">
            <span>{{ $vehiculo->placas ?? 'Sin placas' }}</span>
            <span>Orden {{ $orden->folio }}</span>
            <span>{{ $orden->fecha_ingreso->format('d/m/Y') }}</span>
        </div>

        <p class="qr-ticket-instruccion">
            Escanea este código para consultar el estado de tu vehículo y su historial de servicio.
        </p>

        <a href="{{ $vehiculo->urlPublica() }}#historial" class="qr-ticket-url" target="_blank">
            {{ $vehiculo->urlPublica() }}
        </a>
    </div>
@else
    <div class="aviso alerta" style="max-width:480px; margin:0 auto;">
        El QR de este vehículo está desactivado, no hay nada que imprimir.
    </div>
@endif

@endsection
