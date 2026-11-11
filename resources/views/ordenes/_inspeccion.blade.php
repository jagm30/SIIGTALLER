{{-- Resumen de una inspección (recepción o entrega) dentro de la orden --}}
@if ($inspeccion->estaCompletada())
    <p><span class="etiqueta terminado">Completada</span> <span class="tenue chico">{{ $inspeccion->completada_at->format('d/m/Y H:i') }} · {{ $inspeccion->realizadaPor->name }}</span></p>
    @if ($inspeccion->esRecepcion())
        <p class="chico">
            <strong>Km:</strong> {{ number_format($inspeccion->km) }} ·
            <strong>Gasolina:</strong> {{ $inspeccion->combustibleTexto() }} ·
            <strong>Entregó:</strong> {{ $inspeccion->persona_nombre }}<br>
            <strong>Daños:</strong> {{ $inspeccion->sin_danios ? 'Sin daños visibles' : $inspeccion->danios->count() . ' registrados' }} ·
            <strong>Fotos:</strong> {{ $inspeccion->fotos->count() }}
            @if ($inspeccion->objetos_personales)<br><strong>Objetos que dejó:</strong> {{ $inspeccion->objetos_personales }}@endif
        </p>
        <p><a href="{{ route('inspecciones.pdf', $inspeccion) }}" class="boton chico secundario" target="_blank">Exportar acta en PDF</a></p>
        @if ($inspeccion->fotos->isNotEmpty())
            <div class="fotos">
                @foreach ($inspeccion->fotos->take(6) as $foto)
                    <figure>
                        <a href="{{ route('adjuntos.show', $foto) }}" target="_blank"><img src="{{ route('adjuntos.show', $foto) }}" alt="{{ $foto->anguloFoto?->nombre }}" loading="lazy"></a>
                        <figcaption>{{ $foto->anguloFoto?->nombre ?? 'Foto' }}</figcaption>
                    </figure>
                @endforeach
            </div>
        @endif
    @else
        <p class="chico"><strong>Observaciones finales:</strong> {{ $inspeccion->observaciones ?: 'Sin observaciones.' }}</p>
    @endif
@elseif ($inspeccion->esRecepcion())
    <p><span class="etiqueta por_autorizar">En captura</span> <span class="tenue chico">faltan {{ count($inspeccion->faltantes()) }} datos</span></p>
    @if (auth()->user()->tienePermiso($permiso))
        <a href="{{ route('inspecciones.edit', $inspeccion) }}" class="boton">Continuar captura</a>
    @endif
@else
    <p><span class="etiqueta por_autorizar">Pendiente</span> <span class="tenue chico">revisar con el cliente y completar</span></p>
    @if (auth()->user()->tienePermiso($permiso))
        <a href="{{ route('inspecciones.edit', $inspeccion) }}" class="boton">Continuar entrega</a>
    @endif
@endif
