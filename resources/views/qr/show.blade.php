@extends('layouts.app', ['publico' => true])

@section('titulo', $vehiculo->marca->nombre . ' ' . $vehiculo->modelo->nombre)

{{-- Página pública: NUNCA mostrar nombre, teléfono ni correo del dueño. --}}
@section('contenido')
    <div style="max-width: 640px; margin: 0 auto;">
        <div class="tarjeta">
            <p class="tenue chico">🔧 {{ config('app.name') }}</p>
            <h1>{{ $vehiculo->marca->nombre }} {{ $vehiculo->modelo->nombre }} {{ $vehiculo->anio }}</h1>
            <p class="tenue">{{ $vehiculo->placas ?? '' }} {{ $vehiculo->color ? '· ' . $vehiculo->color : '' }}</p>
        </div>

        @if ($enTaller)
            <div class="aviso alerta">Tu vehículo está en el taller: <strong>{{ $enTaller->estado->label() }}</strong>.</div>
        @endif

        <div class="tarjeta">
            <h2>Próximo servicio</h2>
            @if ($proximo)
                <p><strong>{{ $proximo->descripcion }}</strong><br>
                    @if ($proximo->fecha_programada) el {{ $proximo->fecha_programada->format('d/m/Y') }} @endif
                    @if ($proximo->fecha_programada && $proximo->km_programado) o @endif
                    @if ($proximo->km_programado) a los {{ number_format($proximo->km_programado) }} km @endif
                    <span class="tenue">(lo que ocurra primero)</span></p>
            @else
                <p class="tenue">Sin servicio programado.</p>
            @endif
        </div>

        <div class="tarjeta">
            <h2>Servicios realizados</h2>
            @forelse ($ordenes as $orden)
                <div style="border-bottom: 1px solid var(--borde); padding: .6rem 0;">
                    <strong>{{ ($orden->fecha_entrega ?? $orden->fecha_ingreso)->format('d/m/Y') }}</strong>
                    <span class="tenue">· {{ number_format($orden->entrega?->km ?? $orden->recepcion?->km ?? 0) }} km</span>
                    <ul style="margin: .25rem 0 0; padding-left: 1.2rem;">
                        @foreach ($orden->trabajos as $t)<li>{{ $t->descripcion }}</li>@endforeach
                        @foreach ($orden->refacciones as $r)<li class="tenue">{{ $r->descripcion }} ({{ (float) $r->cantidad }})</li>@endforeach
                    </ul>
                    @if ($mostrarPrecios && $orden->total !== null)
                        <p class="chico" style="margin: .25rem 0 0;">Total: ${{ number_format($orden->total, 2) }}</p>
                    @endif
                </div>
            @empty
                <p class="tenue">Aún no hay servicios registrados.</p>
            @endforelse
        </div>
    </div>
@endsection
