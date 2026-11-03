@extends('layouts.app')

@section('titulo', 'Servicios')

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'catalogos'])
    <div class="encabezado">
        <h1>Servicios</h1>
        <a href="{{ route('servicios.create') }}" class="boton">+ Nuevo servicio</a>
    </div>

    <form method="GET" class="tarjeta fila">
        <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar servicio" style="flex: 1;">
        <button class="boton secundario">Buscar</button>
    </form>

    <div class="tarjeta tabla-scroll">
        <table>
            <thead><tr><th>Servicio</th><th>Cobro</th>@can('precios.ver')<th class="derecha">Precio</th>@endcan<th class="ocultar-movil">Próximo servicio</th><th></th></tr></thead>
            <tbody>
            @forelse ($servicios as $s)
                <tr @if (! $s->activo) class="tenue" @endif>
                    <td>{{ $s->nombre }} @if (! $s->activo)<span class="etiqueta">inactivo</span>@endif<br><span class="tenue chico">{{ $s->clave }} {{ $s->categoria }}</span></td>
                    <td>{{ $s->tipo_cobro->label() }}</td>
                    @can('precios.ver')<td class="derecha">{{ $s->precio !== null ? '$' . number_format($s->precio, 2) : '—' }}@if ($s->tipo_cobro === \App\Enums\TipoCobro::PorHora && $s->precio !== null)/h @endif</td>@endcan
                    <td class="ocultar-movil chico">
                        @if ($s->generaRecordatorio())
                            {{ $s->intervalo_meses ? $s->intervalo_meses . ' meses' : '' }}{{ $s->intervalo_meses && $s->intervalo_km ? ' o ' : '' }}{{ $s->intervalo_km ? number_format($s->intervalo_km) . ' km' : '' }}
                        @else — @endif
                    </td>
                    <td class="derecha"><a href="{{ route('servicios.edit', $s) }}">Editar</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="tenue">Sin servicios.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="paginacion">{{ $servicios->links('pagination::default') }}</div>
    </div>
@endsection
