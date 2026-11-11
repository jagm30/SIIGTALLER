@extends('layouts.app')

@section('titulo', 'Entrega del vehículo · ' . $orden->folio)

{{-- La entrega no repite el checklist: se revisa con el cliente lo registrado al recibir y se anotan observaciones finales. --}}
@section('contenido')
    <div class="encabezado">
        <div>
            <h1>Entrega del vehículo</h1>
            <p class="tenue">Orden {{ $orden->folio }} · {{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo->nombre }}
                · {{ $orden->vehiculo->placas ?? 'Sin placas' }} · {{ $orden->cliente->nombre_completo }}</p>
        </div>
        <a href="{{ route('ordenes.show', $orden) }}" class="boton secundario">Ver orden</a>
    </div>

    <div class="tarjeta">
        <div class="encabezado" style="margin-bottom: .5rem;">
            <h2 style="margin: 0;">Reporte de recepción</h2>
            @if ($recepcion?->estaCompletada())
                <a href="{{ route('inspecciones.pdf', $recepcion) }}" class="boton chico secundario" target="_blank">Acta de recepción en PDF</a>
            @endif
        </div>

        @if ($recepcion?->estaCompletada())
            <p class="chico">
                <strong>Recibido:</strong> {{ $recepcion->completada_at->format('d/m/Y H:i') }} por {{ $recepcion->realizadaPor->name }} ·
                <strong>Km:</strong> {{ number_format($recepcion->km) }} ·
                <strong>Gasolina:</strong> {{ $recepcion->combustibleTexto() }} ·
                <strong>Entregó:</strong> {{ $recepcion->persona_nombre }}
            </p>
            @if ($recepcion->objetos_personales)
                <p class="aviso alerta"><strong>Objetos que dejó el cliente (devuélvelos):</strong> {{ $recepcion->objetos_personales }}</p>
            @endif
            @if ($recepcion->observaciones)
                <p class="chico"><strong>Observaciones al recibir:</strong> {{ $recepcion->observaciones }}</p>
            @endif

            <h3>Checklist</h3>
            <div class="reporte-checklist">
                @foreach ($recepcion->checklistPorGrupo() as $grupo => $respuestas)
                    <table>
                        <tr><th colspan="2">{{ $grupo }}</th></tr>
                        @foreach ($respuestas as $r)
                            <tr>
                                <td>{{ $r->item_nombre }}@if ($r->comentario)<br><span class="tenue chico">{{ $r->comentario }}</span>@endif</td>
                                <td @class(['valor', 'malo' => $r->valor === 'malo'])>{{ $r->checklistItem?->tipo_respuesta->valores()[$r->valor] ?? $r->valor }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endforeach
            </div>

            <h3>Daños al recibir</h3>
            @forelse ($recepcion->danios as $d)
                <p class="chico" style="margin: .2rem 0;">• {{ $d->tipo->label() }} {{ strtolower($d->severidad->label()) }} en {{ $d->zona?->nombre ?? $d->vista->label() }}
                    @if ($d->medida_cm) ({{ $d->medida_cm }} cm) @endif @if ($d->descripcion) — {{ $d->descripcion }} @endif</p>
            @empty
                <p class="chico">{{ $recepcion->sin_danios ? 'Sin daños visibles.' : 'No se registraron daños.' }}</p>
            @endforelse

            @if ($recepcion->fotos->isNotEmpty())
                <h3>Fotos al recibir</h3>
                <div class="fotos">
                    @foreach ($recepcion->fotos as $foto)
                        <figure>
                            <a href="{{ route('adjuntos.show', $foto) }}" target="_blank"><img src="{{ route('adjuntos.show', $foto) }}" alt="{{ $foto->anguloFoto?->nombre }}" loading="lazy"></a>
                            <figcaption>{{ $foto->anguloFoto?->nombre ?? 'Foto' }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif
        @else
            <p class="tenue">Esta orden no tiene una recepción completada.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('inspecciones.update', $entrega) }}" class="tarjeta">
        @csrf @method('PUT')
        <h2>Observaciones finales</h2>
        <div class="campo">
            <label for="observaciones">Lo que se comentó con el cliente al entregar (opcional)</label>
            <textarea id="observaciones" name="observaciones" rows="4" placeholder="Ej. Se le explicó el cambio de balatas; regresar en 10,000 km.">{{ old('observaciones', $entrega->observaciones) }}</textarea>
        </div>
        <div class="fila">
            <button class="boton secundario" name="completar" value="0">Guardar</button>
            <button class="boton exito" name="completar" value="1">Completar entrega</button>
        </div>
    </form>
@endsection
