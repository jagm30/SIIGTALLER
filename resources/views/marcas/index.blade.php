@extends('layouts.app')

@section('titulo', 'Marcas y modelos')

@push('styles')
<style>
    /* ── Marcas y modelos ──────────────────────────────────────────────── */
    .mi-hero {
        background: var(--tarjeta);
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .mi-hero-acento {
        height: 4px;
        background: linear-gradient(90deg, var(--primario) 0%, #5b9cf6 100%);
    }
    .mi-hero-cuerpo {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.1rem 1.2rem;
        flex-wrap: wrap;
    }
    .mi-hero-icono {
        flex-shrink: 0;
        width: 52px; height: 52px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--primario) 0%, #3b82e0 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        line-height: 1;
        user-select: none;
    }
    .mi-hero-cuerpo h1 { margin: 0 0 .2rem; font-size: 1.35rem; }
    .mi-chip { font-size: .875rem; color: var(--tenue); }

    /* Pendientes */
    .mi-pendientes {
        background: var(--tarjeta);
        border: 1px solid #f0d48a;
        border-radius: var(--radio);
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.04);
    }
    .mi-pendientes-acento {
        height: 4px;
        background: linear-gradient(90deg, #d97706 0%, #f0d48a 100%);
    }
    .mi-pendientes-cabeza {
        padding: .9rem 1.1rem .4rem;
    }
    .mi-pendientes-cabeza h2 {
        margin: 0 0 .2rem;
        font-size: 1rem;
        color: #92400e;
    }
    .mi-pendientes-cabeza p {
        margin: 0;
        font-size: .85rem;
        color: #a16207;
    }
    .mi-pendiente-fila {
        padding: .6rem 1.1rem;
        border-top: 1px solid #fde68a;
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }
    .mi-pendiente-fila:last-child { padding-bottom: .9rem; }

    /* Nueva marca */
    .mi-nueva-marca {
        display: flex;
        gap: .6rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .mi-nueva-marca input { flex: 1; min-width: 200px; }

    /* Acordeón de marcas */
    .mi-marca {
        background: var(--tarjeta);
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        margin-bottom: .5rem;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .mi-marca summary {
        padding: .75rem 1rem;
        cursor: pointer;
        list-style: none;
        display: flex;
        align-items: center;
        gap: .5rem;
        user-select: none;
    }
    .mi-marca summary::-webkit-details-marker { display: none; }
    .mi-marca summary::before {
        content: '▶';
        font-size: .65rem;
        color: var(--tenue);
        transition: transform .15s;
        flex-shrink: 0;
    }
    .mi-marca[open] summary::before { transform: rotate(90deg); }
    .mi-marca summary:hover { background: var(--fondo); }
    .mi-marca-nombre { font-weight: 600; }
    .mi-marca-count { font-size: .82rem; color: var(--tenue); margin-left: auto; }
    .mi-marca-cuerpo {
        padding: .5rem 1rem .85rem;
        border-top: 1px solid var(--borde);
    }
    .mi-modelos {
        display: flex;
        flex-wrap: wrap;
        gap: .3rem;
        margin-bottom: .75rem;
    }
    .mi-modelo-tag {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .78rem;
        padding: .15rem .5rem;
        border-radius: 999px;
        background: #e8f0fb;
        color: var(--primario);
        font-weight: 500;
    }
    .mi-modelo-tipo { font-weight: 400; color: var(--tenue); }
    .mi-add-modelo {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        align-items: center;
        margin-top: .5rem;
        padding-top: .6rem;
        border-top: 1px dashed var(--borde);
    }
    .mi-add-modelo input { flex: 1; min-width: 150px; }
    .mi-add-modelo select { width: auto; }

    @media (max-width: 540px) {
        .mi-hero-icono { width: 44px; height: 44px; font-size: 1.3rem; border-radius: 9px; }
        .mi-hero-cuerpo h1 { font-size: 1.15rem; }
    }
</style>
@endpush

@section('contenido')

@include('layouts._subnav', ['seccion' => 'catalogos'])

<div class="mi-hero">
    <div class="mi-hero-acento"></div>
    <div class="mi-hero-cuerpo">
        <div class="mi-hero-icono">🏷️</div>
        <div>
            <h1>Marcas y modelos</h1>
            <span class="mi-chip">{{ $marcas->count() }} {{ $marcas->count() === 1 ? 'marca' : 'marcas' }} registradas</span>
        </div>
    </div>
</div>

{{-- Modelos que recepción dio de alta rápida y hay que revisar --}}
@if ($pendientes->isNotEmpty())
    <div class="mi-pendientes">
        <div class="mi-pendientes-acento"></div>
        <div class="mi-pendientes-cabeza">
            <h2>⚠️ Por verificar ({{ $pendientes->count() }})</h2>
            <p>Corrige el nombre y confírmalo, o fusiónalo con el modelo correcto si está duplicado.</p>
        </div>
        @foreach ($pendientes as $m)
            <div class="mi-pendiente-fila">
                <form method="POST" action="{{ route('modelos.update', $m) }}" class="fila">
                    @csrf @method('PATCH')
                    <span class="tenue" style="min-width: 80px;">{{ $m->marca->nombre }}</span>
                    <input type="text" name="nombre" value="{{ $m->nombre }}" style="width: 160px;">
                    <select name="tipo_carroceria" style="width: auto;">
                        @foreach (\App\Enums\TipoCarroceria::opciones() as $valor => $texto)
                            <option value="{{ $valor }}" @selected($m->tipo_carroceria->value === $valor)>{{ $texto }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="activo" value="1">
                    <button class="boton chico">Confirmar</button>
                    <span class="tenue chico">{{ $m->vehiculos_count }} vehículo(s)</span>
                </form>
                <form method="POST" action="{{ route('modelos.fusionar', $m) }}" class="fila" onsubmit="return confirm('Los vehículos pasarán al modelo elegido y este se borrará. ¿Continuar?')">
                    @csrf
                    <select name="destino_id" style="flex: 1;">
                        <option value="">Fusionar con…</option>
                        @foreach ($marcas as $marca)
                            <optgroup label="{{ $marca->nombre }}">
                                @foreach ($marca->modelos->where('id', '!=', $m->id)->where('verificado', true) as $destino)
                                    <option value="{{ $destino->id }}">{{ $marca->nombre }} {{ $destino->nombre }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <button class="boton chico secundario">Fusionar</button>
                </form>
            </div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('marcas.store') }}" class="tarjeta">
    <div class="mi-nueva-marca">
        @csrf
        <input type="text" name="nombre" placeholder="Nueva marca…" required>
        <button class="boton">+ Agregar marca</button>
    </div>
</form>

@foreach ($marcas as $marca)
    <details class="mi-marca">
        <summary>
            <span class="mi-marca-nombre">{{ $marca->nombre }}</span>
            <span class="mi-marca-count">{{ $marca->modelos->count() }} {{ $marca->modelos->count() === 1 ? 'modelo' : 'modelos' }}</span>
        </summary>
        <div class="mi-marca-cuerpo">
            @if ($marca->modelos->isNotEmpty())
                <div class="mi-modelos">
                    @foreach ($marca->modelos as $m)
                        <span class="mi-modelo-tag">
                            {{ $m->nombre }}
                            <span class="mi-modelo-tipo">· {{ $m->tipo_carroceria->label() }}</span>
                        </span>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('modelos.store', $marca) }}" class="mi-add-modelo">
                @csrf
                <input type="text" name="nombre" placeholder="Nuevo modelo…" required>
                <select name="tipo_carroceria" required>
                    @foreach (\App\Enums\TipoCarroceria::opciones() as $valor => $texto)
                        <option value="{{ $valor }}">{{ $texto }}</option>
                    @endforeach
                </select>
                <button class="boton chico">Agregar</button>
            </form>
        </div>
    </details>
@endforeach

@endsection
