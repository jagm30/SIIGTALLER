@extends('layouts.app', ['publico' => true])

@section('titulo', $vehiculo->marca->nombre . ' ' . $vehiculo->modelo->nombre)

{{-- Página pública: NUNCA mostrar nombre, teléfono ni correo del dueño. --}}
@section('contenido')
<style>
/* ═══════════════════════════════════════════════════════════════
   Portal QR — diseño mobile-first, independiente del panel admin
   ════════════════════════════════════════════════════════════════ */
.qr { max-width: 480px; margin: 0 auto; }

/* ── Hero del vehículo ─────────────────────────────────────────── */
.qr-hero {
    background: linear-gradient(145deg, #12305a 0%, #1f5fbf 100%);
    color: #fff;
    border-radius: 16px;
    padding: 1.5rem 1.4rem 1.3rem;
    margin-bottom: 1rem;
}
.qr-hero-marca {
    font-size: .7rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; opacity: .65; margin: 0 0 .4rem;
}
.qr-hero h1 {
    font-size: 1.75rem; font-weight: 800; line-height: 1.15; margin: 0 0 .5rem;
}
.qr-hero h1 span { font-weight: 400; opacity: .7; }
.qr-hero-meta {
    display: flex; flex-wrap: wrap; gap: .2rem .75rem;
    font-size: .875rem; opacity: .75;
}

/* ── Tarjeta base ──────────────────────────────────────────────── */
.qr-card {
    background: #fff;
    border-radius: 14px;
    padding: 1.1rem 1.25rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.07), 0 3px 12px rgba(0,0,0,.04);
}
.qr-label {
    font-size: .68rem; font-weight: 800; letter-spacing: .12em;
    text-transform: uppercase; color: var(--tenue); margin: 0 0 .75rem;
}

/* ── Banner "en taller" ────────────────────────────────────────── */
.qr-banner {
    display: flex; gap: .9rem; align-items: flex-start;
    background: #fffbf0; border: 1.5px solid #f0d48a;
    border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.qr-banner-icono { font-size: 2rem; line-height: 1; flex-shrink: 0; margin-top: .1rem; }
.qr-banner h2 { font-size: .95rem; font-weight: 700; color: #5a3800; margin: 0 0 .2rem; }
.qr-banner-estado { font-size: .88rem; color: #7a4f00; }
.qr-banner-estado strong { font-weight: 700; }

/* ── Próximo servicio ──────────────────────────────────────────── */
.qr-proximo-nombre { font-size: 1.05rem; font-weight: 700; margin: 0 0 .35rem; }
.qr-proximo-meta { font-size: .875rem; color: var(--tenue); line-height: 1.6; }
.qr-proximo-meta strong { color: var(--texto); font-weight: 600; }

/* ── Lista de trabajos (con punto de color) ────────────────────── */
.qr-trabajo {
    display: flex; align-items: center; gap: .65rem;
    padding: .45rem 0; border-top: 1px solid #f2f3f5; font-size: .9rem;
}
.qr-trabajo:first-child { border-top: 0; }
.qr-punto {
    width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0;
}
.qr-punto-pendiente        { background: #c8d0da; }
.qr-punto-en_proceso       { background: var(--primario); box-shadow: 0 0 0 3px #dce9fb; }
.qr-punto-espera_refacciones { background: var(--alerta); }
.qr-punto-terminado        { background: var(--exito); }
.qr-trabajo-nombre { flex: 1; }
.qr-trabajo-tag {
    font-size: .72rem; font-weight: 700; padding: .15rem .5rem;
    border-radius: 999px; white-space: nowrap;
}
.qr-tag-pendiente        { background: #eef0f3; color: #66727f; }
.qr-tag-en_proceso       { background: #dce9fb; color: #174a96; }
.qr-tag-espera_refacciones { background: #fff1d1; color: #7a4f00; }
.qr-tag-terminado        { background: #dff3e7; color: #155c34; }

/* ── Historial ─────────────────────────────────────────────────── */
details.qr-servicio { border-top: 1px solid #f2f3f5; }
details.qr-servicio:first-of-type { border-top: 0; }

details.qr-servicio summary {
    list-style: none; display: flex; align-items: center; gap: .5rem;
    padding: .9rem 0; cursor: pointer; user-select: none;
}
details.qr-servicio summary::-webkit-details-marker { display: none; }
details.qr-servicio summary::marker { display: none; }

.qr-s-fecha { font-weight: 700; font-size: .95rem; }
.qr-s-km    { font-size: .8rem; color: var(--tenue); flex: 1; }
.qr-s-flecha {
    color: #c0c8d2; font-size: .75rem;
    transition: transform .2s ease; flex-shrink: 0;
}
details[open] .qr-s-flecha { transform: rotate(180deg); }

.qr-servicio-cuerpo { padding-bottom: .9rem; }

.qr-sublabel {
    font-size: .66rem; font-weight: 800; letter-spacing: .1em;
    text-transform: uppercase; color: var(--tenue);
    margin: .75rem 0 .35rem;
}
.qr-sublabel:first-child { margin-top: 0; }

.qr-ul { list-style: none; padding: 0; margin: 0; }
.qr-ul li {
    font-size: .875rem; padding: .2rem 0;
    display: flex; align-items: baseline; gap: .45rem;
    border-top: 1px solid #f8f9fb;
}
.qr-ul li:first-child { border-top: 0; }
.qr-ul li::before { content: '·'; color: #c8d0da; flex-shrink: 0; }

.qr-diagnostico {
    font-size: .875rem; white-space: pre-line;
    background: #f8f9fb; border-radius: 8px;
    padding: .65rem .8rem; margin-bottom: .2rem;
    border-left: 3px solid var(--primario);
}

.qr-total {
    text-align: right; font-weight: 700; font-size: .9rem;
    margin-top: .65rem; padding-top: .65rem;
    border-top: 1px solid #f2f3f5;
}

.qr-vacio { font-size: .9rem; color: var(--tenue); margin: 0; }
</style>

<div class="qr">

    {{-- ── Hero vehículo ─────────────────────────────────── --}}
    <div class="qr-hero">
        <p class="qr-hero-marca">🔧 {{ config('app.name') }}</p>
        <h1>
            {{ $vehiculo->marca->nombre }} {{ $vehiculo->modelo->nombre }}
            @if ($vehiculo->anio)<span>{{ $vehiculo->anio }}</span>@endif
        </h1>
        <div class="qr-hero-meta">
            @if ($vehiculo->placas)<span>{{ $vehiculo->placas }}</span>@endif
            @if ($vehiculo->color)<span>{{ $vehiculo->color }}</span>@endif
        </div>
    </div>

    {{-- ── En taller ──────────────────────────────────────── --}}
    @if ($enTaller)
        <div class="qr-banner">
            <div class="qr-banner-icono">🔧</div>
            <div>
                <h2>Tu vehículo está en el taller</h2>
                <p class="qr-banner-estado">Estado: <strong>{{ $enTaller->estado->label() }}</strong></p>
            </div>
        </div>

        @if ($enTaller->diagnostico || $enTaller->trabajos->isNotEmpty() || $enTaller->refacciones->isNotEmpty())
            <div class="qr-card">
                <p class="qr-label">Servicio en curso</p>
                @include('qr._orden', ['orden' => $enTaller])
            </div>
        @endif
    @endif

    {{-- ── Próximo servicio ───────────────────────────────── --}}
    <div class="qr-card">
        <p class="qr-label">Próximo servicio</p>
        @if ($proximo)
            <p class="qr-proximo-nombre">{{ $proximo->descripcion }}</p>
            <p class="qr-proximo-meta">
                @if ($proximo->fecha_programada)
                    📅 <strong>{{ $proximo->fecha_programada->format('d/m/Y') }}</strong>
                @endif
                @if ($proximo->fecha_programada && $proximo->km_programado)
                    &ensp;o&ensp;
                @endif
                @if ($proximo->km_programado)
                    🛣 <strong>{{ number_format($proximo->km_programado) }} km</strong>
                @endif
                @if ($proximo->fecha_programada && $proximo->km_programado)
                    <br><span style="font-size:.8rem;">(lo que ocurra primero)</span>
                @endif
            </p>
        @else
            <p class="qr-vacio">Sin servicio programado.</p>
        @endif
    </div>

    {{-- ── Historial ──────────────────────────────────────── --}}
    <div class="qr-card" id="historial">
        <p class="qr-label">Historial de servicios</p>
        @forelse ($ordenes as $orden)
            <details class="qr-servicio">
                <summary>
                    <span class="qr-s-fecha">{{ ($orden->fecha_entrega ?? $orden->fecha_ingreso)->format('d/m/Y') }}</span>
                    <span class="qr-s-km">{{ number_format($orden->entrega?->km ?? $orden->recepcion?->km ?? 0) }} km</span>
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

</div>
@endsection
