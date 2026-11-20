@extends('layouts.app')

@section('titulo', 'Servicios')

@push('styles')
<style>
    /* ── Índice de servicios ───────────────────────────────────────────── */
    .si-hero {
        background: var(--tarjeta);
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .si-hero-acento {
        height: 4px;
        background: linear-gradient(90deg, var(--primario) 0%, #5b9cf6 100%);
    }
    .si-hero-cuerpo {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.1rem 1.2rem;
        flex-wrap: wrap;
    }
    .si-hero-izq {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }
    .si-hero-icono {
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
    .si-hero-cuerpo h1 { margin: 0 0 .2rem; font-size: 1.35rem; }
    .si-hero-chips {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .3rem .6rem;
    }
    .si-chip { font-size: .875rem; color: var(--tenue); }
    .si-chip-filtro {
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
    .si-filtros {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        align-items: flex-end;
    }
    .si-filtros input[type=search] { flex: 1; min-width: 220px; }
    .si-inactivo td { color: var(--tenue); }
    .si-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--tenue);
    }
    .si-empty-icon { font-size: 2rem; display: block; margin-bottom: .5rem; opacity: .35; }
    @media (max-width: 540px) {
        .si-hero-icono { width: 44px; height: 44px; font-size: 1.3rem; border-radius: 9px; }
        .si-hero-cuerpo h1 { font-size: 1.15rem; }
    }
</style>
@endpush

@section('contenido')

@include('layouts._subnav', ['seccion' => 'catalogos'])

@php $buscar = request('buscar', ''); @endphp

<div class="si-hero">
    <div class="si-hero-acento"></div>
    <div class="si-hero-cuerpo">
        <div class="si-hero-izq">
            <div class="si-hero-icono">⚙️</div>
            <div>
                <h1>Servicios</h1>
                <div class="si-hero-chips">
                    <span class="si-chip">{{ $servicios->total() }} {{ $servicios->total() === 1 ? 'resultado' : 'resultados' }}</span>
                    @if ($buscar)
                        <span class="si-chip-filtro">🔍 {{ $buscar }}</span>
                    @endif
                </div>
            </div>
        </div>
        <a href="{{ route('servicios.create') }}" class="boton">+ Nuevo servicio</a>
    </div>
</div>

<form method="GET" class="tarjeta">
    <div class="si-filtros">
        <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar servicio…">
        <button class="boton secundario">Buscar</button>
        @if ($buscar)
            <a href="{{ route('servicios.index') }}" class="boton secundario">Limpiar</a>
        @endif
    </div>
</form>

<div class="tarjeta">
    @if ($servicios->isEmpty())
        <div class="si-empty">
            <span class="si-empty-icon">⚙️</span>
            Sin servicios registrados.
        </div>
    @else
        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Cobro</th>
                        @can('precios.ver')<th class="derecha">Precio</th>@endcan
                        <th class="ocultar-movil">Próximo servicio</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($servicios as $s)
                    <tr @class(['si-inactivo' => ! $s->activo])>
                        <td>
                            {{ $s->nombre }}
                            @if (! $s->activo)<span class="etiqueta">inactivo</span>@endif
                            <br>
                            <span class="tenue chico">{{ $s->clave }} {{ $s->categoria }}</span>
                        </td>
                        <td>{{ $s->tipo_cobro->label() }}</td>
                        @can('precios.ver')
                            <td class="derecha">
                                {{ $s->precio !== null ? '$' . number_format($s->precio, 2) : '—' }}@if ($s->tipo_cobro === \App\Enums\TipoCobro::PorHora && $s->precio !== null)/h @endif
                            </td>
                        @endcan
                        <td class="ocultar-movil chico">
                            @php([$meses, $km] = $s->intervaloRecordatorio())
                            @if ($meses || $km)
                                {{ $meses ? $meses . ' meses' : '' }}{{ $meses && $km ? ' o ' : '' }}{{ $km ? number_format($km) . ' km' : '' }}
                                @unless ($s->tieneIntervaloPropio())<br><span class="tenue">(general)</span>@endunless
                            @else
                                —
                            @endif
                        </td>
                        <td class="derecha">
                            <a href="{{ route('servicios.edit', $s) }}" class="tenue chico">Editar</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="paginacion">{{ $servicios->links('pagination::default') }}</div>
    @endif
</div>

@endsection
