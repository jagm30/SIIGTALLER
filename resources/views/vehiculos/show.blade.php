@extends('layouts.app')

@section('titulo', $vehiculo->marca->nombre . ' ' . $vehiculo->modelo->nombre)

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>{{ $vehiculo->marca->nombre }} {{ $vehiculo->modelo->nombre }} {{ $vehiculo->anio }}</h1>
            <p class="tenue">{{ $vehiculo->placas ?? 'Sin placas' }} · {{ $vehiculo->color }} · {{ number_format($vehiculo->km_actual) }} km</p>
        </div>
        <div class="fila">
            @can('recepcion.realizar')
                <a href="{{ route('ordenes.create', ['vehiculo_id' => $vehiculo->id]) }}" class="boton">Recibir en taller</a>
            @endcan
            <a href="{{ route('vehiculos.edit', $vehiculo) }}" class="boton secundario">Editar</a>
        </div>
    </div>

    <div class="rejilla-2">
        <div class="tarjeta">
            <h2>Datos</h2>
            <p><strong>Dueño:</strong> <a href="{{ route('clientes.show', $vehiculo->cliente) }}">{{ $vehiculo->cliente->nombre_completo }}</a> · {{ $vehiculo->cliente->telefono }}<br>
               <strong>VIN:</strong> {{ $vehiculo->vin ?? '—' }}<br>
               <strong>Versión:</strong> {{ $vehiculo->version ?? '—' }}<br>
               <strong>Tipo:</strong> {{ $vehiculo->modelo->tipo_carroceria->label() }}
               @if (! $vehiculo->modelo->verificado) <span class="etiqueta">modelo por verificar</span> @endif</p>
        </div>

        <div class="tarjeta">
            <h2>Próximo servicio</h2>
            @forelse ($vehiculo->recordatorios as $r)
                <p><strong>{{ $r->descripcion }}</strong><br>
                    <span class="tenue">
                        @if ($r->fecha_programada) {{ $r->fecha_programada->format('d/m/Y') }} @endif
                        @if ($r->fecha_programada && $r->km_programado) o @endif
                        @if ($r->km_programado) a los {{ number_format($r->km_programado) }} km @endif
                        (lo que ocurra primero)
                    </span></p>
            @empty
                <p class="tenue">Sin servicios programados.</p>
            @endforelse

            <h3>Página del cliente (QR)</h3>
            @if ($vehiculo->qr_activo)
                <p class="chico"><a href="{{ route('qr.show', $vehiculo->qr_token) }}" target="_blank">{{ route('qr.show', $vehiculo->qr_token) }}</a></p>
            @else
                <p class="tenue">El QR de este vehículo está desactivado.</p>
            @endif
            @can('qr.regenerar')
                <form method="POST" action="{{ route('vehiculos.qr', $vehiculo) }}" onsubmit="return confirm('La etiqueta impresa actual dejará de funcionar. ¿Continuar?')">
                    @csrf
                    <button class="boton chico secundario">Generar QR nuevo</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="tarjeta">
        <h2>Historial de servicio</h2>
        @forelse ($ordenes as $orden)
            <div style="border-bottom: 1px solid var(--borde); padding: .5rem 0;">
                <a href="{{ route('ordenes.show', $orden) }}"><strong>{{ $orden->folio }}</strong></a>
                · {{ $orden->fecha_ingreso->format('d/m/Y') }}
                · <span class="etiqueta {{ $orden->estado->value }}">{{ $orden->estado->label() }}</span>
                <p class="chico" style="margin: .25rem 0;">{{ $orden->motivo_ingreso }}</p>
                @if ($orden->trabajos->isNotEmpty())
                    <p class="chico tenue" style="margin: 0;">Trabajos: {{ $orden->trabajos->pluck('descripcion')->join(', ') }}</p>
                @endif
            </div>
        @empty
            <p class="tenue">Este vehículo no ha tenido servicios.</p>
        @endforelse
        <div class="paginacion">{{ $ordenes->links('pagination::default') }}</div>
    </div>
@endsection
