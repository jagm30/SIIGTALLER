@extends('layouts.app')

@section('titulo', 'Vehículos')

@push('styles')
<style>
    /* ── Índice de vehículos ───────────────────────────────────────────── */
    .vi-hero {
        background: var(--tarjeta);
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .vi-hero-acento {
        height: 4px;
        background: linear-gradient(90deg, var(--primario) 0%, #5b9cf6 100%);
    }
    .vi-hero-cuerpo {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.1rem 1.2rem;
        flex-wrap: wrap;
    }
    .vi-hero-izq {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }
    .vi-hero-icono {
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
    .vi-hero-cuerpo h1 { margin: 0 0 .2rem; font-size: 1.35rem; }
    .vi-hero-chips {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .3rem .6rem;
    }
    .vi-chip { font-size: .875rem; color: var(--tenue); }
    .vi-chip-filtro {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .78rem;
        font-weight: 600;
        padding: .15rem .55rem;
        border-radius: 999px;
        background: #e8f0fb;
        color: var(--primario);
    }
    .vi-filtros {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        align-items: flex-end;
    }
    .vi-filtros input[type=search] { flex: 1; min-width: 220px; }
    .vi-veh-link {
        font-weight: 600;
        color: var(--primario);
        text-decoration: none;
    }
    .vi-veh-link:hover { text-decoration: underline; }
    .vi-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--tenue);
    }
    .vi-empty-icon { font-size: 2rem; display: block; margin-bottom: .5rem; opacity: .35; }
    @media (max-width: 540px) {
        .vi-hero-icono { width: 44px; height: 44px; font-size: 1.3rem; border-radius: 9px; }
        .vi-hero-cuerpo h1 { font-size: 1.15rem; }
    }
</style>
@endpush

@section('contenido')

<div class="vi-hero">
    <div class="vi-hero-acento"></div>
    <div class="vi-hero-cuerpo">
        <div class="vi-hero-izq">
            <div class="vi-hero-icono">🚗</div>
            <div>
                <h1>Vehículos</h1>
                <div class="vi-hero-chips">
                    <span class="vi-chip">{{ $vehiculos->total() }} {{ $vehiculos->total() === 1 ? 'resultado' : 'resultados' }}</span>
                    @if ($buscar)
                        <span class="vi-chip-filtro">🔍 {{ $buscar }}</span>
                    @endif
                </div>
            </div>
        </div>
        <a href="{{ route('vehiculos.create') }}" class="boton">+ Nuevo vehículo</a>
    </div>
</div>

<form method="GET" class="tarjeta">
    <div class="vi-filtros">
        <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar por placas, VIN o cliente…">
        <button class="boton secundario">Buscar</button>
        @if ($buscar)
            <a href="{{ route('vehiculos.index') }}" class="boton secundario">Limpiar</a>
        @endif
    </div>
</form>

<div class="tarjeta">
    @if ($vehiculos->isEmpty())
        <div class="vi-empty">
            <span class="vi-empty-icon">🚗</span>
            No se encontraron vehículos.
        </div>
    @else
        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Placas</th>
                        <th class="ocultar-movil">VIN</th>
                        <th>Dueño</th>
                        <th class="ocultar-movil derecha">Km</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($vehiculos as $v)
                    <tr>
                        <td>
                            <a href="{{ route('vehiculos.show', $v) }}" class="vi-veh-link">
                                {{ $v->marca->nombre }} {{ $v->modelo->nombre }} {{ $v->anio }}
                            </a>
                        </td>
                        <td>{{ $v->placas ?? '—' }}</td>
                        <td class="ocultar-movil chico tenue">{{ $v->vin ?? '—' }}</td>
                        <td>{{ $v->cliente->nombre_completo }}</td>
                        <td class="ocultar-movil derecha">{{ number_format($v->km_actual) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="paginacion">{{ $vehiculos->links('pagination::default') }}</div>
    @endif
</div>

@endsection
