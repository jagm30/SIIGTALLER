@extends('layouts.app', ['publico' => true])

@section('titulo', $vehiculo->marca->nombre . ' ' . $vehiculo->modelo->nombre)

{{-- Página pública: NUNCA mostrar nombre, teléfono ni correo del dueño. --}}

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════════════
   Portal QR — diseño mobile-first, solo para el cliente
   ═══════════════════════════════════════════════════════════════ */

/* Ajusta el contenedor global para esta pantalla */
.contenido { padding: 0; background: #f2f4f8; }

/* ── Barra superior de marca ───────────────────────────────────── */
.qr-topbar {
    display: flex;
    align-items: center;
    gap: .55rem;
    padding: .75rem 1.1rem;
    padding-top: calc(.75rem + env(safe-area-inset-top, 0px));
    background: #12305a;
    color: rgba(255,255,255,.85);
    font-size: .85rem;
    font-weight: 600;
    letter-spacing: .01em;
    position: sticky;
    top: 0;
    z-index: 100;
}
.qr-topbar-icono {
    font-size: 1.1rem;
    line-height: 1;
}
.qr-topbar-logo {
    display: block;
    height: 26px; width: auto; max-width: 100px;
    object-fit: contain;
    background: #fff; border-radius: 4px; padding: 2px;
}

/* ── Área principal ────────────────────────────────────────────── */
.qr {
    max-width: 480px;
    margin: 0 auto;
    padding: 1rem 1rem calc(1.5rem + env(safe-area-inset-bottom, 0px));
}

/* ── Hero del vehículo ─────────────────────────────────────────── */
.qr-hero {
    background: linear-gradient(145deg, #12305a 0%, #1f5fbf 100%);
    color: #fff;
    border-radius: 18px;
    padding: 1.5rem 1.4rem 1.4rem;
    margin-bottom: .85rem;
    box-shadow: 0 4px 20px rgba(18,48,90,.25);
}
.qr-hero-marca {
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase;
    opacity: .6;
    margin: 0 0 .5rem;
}
.qr-hero h1 {
    font-size: 1.75rem;
    font-weight: 800;
    line-height: 1.15;
    margin: 0 0 .6rem;
}
.qr-hero h1 span { font-weight: 400; opacity: .65; }
.qr-hero-chips {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem;
}
.qr-hero-chip {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .8rem;
    background: rgba(255,255,255,.15);
    padding: .2rem .6rem;
    border-radius: 999px;
    backdrop-filter: blur(4px);
}

/* ── Tarjeta base ──────────────────────────────────────────────── */
.qr-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.1rem 1.2rem;
    margin-bottom: .85rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
}
.qr-label {
    font-size: .66rem;
    font-weight: 800;
    letter-spacing: .13em;
    text-transform: uppercase;
    color: var(--tenue);
    margin: 0 0 .85rem;
}

/* ── Banner "en taller" ────────────────────────────────────────── */
.qr-banner {
    background: #fffbf0;
    border: 1.5px solid #f0d48a;
    border-radius: 16px;
    padding: 1rem 1.2rem .9rem;
    margin-bottom: .85rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.qr-banner-titulo {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin-bottom: .5rem;
}
.qr-banner-titulo h2 {
    font-size: .98rem;
    font-weight: 700;
    color: #5a3800;
    margin: 0;
}
.qr-banner-icono { font-size: 1.3rem; line-height: 1; flex-shrink: 0; }

/* ── Indicador de progreso ─────────────────────────────────────── */
.qr-progreso {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    position: relative;
    padding: 0 .25rem;
    margin: .9rem 0 .6rem;
}
.qr-progreso::before {
    content: '';
    position: absolute;
    top: 10px;
    left: calc(.25rem + 10px);
    right: calc(.25rem + 10px);
    height: 2px;
    background: #e4e7ec;
    z-index: 0;
}
.qr-progreso-linea {
    position: absolute;
    top: 10px;
    left: calc(.25rem + 10px);
    height: 2px;
    background: var(--exito);
    z-index: 1;
    transition: width .6s ease;
}
.qr-paso {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .35rem;
    position: relative;
    z-index: 2;
    flex: 1;
}
.qr-paso-circulo {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #e4e7ec;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background .3s;
}
.qr-paso-check {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    background: #fff;
    display: none;
}
.qr-paso.completado .qr-paso-circulo {
    background: var(--exito);
}
.qr-paso.completado .qr-paso-check { display: block; }
.qr-paso.actual .qr-paso-circulo {
    background: var(--primario);
    box-shadow: 0 0 0 4px rgba(31,95,191,.18);
    animation: qr-pulso 1.8s ease-in-out infinite;
}
.qr-paso.actual .qr-paso-check { display: block; }
@keyframes qr-pulso {
    0%, 100% { box-shadow: 0 0 0 4px rgba(31,95,191,.18); }
    50%       { box-shadow: 0 0 0 7px rgba(31,95,191,.08); }
}
.qr-paso-label {
    font-size: .65rem;
    font-weight: 600;
    text-align: center;
    color: #9aa5b4;
    line-height: 1.3;
    max-width: 54px;
}
.qr-paso.completado .qr-paso-label { color: var(--exito); }
.qr-paso.actual .qr-paso-label     { color: var(--primario); }

/* ── Detalle del servicio en curso ─────────────────────────────── */
.qr-sublabel {
    font-size: .65rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--tenue);
    margin: .9rem 0 .4rem;
}
.qr-sublabel:first-child { margin-top: 0; }
.qr-diagnostico {
    font-size: .9rem;
    white-space: pre-line;
    background: #f5f7fa;
    border-radius: 10px;
    padding: .7rem .85rem;
    border-left: 3px solid var(--primario);
    line-height: 1.5;
}
.qr-trabajo {
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: .55rem 0;
    border-top: 1px solid #f2f3f5;
    font-size: .9rem;
}
.qr-trabajo:first-child { border-top: 0; }
.qr-punto {
    width: 10px; height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}
.qr-punto-pendiente          { background: #c8d0da; }
.qr-punto-en_proceso         { background: var(--primario); box-shadow: 0 0 0 3px #dce9fb; }
.qr-punto-espera_refacciones { background: var(--alerta); }
.qr-punto-terminado          { background: var(--exito); }
.qr-trabajo-nombre { flex: 1; line-height: 1.3; }
.qr-trabajo-tag {
    font-size: .72rem;
    font-weight: 700;
    padding: .15rem .5rem;
    border-radius: 999px;
    white-space: nowrap;
}
.qr-tag-pendiente          { background: #eef0f3; color: #66727f; }
.qr-tag-en_proceso         { background: #dce9fb; color: #174a96; }
.qr-tag-espera_refacciones { background: #fff1d1; color: #7a4f00; }
.qr-tag-terminado          { background: #dff3e7; color: #155c34; }

/* ── Próximo servicio ──────────────────────────────────────────── */
.qr-proximo-nombre {
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0 0 .5rem;
    line-height: 1.3;
}
.qr-proximo-dato {
    display: flex;
    align-items: center;
    gap: .45rem;
    font-size: .9rem;
    color: var(--tenue);
    padding: .3rem 0;
}
.qr-proximo-dato strong { color: var(--texto); font-weight: 600; }
.qr-proximo-dato + .qr-proximo-dato {
    border-top: 1px solid #f2f3f5;
}

/* ── Historial (accordion) ─────────────────────────────────────── */
.qr-ul { list-style: none; padding: 0; margin: 0; }
.qr-ul li {
    font-size: .875rem;
    padding: .2rem 0;
    display: flex;
    align-items: baseline;
    gap: .45rem;
    border-top: 1px solid #f8f9fb;
    line-height: 1.4;
}
.qr-ul li:first-child { border-top: 0; }
.qr-ul li::before { content: '·'; color: #c8d0da; flex-shrink: 0; }
.qr-total {
    text-align: right;
    font-weight: 700;
    font-size: .9rem;
    margin-top: .65rem;
    padding-top: .65rem;
    border-top: 1px solid #f2f3f5;
}

details.qr-servicio { border-top: 1px solid #f2f3f5; }
details.qr-servicio:first-of-type { border-top: 0; }
details.qr-servicio summary {
    list-style: none;
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .95rem 0;
    cursor: pointer;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
    min-height: 44px;
}
details.qr-servicio summary::-webkit-details-marker { display: none; }
details.qr-servicio summary::marker               { display: none; }
.qr-s-fecha { font-weight: 700; font-size: .95rem; white-space: nowrap; }
.qr-s-desc  { font-size: .82rem; color: var(--tenue); flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.qr-s-km    { font-size: .78rem; color: var(--tenue); white-space: nowrap; }
.qr-s-flecha {
    color: #c0c8d2;
    font-size: .8rem;
    transition: transform .22s ease;
    flex-shrink: 0;
    margin-left: auto;
}
details[open] .qr-s-flecha { transform: rotate(180deg); }
.qr-servicio-cuerpo { padding-bottom: 1rem; }

/* ── Estado vacío ──────────────────────────────────────────────── */
.qr-vacio {
    font-size: .9rem;
    color: var(--tenue);
    margin: 0;
    text-align: center;
    padding: .5rem 0 .25rem;
}

/* ── Pie ───────────────────────────────────────────────────────── */
.qr-pie {
    text-align: center;
    font-size: .72rem;
    color: #a0aab4;
    padding: .5rem 0 .25rem;
}

/* ── Responsivo ────────────────────────────────────────────────── */
@media (max-width: 400px) {
    .qr { padding: .75rem .75rem calc(1.25rem + env(safe-area-inset-bottom, 0px)); }
    .qr-hero h1 { font-size: 1.5rem; }
    .qr-paso-label { font-size: .6rem; max-width: 46px; }
}
</style>
@endpush

@section('contenido')

@php
    // Mapeo de estado a paso (1-4) para el indicador de progreso
    $pasoActual = match($enTaller?->estado->value) {
        'recibido'                       => 1,
        'diagnostico', 'por_autorizar'   => 2,
        'en_reparacion', 'espera_refacciones' => 3,
        'terminado'                      => 4,
        default                          => 0,
    };
    $pasos = ['Recibido', 'Diagnóstico', 'Reparación', 'Listo'];
    // % de la línea de progreso completada (entre los 4 círculos hay 3 tramos)
    $lineaW = $pasoActual > 0 ? round(min($pasoActual - 1, 3) / 3 * 100) : 0;
@endphp

{{-- ── Barra superior ─────────────────────────────────────────────── --}}
<div class="qr-topbar">
    <span class="qr-topbar-icono">@include('layouts._logo', ['clase' => 'qr-topbar-logo'])</span>
    {{ $nombreTaller }}
</div>

<div class="qr">

    {{-- ── Hero vehículo ─────────────────────────────────────────── --}}
    <div class="qr-hero">
        <p class="qr-hero-marca">Portal de seguimiento</p>
        <h1>
            {{ $vehiculo->marca->nombre }} {{ $vehiculo->modelo->nombre }}
            @if ($vehiculo->anio)<span>{{ $vehiculo->anio }}</span>@endif
        </h1>
        <div class="qr-hero-chips">
            @if ($vehiculo->placas)
                <span class="qr-hero-chip">🪪 {{ $vehiculo->placas }}</span>
            @endif
            @if ($vehiculo->color)
                <span class="qr-hero-chip">🎨 {{ $vehiculo->color }}</span>
            @endif
            @if ($vehiculo->km_actual)
                <span class="qr-hero-chip">🛣 {{ number_format($vehiculo->km_actual) }} km</span>
            @endif
        </div>
    </div>

    {{-- ── En taller ──────────────────────────────────────────────── --}}
    @if ($enTaller)
        <div class="qr-banner">
            <div class="qr-banner-titulo">
                <span class="qr-banner-icono">🔧</span>
                <h2>Tu vehículo está en el taller</h2>
            </div>

            {{-- Indicador de progreso --}}
            <div class="qr-progreso">
                <div class="qr-progreso-linea" style="width: {{ $lineaW }}%;"></div>
                @foreach ($pasos as $i => $label)
                    @php
                        $n = $i + 1;
                        $clase = $n < $pasoActual ? 'completado' : ($n === $pasoActual ? 'actual' : '');
                    @endphp
                    <div class="qr-paso {{ $clase }}">
                        <div class="qr-paso-circulo">
                            <div class="qr-paso-check"></div>
                        </div>
                        <span class="qr-paso-label">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Detalle del servicio en curso --}}
        @if ($enTaller->diagnostico || $enTaller->trabajos->isNotEmpty() || $enTaller->refacciones->isNotEmpty())
            <div class="qr-card">
                <p class="qr-label">Servicio en curso</p>
                @include('qr._orden', ['orden' => $enTaller])
            </div>
        @endif
    @endif

    {{-- ── Próximo servicio ───────────────────────────────────────── --}}
    <div class="qr-card">
        <p class="qr-label">Próximo servicio</p>
        @if ($proximo)
            <p class="qr-proximo-nombre">{{ $proximo->descripcion }}</p>
            @if ($proximo->fecha_programada)
                <div class="qr-proximo-dato">
                    📅 Fecha: <strong>{{ $proximo->fecha_programada->format('d/m/Y') }}</strong>
                </div>
            @endif
            @if ($proximo->km_programado)
                <div class="qr-proximo-dato">
                    🛣 Kilometraje: <strong>{{ number_format($proximo->km_programado) }} km</strong>
                </div>
            @endif
            @if ($proximo->fecha_programada && $proximo->km_programado)
                <p style="font-size:.78rem; color:var(--tenue); margin:.4rem 0 0;">
                    Lo que ocurra primero.
                </p>
            @endif
        @else
            <p class="qr-vacio">Sin servicio programado.</p>
        @endif
    </div>

    {{-- ── Historial de servicios ──────────────────────────────────── --}}
    <div class="qr-card" id="historial">
        <p class="qr-label">Historial de servicios</p>
        @forelse ($ordenes as $orden)
            @php
                $fecha   = ($orden->fecha_entrega ?? $orden->fecha_ingreso)->format('d/m/Y');
                $km      = $orden->entrega?->km ?? $orden->recepcion?->km;
                $primer  = $orden->trabajos->first()?->descripcion;
                $nTrabs  = $orden->trabajos->count();
            @endphp
            <details class="qr-servicio">
                <summary>
                    <span class="qr-s-fecha">{{ $fecha }}</span>
                    @if ($primer)
                        <span class="qr-s-desc">{{ $primer }}@if ($nTrabs > 1) +{{ $nTrabs - 1 }}@endif</span>
                    @endif
                    @if ($km)
                        <span class="qr-s-km">{{ number_format($km) }} km</span>
                    @endif
                    <span class="qr-s-flecha">▼</span>
                </summary>
                <div class="qr-servicio-cuerpo">
                    @include('qr._orden')
                    @if ($mostrarPrecios && $orden->total !== null)
                        <p class="qr-total">Total: ${{ number_format($orden->total, 2) }}</p>
                    @endif
                </div>
            </details>
        @empty
            <p class="qr-vacio">Aún no hay servicios registrados.</p>
        @endforelse
    </div>

    <p class="qr-pie">{{ $nombreTaller }} · historial de tu vehículo</p>

</div>
@endsection
