@extends('layouts.app')

@section('titulo', 'Entrega del vehículo · ' . $orden->folio)

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>Entrega del vehículo</h1>
            <p class="tenue">Orden {{ $orden->folio }} · {{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo->nombre }}
                · {{ $orden->vehiculo->placas ?? 'Sin placas' }} · {{ $orden->cliente->nombre_completo }}</p>
        </div>
        <a href="{{ route('ordenes.show', $orden) }}" class="boton secundario">Ver orden</a>
    </div>

    {{-- ── 1. Reporte de recepción ──────────────────────────────────────────────── --}}
    <div class="tarjeta">
        <div class="encabezado" style="margin-bottom: .5rem;">
            <h2 style="margin: 0;">1. Reporte de recepción</h2>
            @if ($recepcion?->estaCompletada())
                <a href="{{ route('inspecciones.pdf', $recepcion) }}" class="boton chico secundario" target="_blank">Acta PDF</a>
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
                <h3>Fotos de recepción</h3>
                <div class="fotos">
                    @foreach ($recepcion->fotos as $foto)
                        <figure>
                            <a href="{{ route('adjuntos.show', $foto) }}"
                               class="foto-lightbox"
                               data-src="{{ route('adjuntos.show', $foto) }}"
                               data-caption="{{ $foto->anguloFoto?->nombre ?? 'Foto' }}"
                               data-grupo="entrega-recepcion-{{ $recepcion->id }}">
                                <img src="{{ route('adjuntos.show', $foto) }}" alt="{{ $foto->anguloFoto?->nombre }}" loading="lazy">
                            </a>
                            <figcaption>{{ $foto->anguloFoto?->nombre ?? 'Foto' }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif
        @else
            <p class="tenue">Esta orden no tiene una recepción completada.</p>
        @endif
    </div>

    {{-- ── 2. Trabajos realizados ───────────────────────────────────────────────── --}}
    <div class="tarjeta">
        <h2>2. Trabajos realizados</h2>
        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Trabajo</th>
                        <th>Mecánicos</th>
                        <th>Avance</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($trabajos as $t)
                    <tr>
                        <td>{{ $t->descripcion }}</td>
                        <td class="chico">{{ $t->mecanicos->map(fn ($m) => $m->name)->join(', ') ?: '—' }}</td>
                        <td class="chico">{{ $t->estado->label() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="tenue">No se registraron trabajos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── 3. Refacciones utilizadas ────────────────────────────────────────────── --}}
    <div class="tarjeta">
        <h2>3. Refacciones utilizadas</h2>
        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Refacción</th>
                        <th>Para el trabajo</th>
                        <th class="derecha">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($refacciones as $r)
                    <tr>
                        <td>{{ $r->descripcion }}
                            @if ($r->numero_parte)<span class="tenue chico"> · {{ $r->numero_parte }}</span>@endif
                            @if ($r->proporcionada_por_cliente)<span class="etiqueta">la trajo el cliente</span>@endif
                        </td>
                        <td class="chico">{{ $r->trabajo?->descripcion ?? '—' }}</td>
                        <td class="derecha">{{ (float) $r->cantidad }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="tenue">No se utilizaron refacciones.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── 4. Conformidad del cliente ───────────────────────────────────────────── --}}
    @if ($errors->any())
        <div class="aviso error">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('inspecciones.update', $entrega) }}" class="tarjeta">
        @csrf @method('PUT')
        <h2>4. Conformidad del cliente</h2>

        <div class="campo">
            <label for="observaciones">Observaciones finales <span class="tenue">(opcional)</span></label>
            <textarea id="observaciones" name="observaciones" rows="3"
                      placeholder="Ej. Se le explicó el cambio de balatas; regresar en 10,000 km.">{{ old('observaciones', $entrega->observaciones) }}</textarea>
        </div>

        <div class="rejilla">
            <div class="campo">
                <label for="firmante_nombre">Nombre de quien recibe el vehículo</label>
                <input type="text" id="firmante_nombre" name="firmante_nombre"
                       value="{{ old('firmante_nombre', $entrega->firmante_nombre ?? $orden->cliente->nombre_completo) }}">
            </div>
        </div>

        @if ($entrega->firma)
            <p><img src="{{ route('adjuntos.show', $entrega->firma) }}" alt="Firma" style="max-width: 280px; border: 1px solid var(--borde); border-radius: 6px;"><br>
                <span class="tenue chico">Firma guardada. Si vuelven a firmar, se reemplaza.</span></p>
        @endif

        <div class="campo">
            <label>Firma de conformidad</label>
            <canvas id="lienzo-firma" class="firma"></canvas>
            <input type="hidden" name="firma" id="firma">
            <div class="fila" style="margin-top: .35rem;">
                <button type="button" class="boton-link chico" id="limpiar-firma">Borrar</button>
                <span class="ayuda" style="margin: 0;">Firma con el dedo o el mouse. Se guarda con los botones de abajo.</span>
            </div>
        </div>

        <div class="fila" style="margin-top: .75rem;">
            <button class="boton secundario" name="completar" value="0">Guardar avance</button>
            <button class="boton exito" name="completar" value="1">Completar entrega</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    // Firma con el dedo o el mouse (misma lógica que la recepción).
    (function () {
        const lienzo = document.getElementById('lienzo-firma');
        const ctx    = lienzo.getContext('2d');
        const campo  = document.getElementById('firma');
        let dibujando = false;
        let trazando  = false;

        function ajustarTamano() {
            const r = lienzo.getBoundingClientRect();
            if (Math.round(r.width) === lienzo.width) return;
            campo.value = '';
            lienzo.width  = Math.round(r.width);
            lienzo.height = r.height;
            ctx.lineWidth   = 2.5;
            ctx.lineCap     = 'round';
            ctx.strokeStyle = '#111';
        }

        function punto(e) {
            const r = lienzo.getBoundingClientRect();
            return { x: e.clientX - r.left, y: e.clientY - r.top };
        }

        lienzo.addEventListener('pointerdown', (e) => {
            dibujando = true;
            const p = punto(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
        });
        lienzo.addEventListener('pointermove', (e) => {
            if (!dibujando) return;
            const p = punto(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            trazando = true;
        });
        ['pointerup', 'pointerleave'].forEach((ev) => lienzo.addEventListener(ev, () => {
            dibujando = false;
            if (trazando) campo.value = lienzo.toDataURL('image/png');
            trazando = false;
        }));

        document.getElementById('limpiar-firma').addEventListener('click', () => {
            ctx.clearRect(0, 0, lienzo.width, lienzo.height);
            campo.value = '';
        });

        ajustarTamano();
        window.addEventListener('resize', ajustarTamano);
    })();
</script>
@endpush
