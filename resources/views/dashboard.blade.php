@extends('layouts.app')

@section('titulo', 'Inicio')

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════
   Dashboard — estilos específicos
   ═══════════════════════════════════════════════════════ */

/* ── Hero ───────────────────────────────────────────── */
.db-hero {
    background: var(--tarjeta);
    border: 1px solid var(--borde);
    border-radius: var(--radio);
    margin-bottom: 1rem;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.db-hero-acento {
    height: 4px;
    background: linear-gradient(90deg, var(--primario) 0%, #5b9cf6 100%);
}
.db-hero-cuerpo {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.1rem 1.2rem;
    flex-wrap: wrap;
}
.db-hero-izq {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 0;
}
.db-hero-icono {
    flex-shrink: 0;
    width: 52px;
    height: 52px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primario) 0%, #3b82e0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.55rem;
    line-height: 1;
    user-select: none;
}
.db-hero-cuerpo h1 { margin: 0 0 .2rem; font-size: 1.35rem; }
.db-hero-chips {
    display: flex;
    flex-wrap: wrap;
    gap: .25rem .8rem;
}
.db-hero-chip {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .875rem;
    color: var(--tenue);
}

/* ── KPI tiles ──────────────────────────────────────── */
.db-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
    gap: .75rem;
    margin-bottom: 1rem;
}
.db-kpi {
    background: #fff;
    border: 1px solid var(--borde);
    border-radius: var(--radio);
    padding: 1rem 1.1rem 1rem 1.5rem;
    position: relative;
    overflow: hidden;
    transition: transform .18s ease, box-shadow .18s ease;
    cursor: default;
    user-select: none;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.db-kpi:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,.09);
}
.db-kpi::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 5px;
    border-radius: 8px 0 0 8px;
}
.db-kpi--azul::before  { background: var(--primario); }
.db-kpi--verde::before { background: var(--exito); }
.db-kpi--rojo::before  { background: var(--peligro); }
.db-kpi--ambar::before { background: var(--alerta); }

.db-kpi__num {
    display: block;
    font-size: 2.4rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -.04em;
    color: var(--texto);
}
.db-kpi--rojo  .db-kpi__num { color: var(--peligro); }
.db-kpi--verde .db-kpi__num { color: var(--exito); }

.db-kpi__label {
    display: block;
    font-size: .76rem;
    font-weight: 700;
    color: var(--tenue);
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-top: .35rem;
}
.db-kpi__sub {
    display: block;
    font-size: .8rem;
    color: var(--tenue);
    margin-top: .2rem;
}

/* ── Distribución por estado ────────────────────────── */
.db-dist { display: grid; gap: .65rem; }
.db-dist-row {
    display: grid;
    grid-template-columns: 155px 1fr 2.2rem;
    align-items: center;
    gap: .75rem;
}
.db-dist-label {
    display: flex;
    align-items: center;
    gap: .45rem;
    font-size: .875rem;
}
.db-dist-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    background: var(--ec, #9aa5b1);
}
.db-dist-track {
    height: 9px;
    background: #edf0f4;
    border-radius: 999px;
    overflow: hidden;
}
.db-dist-fill {
    height: 100%;
    border-radius: 999px;
    width: 0;
    background: var(--ec, #9aa5b1);
    transition: width .85s cubic-bezier(.4, 0, .2, 1);
}
.db-dist-num {
    font-size: .875rem;
    font-weight: 700;
    text-align: right;
    color: var(--texto);
}
/* Color por estado vía variable CSS custom */
.db-est-recibido           { --ec: #8f9fae; }
.db-est-diagnostico,
.db-est-por_autorizar      { --ec: #c89d00; }
.db-est-en_reparacion      { --ec: var(--primario); }
.db-est-espera_refacciones { --ec: #c0530a; }
.db-est-terminado          { --ec: var(--exito); }

/* ── Layout 2 columnas ─────────────────────────────── */
.db-row {
    display: grid;
    grid-template-columns: 310px 1fr;
    gap: 1rem;
    align-items: start;
    margin-bottom: 1rem;
}

/* ── Alertas panel ─────────────────────────────────── */
.db-alert-list { margin: 0; }
.db-alert-item {
    display: flex;
    gap: .65rem;
    padding: .6rem 0;
    border-bottom: 1px solid var(--borde);
    align-items: flex-start;
    font-size: .875rem;
}
.db-alert-item:last-child { border-bottom: 0; }
.db-alert-bullet {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--peligro);
    margin-top: .35rem;
    flex-shrink: 0;
}
.db-ok {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.75rem 1rem;
    text-align: center;
    color: var(--exito);
    gap: .4rem;
}
.db-ok__icon {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: #e8f5ed;
    display: flex;
    align-items: center;
    justify-content: center;
}
.db-ok__icon svg { display: block; }

/* ── Tabla de órdenes ──────────────────────────────── */
.db-tabla tr.atrasada td { background: #fff9f9; }
.db-tabla tr.atrasada td:first-child {
    border-left: 3px solid var(--peligro);
    padding-left: calc(.5rem - 3px);
}
.db-tabla tr.atrasada:hover td { background: #fff2f2; }

.db-folio {
    font-family: ui-monospace, "Cascadia Code", Consolas, monospace;
    font-size: .9rem;
    font-weight: 700;
    color: var(--primario);
    text-decoration: none;
}
.db-folio:hover { text-decoration: underline; }

.db-dias {
    display: inline-block;
    font-size: .77rem;
    font-weight: 700;
    padding: .15rem .45rem;
    border-radius: 4px;
    white-space: nowrap;
}
.db-dias--ok      { background: #eef0f3; color: var(--tenue); }
.db-dias--medio   { background: #fff6e0; color: var(--alerta); }
.db-dias--critico { background: #fdecea; color: var(--peligro); }

.db-promesa {
    font-size: .72rem;
    font-weight: 700;
    color: var(--peligro);
    display: block;
    margin-top: .15rem;
    white-space: nowrap;
}

/* ── Recordatorios ─────────────────────────────────── */
.db-rec-badge {
    display: inline-block;
    font-size: .77rem;
    font-weight: 700;
    color: #fff;
    padding: .2rem .5rem;
    border-radius: 5px;
    white-space: nowrap;
    min-width: 48px;
    text-align: center;
}
.db-rec-badge--pronto    { background: var(--peligro); }
.db-rec-badge--medio     { background: var(--alerta); }
.db-rec-badge--tranquilo { background: var(--tenue); }

/* ── Encabezado de sección ─────────────────────────── */
.db-sec-hdr {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: .5rem;
    margin-bottom: .85rem;
}
.db-sec-hdr h2 { margin: 0; }

/* ── Estado vacío ──────────────────────────────────── */
.db-vacio {
    text-align: center;
    padding: 2.5rem 1rem;
    color: var(--tenue);
}
.db-vacio svg { opacity: .3; display: block; margin: 0 auto .75rem; }

/* ── Responsivo ────────────────────────────────────── */
@media (max-width: 760px) {
    .db-row { grid-template-columns: 1fr; }
    .db-dist-row { grid-template-columns: auto 1fr auto; }
}
@media (max-width: 540px) {
    .db-kpi__num { font-size: 2rem; }
    .db-kpis { grid-template-columns: repeat(2, 1fr); }
    .db-hero-icono { width: 44px; height: 44px; font-size: 1.3rem; border-radius: 9px; }
    .db-hero-cuerpo h1 { font-size: 1.15rem; }
}
</style>
@endpush

@section('contenido')
@php
    $esAdmin = auth()->user()->tienePermiso('ordenes.ver_todas');
    $ahora   = now();
@endphp

{{-- ══ Hero ══════════════════════════════════════════════════════════════ --}}
<div class="db-hero">
    <div class="db-hero-acento"></div>
    <div class="db-hero-cuerpo">
        <div class="db-hero-izq">
            <div class="db-hero-icono">🔧</div>
            <div>
                <h1>{{ $esAdmin ? 'Resumen del taller' : 'Mis órdenes' }}</h1>
                <div class="db-hero-chips">
                    <span class="db-hero-chip">📅 {{ $ahora->format('d/m/Y') }}</span>
                    <span class="db-hero-chip">
                        📋 {{ $ordenes->count() }} {{ $ordenes->count() === 1 ? 'orden abierta' : 'órdenes abiertas' }}
                    </span>
                    @if ($kpis && $kpis['atrasadas'] > 0)
                        <span class="db-hero-chip" style="color: var(--peligro); font-weight:600;">
                            ⚠ {{ $kpis['atrasadas'] }} {{ $kpis['atrasadas'] === 1 ? 'atrasada' : 'atrasadas' }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        @can('recepcion.realizar')
            <a href="{{ route('ordenes.create') }}" class="boton">+ Recibir vehículo</a>
        @endcan
    </div>
</div>

{{-- ══ KPI tiles (solo admin / recepción) ════════════════════════════ --}}
@if ($kpis)
<div class="db-kpis">

    <div class="db-kpi db-kpi--azul">
        <strong class="db-kpi__num" data-count="{{ $kpis['abiertas'] }}">{{ $kpis['abiertas'] }}</strong>
        <span class="db-kpi__label">Órdenes activas</span>
        <span class="db-kpi__sub">en este momento</span>
    </div>

    <div class="db-kpi db-kpi--verde">
        <strong class="db-kpi__num" data-count="{{ $kpis['terminadas'] }}">{{ $kpis['terminadas'] }}</strong>
        <span class="db-kpi__label">Listas para entregar</span>
        <span class="db-kpi__sub">trabajo terminado</span>
    </div>

    <div class="db-kpi {{ $kpis['atrasadas'] > 0 ? 'db-kpi--rojo' : 'db-kpi--azul' }}">
        <strong class="db-kpi__num" data-count="{{ $kpis['atrasadas'] }}">{{ $kpis['atrasadas'] }}</strong>
        <span class="db-kpi__label">Fecha vencida</span>
        <span class="db-kpi__sub">{{ $kpis['atrasadas'] > 0 ? 'requieren atención' : 'ninguna · al día ✓' }}</span>
    </div>

    @if ($verPrecios)
    <div class="db-kpi db-kpi--ambar">
        <strong class="db-kpi__num">${{ number_format($kpis['ingresos_mes'], 0) }}</strong>
        <span class="db-kpi__label">Facturado este mes</span>
        <span class="db-kpi__sub">
            {{ $kpis['entregadas_mes'] }}
            {{ $kpis['entregadas_mes'] === 1 ? 'entrega' : 'entregas' }}
        </span>
    </div>
    @endif

</div>
@endif

{{-- ══ Distribución + Alertas (solo admin, si hay órdenes) ════════════ --}}
@if ($kpis && $ordenes->isNotEmpty())
@php
    $maxCount         = $porEstado->max('count') ?: 1;
    $ordenesAtrasadas = $ordenes->filter(fn ($o) => $o->fecha_promesa?->isPast());
@endphp
<div class="db-row">

    {{-- Barras de distribución por estado --}}
    <div class="tarjeta" style="margin-bottom:0;">
        <h2 style="margin-bottom:.85rem;">Por estado</h2>
        <div class="db-dist">
            @foreach ($porEstado as $e)
            <div class="db-dist-row db-est-{{ $e['value'] }}">
                <div class="db-dist-label">
                    <span class="db-dist-dot"></span>
                    <span>{{ $e['label'] }}</span>
                </div>
                <div class="db-dist-track">
                    <div class="db-dist-fill"
                         data-w="{{ $e['count'] > 0 ? round($e['count'] / $maxCount * 100) : 0 }}">
                    </div>
                </div>
                <div class="db-dist-num">{{ $e['count'] }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Panel derecho: atrasadas o "todo al día" --}}
    <div class="tarjeta" style="margin-bottom:0;">
        @if ($ordenesAtrasadas->isNotEmpty())
            <div class="db-sec-hdr">
                <h2>Fecha vencida</h2>
                <span class="etiqueta cancelado">{{ $ordenesAtrasadas->count() }}</span>
            </div>
            <div class="db-alert-list">
                @foreach ($ordenesAtrasadas->take(7) as $a)
                <div class="db-alert-item">
                    <span class="db-alert-bullet"></span>
                    <div>
                        <a href="{{ route('ordenes.show', $a) }}" style="font-weight:600;
                            font-family:ui-monospace,Consolas,monospace;">{{ $a->folio }}</a>
                        · {{ $a->vehiculo->marca->nombre }} {{ $a->vehiculo->modelo->nombre }}<br>
                        <span class="tenue chico">
                            {{ $a->cliente->nombre_completo }}
                            · Prometido: {{ $a->fecha_promesa->format('d/m/Y') }}
                            ({{ $a->fecha_promesa->diffForHumans() }})
                        </span>
                    </div>
                </div>
                @endforeach
                @if ($ordenesAtrasadas->count() > 7)
                    <p class="tenue chico" style="margin:.5rem 0 0;">y {{ $ordenesAtrasadas->count() - 7 }} más…</p>
                @endif
            </div>
        @else
            <div class="db-ok">
                <div class="db-ok__icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                         stroke="var(--exito)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <strong style="color:var(--exito);">Todo al día</strong>
                <span class="tenue chico">Ninguna orden supera su fecha promesa</span>
            </div>
        @endif
    </div>

</div>
@endif

{{-- ══ Tabla de órdenes activas ════════════════════════════════════════ --}}
<div class="tarjeta">
    <div class="db-sec-hdr">
        <h2>Órdenes activas</h2>
        @if ($ordenes->isNotEmpty())
            <span class="tenue chico">{{ $ordenes->count() }} {{ $ordenes->count() === 1 ? 'orden' : 'órdenes' }}</span>
        @endif
    </div>

    @if ($ordenes->isEmpty())
        <div class="db-vacio">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8"  y1="2" x2="8"  y2="6"/>
                <line x1="3"  y1="10" x2="21" y2="10"/>
            </svg>
            No hay vehículos en el taller en este momento.
        </div>
    @else
        <div class="tabla-scroll">
            <table class="db-tabla">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Vehículo</th>
                        <th class="ocultar-movil">Cliente</th>
                        <th>Estado</th>
                        @if ($esAdmin)
                            <th class="ocultar-movil">Mecánico</th>
                        @endif
                        <th style="text-align:right;">Días</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($ordenes as $orden)
                @php
                    $dias      = (int) $orden->fecha_ingreso->diffInDays($ahora);
                    $claseDias = $dias >= 8 ? 'db-dias--critico' : ($dias >= 4 ? 'db-dias--medio' : 'db-dias--ok');
                    $atrasada  = $orden->fecha_promesa?->isPast();
                @endphp
                <tr @class(['atrasada' => $atrasada])>
                    <td>
                        <a href="{{ route('ordenes.show', $orden) }}" class="db-folio">{{ $orden->folio }}</a>
                    </td>
                    <td>
                        {{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo->nombre }}
                        @if ($orden->vehiculo->anio)
                            <span class="tenue">{{ $orden->vehiculo->anio }}</span>
                        @endif
                        <br>
                        <span class="tenue chico">{{ $orden->vehiculo->placas ?? 'Sin placas' }}</span>
                    </td>
                    <td class="ocultar-movil">{{ $orden->cliente->nombre_completo }}</td>
                    <td>
                        <span class="etiqueta {{ $orden->estado->value }}">{{ $orden->estado->label() }}</span>
                        @if ($atrasada)
                            <span class="db-promesa">⚠ prometido {{ $orden->fecha_promesa->format('d/m') }}</span>
                        @endif
                    </td>
                    @if ($esAdmin)
                        <td class="ocultar-movil">{{ $orden->mecanicoResponsable?->name ?? '—' }}</td>
                    @endif
                    <td style="text-align:right;">
                        <span class="db-dias {{ $claseDias }}">{{ $dias }}d</span>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ══ Recordatorios por vencer ════════════════════════════════════════ --}}
@if ($recordatorios->isNotEmpty())
<div class="tarjeta">
    <div class="db-sec-hdr">
        <h2>Servicios por vencer</h2>
        <span class="tenue chico">próximos 15 días · {{ $recordatorios->count() }}</span>
    </div>
    <div class="tabla-scroll">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Vehículo</th>
                    <th>Servicio</th>
                    <th class="ocultar-movil">Cliente</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($recordatorios as $r)
            @php
                $diasHasta = (int) $ahora->diffInDays($r->fecha_programada);
                $claseRec  = $diasHasta <= 2 ? 'db-rec-badge--pronto'
                           : ($diasHasta <= 7 ? 'db-rec-badge--medio'
                           : 'db-rec-badge--tranquilo');
            @endphp
            <tr>
                <td>
                    <span class="db-rec-badge {{ $claseRec }}">{{ $r->fecha_programada->format('d/m') }}</span>
                </td>
                <td>
                    <a href="{{ route('vehiculos.show', $r->vehiculo) }}">
                        {{ $r->vehiculo->modelo->nombre }} · {{ $r->vehiculo->placas }}
                    </a>
                </td>
                <td>{{ $r->descripcion }}</td>
                <td class="ocultar-movil">
                    {{ $r->vehiculo->cliente->nombre_completo }}
                    @if ($r->vehiculo->cliente->telefono)
                        · <span class="tenue">{{ $r->vehiculo->cliente->telefono }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    // Anima las barras de distribución al cargar la página
    var fills = document.querySelectorAll('.db-dist-fill[data-w]');
    requestAnimationFrame(function () {
        fills.forEach(function (el) {
            setTimeout(function () {
                el.style.width = el.dataset.w + '%';
            }, 80);
        });
    });

    // Count-up para cifras enteras en los KPI tiles
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var end = parseInt(el.dataset.count, 10);
        if (isNaN(end) || end === 0) return;
        var dur = 500;
        var t0  = performance.now();
        (function step(t) {
            var p    = Math.min((t - t0) / dur, 1);
            var ease = 1 - Math.pow(1 - p, 2);
            el.textContent = Math.round(end * ease);
            if (p < 1) requestAnimationFrame(step);
        }(t0));
    });
})();
</script>
@endpush
