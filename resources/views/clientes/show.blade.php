@extends('layouts.app')

@section('titulo', $cliente->nombre_completo)

@push('styles')
<style>
    /* ── Perfil de cliente ─────────────────────────────────────────────── */
    .cl-hero {
        background: var(--tarjeta);
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .cl-hero-acento {
        height: 4px;
        background: linear-gradient(90deg, var(--primario) 0%, #5b9cf6 100%);
    }
    .cl-hero-cuerpo {
        display: flex;
        align-items: flex-start;
        gap: 1.1rem;
        padding: 1.2rem 1.2rem .8rem;
    }
    .cl-avatar {
        flex-shrink: 0;
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primario) 0%, #3b82e0 100%);
        color: #fff;
        font-size: 1.55rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        letter-spacing: -.02em;
        user-select: none;
    }
    .cl-hero-info { flex: 1; min-width: 0; }
    .cl-hero-info h1 { font-size: 1.4rem; margin: 0 0 .2rem; line-height: 1.2; }
    .cl-tipo-badge {
        display: inline-block;
        padding: .15rem .55rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 600;
        background: #e8f0fb;
        color: var(--primario);
        margin-bottom: .45rem;
    }
    .cl-contacto {
        display: flex;
        flex-wrap: wrap;
        gap: .3rem .8rem;
    }
    .cl-chip {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .875rem;
        color: var(--tenue);
    }
    .cl-hero-acciones {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        padding: 0 1.2rem .9rem;
        justify-content: flex-end;
    }

    /* Stats strip */
    .cl-stats {
        display: flex;
        border-top: 1px solid var(--borde);
        background: #fafbfc;
    }
    .cl-stat {
        flex: 1;
        padding: .65rem 1.1rem;
    }
    .cl-stat + .cl-stat { border-left: 1px solid var(--borde); }
    .cl-stat-num {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--primario);
        line-height: 1.1;
    }
    .cl-stat-label {
        font-size: .78rem;
        color: var(--tenue);
        margin-top: .05rem;
    }

    /* Tarjetas de vehículos */
    .cl-vehiculos-lista { display: grid; gap: .55rem; }
    .cl-veh {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .65rem .8rem;
        border: 1px solid var(--borde);
        border-radius: 7px;
        text-decoration: none;
        color: var(--texto);
        background: #fafbfc;
        transition: border-color .15s, background .15s, box-shadow .15s;
    }
    .cl-veh:hover {
        border-color: var(--primario);
        background: #f0f6ff;
        box-shadow: 0 2px 8px rgba(31,95,191,.09);
    }
    .cl-veh-icono { font-size: 1.3rem; flex-shrink: 0; }
    .cl-veh-info  { flex: 1; min-width: 0; }
    .cl-veh-nombre {
        font-weight: 600;
        font-size: .93rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cl-veh-sub {
        font-size: .8rem;
        color: var(--tenue);
        margin-top: .05rem;
    }
    .cl-veh-flecha { color: #b0b8c4; font-size: 1rem; flex-shrink: 0; }

    /* Datos del cliente: lista definición */
    .cl-datos-dl { margin: 0; }
    .cl-dato {
        display: grid;
        grid-template-columns: 136px 1fr;
        gap: .2rem 1rem;
        padding: .45rem 0;
        border-bottom: 1px solid #f0f1f3;
        font-size: .9rem;
        align-items: baseline;
    }
    .cl-dato:last-of-type { border-bottom: 0; }
    .cl-dato dt {
        font-size: .78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: var(--tenue);
        line-height: 1.5;
    }
    .cl-dato dd { margin: 0; line-height: 1.5; word-break: break-word; }
    .cl-notas {
        margin-top: .8rem;
        padding: .55rem .75rem;
        background: #fffbea;
        border: 1px solid #f0d48a;
        border-radius: 7px;
        font-size: .875rem;
        color: #6b4600;
        line-height: 1.45;
    }

    /* Tabla de órdenes */
    .cl-ordenes-tabla td { vertical-align: middle; }
    .cl-folio {
        font-family: ui-monospace, "Cascadia Code", Consolas, monospace;
        font-size: .9rem;
        font-weight: 600;
        color: var(--primario);
        white-space: nowrap;
        text-decoration: none;
    }
    .cl-folio:hover { text-decoration: underline; }
    .cl-fecha { font-size: .875rem; color: var(--tenue); white-space: nowrap; }
    .cl-modelo { font-size: .9rem; }

    /* Estado vacío */
    .cl-empty {
        text-align: center;
        padding: 1.75rem 1rem;
        color: var(--tenue);
        font-size: .9rem;
    }
    .cl-empty-icon {
        font-size: 2rem;
        display: block;
        margin-bottom: .4rem;
        opacity: .35;
    }

    /* Eliminar cliente */
    .cl-zona-eliminar {
        margin-top: .25rem;
        text-align: right;
    }

    /* ── Responsive ──────────────────────────────────────────────────── */
    @media (max-width: 600px) {
        .cl-hero-cuerpo { gap: .8rem; }
        .cl-avatar { width: 48px; height: 48px; font-size: 1.25rem; }
        .cl-hero-info h1 { font-size: 1.2rem; }
        .cl-hero-acciones { justify-content: flex-start; padding-top: 0; }
    }
    @media (max-width: 480px) {
        .cl-stats { flex-direction: column; }
        .cl-stat + .cl-stat { border-left: 0; border-top: 1px solid var(--borde); }
        .cl-dato { grid-template-columns: 1fr; gap: .05rem; }
        .cl-dato dt { margin-top: .3rem; }
    }
</style>
@endpush

@section('contenido')

@php
    $inicial       = mb_strtoupper(mb_substr($cliente->nombre_completo, 0, 1));
    $totalVehiculos = $cliente->vehiculos->count();
    $totalOrdenes  = $ordenes->count();
    $ultimaOrden   = $ordenes->first();
@endphp

{{-- ── Perfil ─────────────────────────────────────────────────────────── --}}
<div class="cl-hero">
    <div class="cl-hero-acento"></div>

    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start;">
        <div class="cl-hero-cuerpo" style="flex:1; min-width:0;">
            <div class="cl-avatar">{{ $inicial }}</div>
            <div class="cl-hero-info">
                <h1>{{ $cliente->nombre_completo }}</h1>
                <span class="cl-tipo-badge">{{ $cliente->tipo_persona->label() }}</span>
                <div class="cl-contacto">
                    <span class="cl-chip">📞 {{ $cliente->telefono }}</span>
                    @if ($cliente->email)
                        <span class="cl-chip">✉ {{ $cliente->email }}</span>
                    @endif
                    @if ($cliente->rfc)
                        <span class="cl-chip">RFC: {{ $cliente->rfc }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="cl-hero-acciones">
            <a href="{{ route('vehiculos.create', ['cliente_id' => $cliente->id]) }}" class="boton">+ Vehículo</a>
            <a href="{{ route('clientes.edit', $cliente) }}" class="boton secundario">Editar</a>
        </div>
    </div>

    <div class="cl-stats">
        <div class="cl-stat">
            <div class="cl-stat-num">{{ $totalVehiculos }}</div>
            <div class="cl-stat-label">{{ $totalVehiculos === 1 ? 'Vehículo' : 'Vehículos' }}</div>
        </div>
        <div class="cl-stat">
            <div class="cl-stat-num">{{ $totalOrdenes }}</div>
            <div class="cl-stat-label">{{ $totalOrdenes === 1 ? 'Orden de servicio' : 'Órdenes de servicio' }}</div>
        </div>
        @if ($ultimaOrden)
            <div class="cl-stat">
                <div class="cl-stat-num" style="font-size:1rem;">{{ $ultimaOrden->fecha_ingreso->format('d/m/Y') }}</div>
                <div class="cl-stat-label">Última visita</div>
            </div>
        @endif
    </div>
</div>

{{-- ── Vehículos y datos ────────────────────────────────────────────── --}}
<div class="rejilla-2">
    <div class="tarjeta">
        <h2>Vehículos</h2>
        @if ($cliente->vehiculos->isEmpty())
            <div class="cl-empty">
                <span class="cl-empty-icon">🚗</span>
                Sin vehículos registrados.
            </div>
        @else
            <div class="cl-vehiculos-lista">
                @foreach ($cliente->vehiculos as $v)
                    <a href="{{ route('vehiculos.show', $v) }}" class="cl-veh">
                        <span class="cl-veh-icono">🚘</span>
                        <span class="cl-veh-info">
                            <span class="cl-veh-nombre">{{ $v->marca->nombre }} {{ $v->modelo->nombre }} {{ $v->anio }}</span>
                            <span class="cl-veh-sub">
                                {{ $v->placas ?? 'Sin placas' }}@if ($v->color) · {{ $v->color }}@endif
                            </span>
                        </span>
                        <span class="cl-veh-flecha">›</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="tarjeta">
        <h2>Datos del cliente</h2>
        <dl class="cl-datos-dl">
            <div class="cl-dato">
                <dt>RFC</dt>
                <dd>{{ $cliente->rfc ?? '—' }}</dd>
            </div>
            <div class="cl-dato">
                <dt>Tel. alterno</dt>
                <dd>{{ $cliente->telefono_alt ?? '—' }}</dd>
            </div>
            <div class="cl-dato">
                <dt>Dirección</dt>
                <dd>{{ $cliente->direccion ?? '—' }}</dd>
            </div>
        </dl>
        @if ($cliente->notas)
            <div class="cl-notas">{{ $cliente->notas }}</div>
        @endif
    </div>
</div>

{{-- ── Historial de órdenes ─────────────────────────────────────────── --}}
<div class="tarjeta">
    <h2>Historial de órdenes</h2>
    @if ($ordenes->isEmpty())
        <div class="cl-empty">
            <span class="cl-empty-icon">📋</span>
            Sin órdenes registradas.
        </div>
    @else
        <div class="tabla-scroll">
            <table class="cl-ordenes-tabla">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Vehículo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ordenes as $orden)
                        <tr>
                            <td><a href="{{ route('ordenes.show', $orden) }}" class="cl-folio">{{ $orden->folio }}</a></td>
                            <td class="cl-fecha">{{ $orden->fecha_ingreso->format('d/m/Y') }}</td>
                            <td class="cl-modelo">{{ $orden->vehiculo->modelo->nombre }}</td>
                            <td><span class="etiqueta {{ $orden->estado->value }}">{{ $orden->estado->label() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Eliminar (solo si no tiene datos asociados) ─────────────────── --}}
@if ($cliente->vehiculos->isEmpty() && $ordenes->isEmpty())
    <div class="cl-zona-eliminar">
        <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" onsubmit="return confirm('¿Eliminar este cliente?')">
            @csrf @method('DELETE')
            <button class="boton-link">Eliminar cliente</button>
        </form>
    </div>
@endif

@endsection
