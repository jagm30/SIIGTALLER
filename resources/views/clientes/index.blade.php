@extends('layouts.app')

@section('titulo', 'Clientes')

@push('styles')
<style>
    /* ── Índice de clientes ────────────────────────────────────────────── */
    .ci-hero {
        background: var(--tarjeta);
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .ci-hero-acento {
        height: 4px;
        background: linear-gradient(90deg, var(--primario) 0%, #5b9cf6 100%);
    }
    .ci-hero-cuerpo {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.1rem 1.2rem;
        flex-wrap: wrap;
    }
    .ci-hero-izq {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }
    .ci-hero-icono {
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
    .ci-hero-cuerpo h1 { margin: 0 0 .2rem; font-size: 1.35rem; }
    .ci-hero-chips {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .3rem .6rem;
    }
    .ci-chip { font-size: .875rem; color: var(--tenue); }
    .ci-chip-filtro {
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
    .ci-filtros {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        align-items: flex-end;
    }
    .ci-filtros input[type=search] { flex: 1; min-width: 220px; }
    .ci-nombre-link {
        font-weight: 600;
        color: var(--primario);
        text-decoration: none;
    }
    .ci-nombre-link:hover { text-decoration: underline; }
    .ci-veh-badge {
        display: inline-block;
        font-size: .78rem;
        font-weight: 700;
        padding: .1rem .45rem;
        border-radius: 999px;
        background: #e8f0fb;
        color: var(--primario);
    }
    .ci-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--tenue);
    }
    .ci-empty-icon { font-size: 2rem; display: block; margin-bottom: .5rem; opacity: .35; }
    @media (max-width: 540px) {
        .ci-hero-icono { width: 44px; height: 44px; font-size: 1.3rem; border-radius: 9px; }
        .ci-hero-cuerpo h1 { font-size: 1.15rem; }
    }
</style>
@endpush

@section('contenido')

<div class="ci-hero">
    <div class="ci-hero-acento"></div>
    <div class="ci-hero-cuerpo">
        <div class="ci-hero-izq">
            <div class="ci-hero-icono">👥</div>
            <div>
                <h1>Clientes</h1>
                <div class="ci-hero-chips">
                    <span class="ci-chip">{{ $clientes->total() }} {{ $clientes->total() === 1 ? 'resultado' : 'resultados' }}</span>
                    @if ($buscar)
                        <span class="ci-chip-filtro">🔍 {{ $buscar }}</span>
                    @endif
                </div>
            </div>
        </div>
        <a href="{{ route('clientes.create') }}" class="boton">+ Nuevo cliente</a>
    </div>
</div>

<form method="GET" class="tarjeta">
    <div class="ci-filtros">
        <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre, teléfono o RFC…">
        <button class="boton secundario">Buscar</button>
        @if ($buscar)
            <a href="{{ route('clientes.index') }}" class="boton secundario">Limpiar</a>
        @endif
    </div>
</form>

<div class="tarjeta">
    @if ($clientes->isEmpty())
        <div class="ci-empty">
            <span class="ci-empty-icon">👥</span>
            No se encontraron clientes.
        </div>
    @else
        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th class="ocultar-movil">RFC</th>
                        <th class="derecha">Vehículos</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($clientes as $cliente)
                    <tr>
                        <td>
                            <a href="{{ route('clientes.show', $cliente) }}" class="ci-nombre-link">
                                {{ $cliente->nombre_completo }}
                            </a>
                        </td>
                        <td>{{ $cliente->telefono }}</td>
                        <td class="ocultar-movil tenue">{{ $cliente->rfc ?? '—' }}</td>
                        <td class="derecha">
                            @if ($cliente->vehiculos_count > 0)
                                <span class="ci-veh-badge">{{ $cliente->vehiculos_count }}</span>
                            @else
                                <span class="tenue">0</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="paginacion">{{ $clientes->links('pagination::default') }}</div>
    @endif
</div>

@endsection
