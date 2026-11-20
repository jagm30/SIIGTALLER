@extends('layouts.app')

@section('titulo', 'Refacciones')

@push('styles')
<style>
    /* ── Índice de refacciones ─────────────────────────────────────────── */
    .ri-hero {
        background: var(--tarjeta);
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .ri-hero-acento {
        height: 4px;
        background: linear-gradient(90deg, var(--primario) 0%, #5b9cf6 100%);
    }
    .ri-hero-cuerpo {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.1rem 1.2rem;
        flex-wrap: wrap;
    }
    .ri-hero-izq {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }
    .ri-hero-icono {
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
    .ri-hero-cuerpo h1 { margin: 0 0 .2rem; font-size: 1.35rem; }
    .ri-hero-chips {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .3rem .6rem;
    }
    .ri-chip { font-size: .875rem; color: var(--tenue); }
    .ri-chip-filtro {
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
    .ri-filtros {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        align-items: flex-end;
    }
    .ri-filtros input[type=search] { flex: 1; min-width: 220px; }
    .ri-inactivo td { color: var(--tenue); }
    .ri-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--tenue);
    }
    .ri-empty-icon { font-size: 2rem; display: block; margin-bottom: .5rem; opacity: .35; }
    @media (max-width: 540px) {
        .ri-hero-icono { width: 44px; height: 44px; font-size: 1.3rem; border-radius: 9px; }
        .ri-hero-cuerpo h1 { font-size: 1.15rem; }
    }
</style>
@endpush

@section('contenido')

@include('layouts._subnav', ['seccion' => 'catalogos'])

@php $buscar = request('buscar', ''); @endphp

<div class="ri-hero">
    <div class="ri-hero-acento"></div>
    <div class="ri-hero-cuerpo">
        <div class="ri-hero-izq">
            <div class="ri-hero-icono">🔩</div>
            <div>
                <h1>Refacciones</h1>
                <div class="ri-hero-chips">
                    <span class="ri-chip">{{ $refacciones->total() }} {{ $refacciones->total() === 1 ? 'resultado' : 'resultados' }}</span>
                    @if ($buscar)
                        <span class="ri-chip-filtro">🔍 {{ $buscar }}</span>
                    @endif
                </div>
            </div>
        </div>
        <a href="{{ route('refacciones.create') }}" class="boton">+ Nueva refacción</a>
    </div>
</div>

<form method="GET" class="tarjeta">
    <div class="ri-filtros">
        <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Descripción o número de parte…">
        <button class="boton secundario">Buscar</button>
        @if ($buscar)
            <a href="{{ route('refacciones.index') }}" class="boton secundario">Limpiar</a>
        @endif
    </div>
</form>

<div class="tarjeta">
    @if ($refacciones->isEmpty())
        <div class="ri-empty">
            <span class="ri-empty-icon">🔩</span>
            Sin refacciones registradas.
        </div>
    @else
        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Refacción</th>
                        <th class="ocultar-movil">Marca</th>
                        <th>Unidad</th>
                        @can('costos.ver')<th class="derecha ocultar-movil">Costo</th>@endcan
                        @can('precios.ver')<th class="derecha">Precio</th>@endcan
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($refacciones as $r)
                    <tr @class(['ri-inactivo' => ! $r->activo])>
                        <td>
                            {{ $r->descripcion }}
                            @if (! $r->activo)<span class="etiqueta">inactiva</span>@endif
                            <br>
                            <span class="tenue chico">{{ $r->numero_parte }}</span>
                        </td>
                        <td class="ocultar-movil">{{ $r->marca ?? '—' }}</td>
                        <td>{{ $r->unidad->label() }}</td>
                        @can('costos.ver')
                            <td class="derecha ocultar-movil">{{ $r->costo !== null ? '$' . number_format($r->costo, 2) : '—' }}</td>
                        @endcan
                        @can('precios.ver')
                            <td class="derecha">{{ $r->precio_venta !== null ? '$' . number_format($r->precio_venta, 2) : '—' }}</td>
                        @endcan
                        <td class="derecha"><a href="{{ route('refacciones.edit', $r) }}" class="tenue chico">Editar</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="paginacion">{{ $refacciones->links('pagination::default') }}</div>
    @endif
</div>

@endsection
