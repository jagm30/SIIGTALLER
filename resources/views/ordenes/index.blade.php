@extends('layouts.app')

@section('titulo', 'Órdenes')

@push('styles')
<style>
    /* ── Índice de órdenes ─────────────────────────────────────────────── */
    .oi-hero {
        background: var(--tarjeta);
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .oi-hero-acento {
        height: 4px;
        background: linear-gradient(90deg, var(--primario) 0%, #5b9cf6 100%);
    }
    .oi-hero-cuerpo {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.1rem 1.2rem;
        flex-wrap: wrap;
    }
    .oi-hero-izq {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }
    .oi-hero-icono {
        flex-shrink: 0;
        width: 52px;
        height: 52px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--primario) 0%, #3b82e0 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        line-height: 1;
        user-select: none;
    }
    .oi-hero-cuerpo h1 { margin: 0 0 .2rem; font-size: 1.35rem; }
    .oi-hero-chips {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .3rem .6rem;
    }
    .oi-chip {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .875rem;
        color: var(--tenue);
    }
    .oi-chip-filtro {
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

    /* Filtros */
    .oi-filtros {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        align-items: flex-end;
    }
    .oi-filtros input[type=search] { flex: 1; min-width: 200px; }
    .oi-filtros select { width: auto; min-width: 180px; }

    /* Tabla */
    .oi-folio {
        font-family: ui-monospace, "Cascadia Code", Consolas, monospace;
        font-size: .9rem;
        font-weight: 700;
        color: var(--primario);
        text-decoration: none;
        white-space: nowrap;
    }
    .oi-folio:hover { text-decoration: underline; }
    .oi-fecha { font-size: .875rem; color: var(--tenue); white-space: nowrap; }
    .oi-tabla tr.atrasada td { background: #fff9f9; }
    .oi-tabla tr.atrasada td:first-child {
        border-left: 3px solid var(--peligro);
        padding-left: calc(.5rem - 3px);
    }
    .oi-tabla tr.atrasada:hover td { background: #fff2f2; }
    .oi-promesa {
        display: block;
        font-size: .72rem;
        font-weight: 700;
        color: var(--peligro);
        margin-top: .1rem;
        white-space: nowrap;
    }
    .oi-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--tenue);
    }
    .oi-empty-icon {
        font-size: 2rem;
        display: block;
        margin-bottom: .5rem;
        opacity: .35;
    }

    @media (max-width: 540px) {
        .oi-hero-icono { width: 44px; height: 44px; font-size: 1.3rem; border-radius: 9px; }
        .oi-hero-cuerpo h1 { font-size: 1.15rem; }
        .oi-filtros select { min-width: 0; width: 100%; }
    }
</style>
@endpush

@section('contenido')

{{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
<div class="oi-hero">
    <div class="oi-hero-acento"></div>
    <div class="oi-hero-cuerpo">
        <div class="oi-hero-izq">
            <div class="oi-hero-icono">📋</div>
            <div>
                <h1>Órdenes de servicio</h1>
                <div class="oi-hero-chips">
                    <span class="oi-chip">
                        {{ $ordenes->total() }} {{ $ordenes->total() === 1 ? 'resultado' : 'resultados' }}
                    </span>
                    @if ($buscar)
                        <span class="oi-chip-filtro">🔍 {{ $buscar }}</span>
                    @endif
                    @if ($estado)
                        <span class="etiqueta {{ $estado->value }}" style="font-size:.78rem;">{{ $estado->label() }}</span>
                    @endif
                </div>
            </div>
        </div>
        @can('recepcion.realizar')
            <a href="{{ route('ordenes.create') }}" class="boton">+ Recibir vehículo</a>
        @endcan
    </div>
</div>

{{-- ── Filtros ─────────────────────────────────────────────────────────── --}}
<form method="GET" class="tarjeta">
    <div class="oi-filtros">
        <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar por folio o placas…">
        <select name="estado">
            <option value="">Todos los estados</option>
            @foreach (\App\Enums\EstadoOrden::enUsoTodos() as $e)
                <option value="{{ $e->value }}" @selected($estado === $e)>{{ $e->label() }}</option>
            @endforeach
        </select>
        <button class="boton secundario">Filtrar</button>
        @if ($buscar || $estado)
            <a href="{{ route('ordenes.index') }}" class="boton secundario">Limpiar</a>
        @endif
    </div>
</form>

{{-- ── Tabla ───────────────────────────────────────────────────────────── --}}
<div class="tarjeta">
    @if ($ordenes->isEmpty())
        <div class="oi-empty">
            <span class="oi-empty-icon">📋</span>
            No hay órdenes con esos filtros.
        </div>
    @else
        <div class="tabla-scroll">
            <table class="oi-tabla">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Ingreso</th>
                        <th>Vehículo</th>
                        <th class="ocultar-movil">Cliente</th>
                        <th>Estado</th>
                        <th class="ocultar-movil">Mecánico</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($ordenes as $orden)
                @php $atrasada = $orden->fecha_promesa?->isPast() && ! $orden->estaCerrada(); @endphp
                <tr @class(['atrasada' => $atrasada])>
                    <td>
                        <a href="{{ route('ordenes.show', $orden) }}" class="oi-folio">{{ $orden->folio }}</a>
                    </td>
                    <td class="oi-fecha">{{ $orden->fecha_ingreso->format('d/m/Y') }}</td>
                    <td>
                        {{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo->nombre }}
                        <br><span class="tenue chico">{{ $orden->vehiculo->placas ?? 'Sin placas' }}</span>
                    </td>
                    <td class="ocultar-movil">{{ $orden->cliente->nombre_completo }}</td>
                    <td>
                        <span class="etiqueta {{ $orden->estado->value }}">{{ $orden->estado->label() }}</span>
                        @if ($atrasada)
                            <span class="oi-promesa">⚠ {{ $orden->fecha_promesa->format('d/m') }}</span>
                        @endif
                    </td>
                    <td class="ocultar-movil">{{ $orden->mecanicoResponsable?->name ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="paginacion">{{ $ordenes->links('pagination::default') }}</div>
    @endif
</div>

@endsection
