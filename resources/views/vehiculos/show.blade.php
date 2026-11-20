@extends('layouts.app')

@section('titulo', $vehiculo->marca->nombre . ' ' . $vehiculo->modelo->nombre)

@push('styles')
<style>
    /* ── Vista de vehículo ─────────────────────────────────────────────── */
    .veh-hero {
        background: var(--tarjeta);
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .veh-hero-acento {
        height: 4px;
        background: linear-gradient(90deg, #2563c8 0%, #5b9cf6 100%);
    }
    .veh-hero-cuerpo {
        display: flex;
        align-items: flex-start;
        gap: 1.1rem;
        padding: 1.2rem 1.2rem .8rem;
        flex-wrap: wrap;
    }
    .veh-icono-bloque {
        flex-shrink: 0;
        width: 58px;
        height: 58px;
        border-radius: 12px;
        background: linear-gradient(135deg, #1e4fa8 0%, #3b7de0 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        line-height: 1;
        user-select: none;
    }
    .veh-hero-info { flex: 1; min-width: 0; }
    .veh-hero-info h1 { font-size: 1.4rem; margin: 0 0 .2rem; line-height: 1.2; }
    .veh-badges {
        display: flex;
        flex-wrap: wrap;
        gap: .3rem;
        margin-bottom: .45rem;
    }
    .veh-badge {
        display: inline-block;
        padding: .15rem .55rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 600;
    }
    .veh-badge-tipo {
        background: #e8f0fb;
        color: var(--primario);
    }
    .veh-badge-noverif {
        background: #fff1d1;
        color: #7a4f00;
    }
    .veh-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .3rem .8rem;
    }
    .veh-chip {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .875rem;
        color: var(--tenue);
    }
    .veh-hero-acciones {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        padding: 0 1.2rem .9rem;
        justify-content: flex-end;
    }

    /* Stats strip */
    .veh-stats {
        display: flex;
        border-top: 1px solid var(--borde);
        background: #fafbfc;
    }
    .veh-stat {
        flex: 1;
        padding: .65rem 1.1rem;
    }
    .veh-stat + .veh-stat { border-left: 1px solid var(--borde); }
    .veh-stat-num {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--primario);
        line-height: 1.1;
    }
    .veh-stat-label {
        font-size: .78rem;
        color: var(--tenue);
        margin-top: .05rem;
    }

    /* Datos técnicos */
    .veh-datos-dl { margin: 0; }
    .veh-dato {
        display: grid;
        grid-template-columns: 130px 1fr;
        gap: .2rem 1rem;
        padding: .45rem 0;
        border-bottom: 1px solid #f0f1f3;
        font-size: .9rem;
        align-items: baseline;
    }
    .veh-dato:last-of-type { border-bottom: 0; }
    .veh-dato dt {
        font-size: .78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: var(--tenue);
        line-height: 1.5;
    }
    .veh-dato dd { margin: 0; line-height: 1.5; word-break: break-word; }
    .veh-dato dd a { color: var(--primario); text-decoration: none; }
    .veh-dato dd a:hover { text-decoration: underline; }

    /* Próximo servicio */
    .veh-recordatorio {
        display: flex;
        gap: .75rem;
        align-items: flex-start;
        padding: .6rem 0;
        border-bottom: 1px solid #f0f1f3;
    }
    .veh-recordatorio:last-of-type { border-bottom: 0; }
    .veh-rec-icono {
        font-size: 1.1rem;
        margin-top: .1rem;
        flex-shrink: 0;
    }
    .veh-rec-titulo {
        font-weight: 600;
        font-size: .92rem;
        margin: 0 0 .15rem;
    }
    .veh-rec-cuando {
        font-size: .82rem;
        color: var(--tenue);
    }

    /* QR */
    .veh-qr-link {
        font-size: .82rem;
        word-break: break-all;
        color: var(--tenue);
        margin: .3rem 0 .6rem;
        display: block;
    }
    .veh-qr-inactivo {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .88rem;
        color: var(--tenue);
        margin: .25rem 0 .6rem;
    }

    /* Historial de órdenes */
    .veh-orden {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: .5rem 1rem;
        padding: .8rem 0;
        border-bottom: 1px solid var(--borde);
        align-items: start;
    }
    .veh-orden:last-of-type { border-bottom: 0; }
    .veh-orden-meta { min-width: 0; }
    .veh-folio {
        font-family: ui-monospace, "Cascadia Code", Consolas, monospace;
        font-size: .9rem;
        font-weight: 700;
        color: var(--primario);
        text-decoration: none;
        white-space: nowrap;
    }
    .veh-folio:hover { text-decoration: underline; }
    .veh-orden-fecha {
        font-size: .8rem;
        color: var(--tenue);
        margin-top: .05rem;
        white-space: nowrap;
    }
    .veh-orden-motivo {
        font-size: .9rem;
        color: var(--texto);
        margin: .2rem 0 0;
        line-height: 1.4;
    }
    .veh-orden-trabajos {
        font-size: .82rem;
        color: var(--tenue);
        margin: .2rem 0 0;
        line-height: 1.4;
    }
    .veh-orden-estado { padding-top: .15rem; }

    .veh-empty {
        text-align: center;
        padding: 1.75rem 1rem;
        color: var(--tenue);
        font-size: .9rem;
    }
    .veh-empty-icon {
        font-size: 2rem;
        display: block;
        margin-bottom: .4rem;
        opacity: .35;
    }

    /* ── Responsive ──────────────────────────────────────────────────── */
    @media (max-width: 600px) {
        .veh-hero-cuerpo { gap: .8rem; }
        .veh-icono-bloque { width: 48px; height: 48px; border-radius: 10px; font-size: 1.45rem; }
        .veh-hero-info h1 { font-size: 1.2rem; }
        .veh-hero-acciones { justify-content: flex-start; padding-top: 0; }
        .veh-orden { grid-template-columns: 1fr auto; }
        .veh-orden-meta-left { grid-column: 1 / -1; }
    }
    @media (max-width: 480px) {
        .veh-stats { flex-direction: column; }
        .veh-stat + .veh-stat { border-left: 0; border-top: 1px solid var(--borde); }
        .veh-dato { grid-template-columns: 1fr; gap: .05rem; }
        .veh-dato dt { margin-top: .3rem; }
        .veh-orden { grid-template-columns: 1fr; gap: .3rem; }
    }
</style>
@endpush

@section('contenido')

@php
    $iconoPorTipo = [
        'sedan'      => '🚗',
        'hatchback'  => '🚗',
        'suv'        => '🚙',
        'pickup'     => '🛻',
        'van'        => '🚐',
        'coupe'      => '🏎️',
        'moto'       => '🏍️',
        'camion'     => '🚛',
    ];
    $icono = $iconoPorTipo[$vehiculo->modelo->tipo_carroceria->value] ?? '🚗';
    $totalServicios = $vehiculo->ordenes()->count();
@endphp

{{-- ── Hero ────────────────────────────────────────────────────────────── --}}
<div class="veh-hero">
    <div class="veh-hero-acento"></div>

    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start;">
        <div class="veh-hero-cuerpo" style="flex:1; min-width:0;">
            <div class="veh-icono-bloque">{{ $icono }}</div>
            <div class="veh-hero-info">
                <h1>{{ $vehiculo->marca->nombre }} {{ $vehiculo->modelo->nombre }} {{ $vehiculo->anio }}</h1>
                <div class="veh-badges">
                    <span class="veh-badge veh-badge-tipo">{{ $vehiculo->modelo->tipo_carroceria->label() }}</span>
                    @if (! $vehiculo->modelo->verificado)
                        <span class="veh-badge veh-badge-noverif">modelo por verificar</span>
                    @endif
                </div>
                <div class="veh-chips">
                    @if ($vehiculo->placas)
                        <span class="veh-chip">🪪 {{ $vehiculo->placas }}</span>
                    @else
                        <span class="veh-chip">Sin placas</span>
                    @endif
                    @if ($vehiculo->color)
                        <span class="veh-chip">🎨 {{ $vehiculo->color }}</span>
                    @endif
                    @if ($vehiculo->km_actual)
                        <span class="veh-chip">🛣 {{ number_format($vehiculo->km_actual) }} km</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="veh-hero-acciones">
            @can('recepcion.realizar')
                <a href="{{ route('ordenes.create', ['vehiculo_id' => $vehiculo->id]) }}" class="boton">Recibir en taller</a>
            @endcan
            <a href="{{ route('vehiculos.edit', $vehiculo) }}" class="boton secundario">Editar</a>
        </div>
    </div>

    <div class="veh-stats">
        <div class="veh-stat">
            <div class="veh-stat-num">{{ $vehiculo->anio }}</div>
            <div class="veh-stat-label">Año del modelo</div>
        </div>
        <div class="veh-stat">
            <div class="veh-stat-num">{{ $vehiculo->km_actual ? number_format($vehiculo->km_actual) : '—' }}</div>
            <div class="veh-stat-label">Kilómetros actuales</div>
        </div>
        <div class="veh-stat">
            <div class="veh-stat-num">{{ $totalServicios }}</div>
            <div class="veh-stat-label">{{ $totalServicios === 1 ? 'Servicio registrado' : 'Servicios registrados' }}</div>
        </div>
    </div>
</div>

{{-- ── Datos técnicos y próximo servicio ────────────────────────────── --}}
<div class="rejilla-2">
    <div class="tarjeta">
        <h2>Datos técnicos</h2>
        <dl class="veh-datos-dl">
            <div class="veh-dato">
                <dt>Propietario</dt>
                <dd><a href="{{ route('clientes.show', $vehiculo->cliente) }}">{{ $vehiculo->cliente->nombre_completo }}</a>
                    <span class="tenue chico"> · {{ $vehiculo->cliente->telefono }}</span></dd>
            </div>
            <div class="veh-dato">
                <dt>Versión</dt>
                <dd>{{ $vehiculo->version ?? '—' }}</dd>
            </div>
            <div class="veh-dato">
                <dt>VIN</dt>
                <dd>{{ $vehiculo->vin ?? '—' }}</dd>
            </div>
            <div class="veh-dato">
                <dt>No. de motor</dt>
                <dd>{{ $vehiculo->numero_motor ?? '—' }}</dd>
            </div>
            <div class="veh-dato">
                <dt>Tipo</dt>
                <dd>{{ $vehiculo->modelo->tipo_carroceria->label() }}</dd>
            </div>
        </dl>
    </div>

    <div class="tarjeta">
        <h2>Próximo servicio</h2>
        @if ($vehiculo->recordatorios->isEmpty())
            <div class="veh-empty" style="padding: .9rem 0 .5rem;">
                <span class="veh-empty-icon" style="font-size: 1.5rem;">📅</span>
                Sin servicios programados.
            </div>
        @else
            @foreach ($vehiculo->recordatorios as $r)
                <div class="veh-recordatorio">
                    <span class="veh-rec-icono">🔔</span>
                    <div>
                        <p class="veh-rec-titulo">{{ $r->descripcion }}</p>
                        <span class="veh-rec-cuando">
                            @if ($r->fecha_programada) {{ $r->fecha_programada->format('d/m/Y') }} @endif
                            @if ($r->fecha_programada && $r->km_programado) o @endif
                            @if ($r->km_programado) a los {{ number_format($r->km_programado) }} km @endif
                            (lo que ocurra primero)
                        </span>
                    </div>
                </div>
            @endforeach
        @endif

        <h3 style="margin-top:1rem; padding-top:.75rem; border-top:1px solid var(--borde);">Página del cliente (QR)</h3>
        @if ($vehiculo->qr_activo)
            <a href="{{ route('qr.show', $vehiculo->qr_token) }}" target="_blank" class="veh-qr-link">
                {{ route('qr.show', $vehiculo->qr_token) }}
            </a>
        @else
            <p class="veh-qr-inactivo">QR desactivado para este vehículo.</p>
        @endif
        @can('qr.regenerar')
            <form method="POST" action="{{ route('vehiculos.qr', $vehiculo) }}"
                  onsubmit="return confirm('La etiqueta impresa actual dejará de funcionar. ¿Continuar?')">
                @csrf
                <button class="boton chico secundario">Generar QR nuevo</button>
            </form>
        @endcan
    </div>
</div>

{{-- ── Historial de servicios ─────────────────────────────────────────── --}}
<div class="tarjeta">
    <h2>Historial de servicio</h2>
    @if ($ordenes->isEmpty())
        <div class="veh-empty">
            <span class="veh-empty-icon">🔧</span>
            Este vehículo no ha tenido servicios.
        </div>
    @else
        @foreach ($ordenes as $orden)
            <div class="veh-orden">
                <div>
                    <a href="{{ route('ordenes.show', $orden) }}" class="veh-folio">{{ $orden->folio }}</a>
                    <div class="veh-orden-fecha">{{ $orden->fecha_ingreso->format('d/m/Y') }}</div>
                </div>
                <div class="veh-orden-meta">
                    <p class="veh-orden-motivo">{{ $orden->motivo_ingreso }}</p>
                    @if ($orden->trabajos->isNotEmpty())
                        <p class="veh-orden-trabajos">{{ $orden->trabajos->pluck('descripcion')->join(' · ') }}</p>
                    @endif
                </div>
                <div class="veh-orden-estado">
                    <span class="etiqueta {{ $orden->estado->value }}">{{ $orden->estado->label() }}</span>
                </div>
            </div>
        @endforeach
        <div class="paginacion">{{ $ordenes->links('pagination::default') }}</div>
    @endif
</div>

@endsection
