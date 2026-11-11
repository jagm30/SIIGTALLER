@php
    $vehiculo = $orden->vehiculo;
    $titulo = $inspeccion->esRecepcion() ? 'Acta de recepción del vehículo' : 'Acta de entrega del vehículo';
    $siNo = fn (?bool $v) => $v === null ? '—' : ($v ? 'Sí' : 'No');

    // El checklist va en 3 columnas: se reparte en renglones (títulos de grupo + puntos) a partes iguales.
    $renglones = $checklist->flatMap(fn ($respuestas, $grupo) => collect([['grupo' => $grupo]])
        ->concat($respuestas->map(fn ($r) => ['respuesta' => $r])));
    $columnas = $renglones->chunk((int) ceil(max($renglones->count(), 1) / 3));

    // Fotos de la inspección y de los daños, todas en miniatura.
    $fotos = $inspeccion->fotos->map(fn ($f) => ['adjunto' => $f, 'pie' => $f->anguloFoto?->nombre ?? 'Foto'])
        ->concat($inspeccion->danios->flatMap(fn ($d) => $d->fotos->map(fn ($f) => ['adjunto' => $f, 'pie' => 'Daño: ' . ($d->zona?->nombre ?? $d->vista->label())])));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }} · {{ $orden->folio }}</title>
    <style>
        @page { size: letter; margin: 1.1cm 1.2cm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 8.5pt; color: #1f2933; margin: 0; }
        h1 { font-size: 13pt; margin: 0; }
        h2 { font-size: 9.5pt; margin: 8pt 0 3pt; padding-bottom: 2pt; border-bottom: 1px solid #9aa5b1; text-transform: uppercase; letter-spacing: .3pt; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .tenue { color: #616e7c; }
        .encabezado td { padding: 0; }
        .datos td { padding: 2pt 4pt; border: 1px solid #cbd2d9; }
        .datos .etq { color: #616e7c; font-size: 7pt; display: block; }
        .checklist > tbody > tr > td { width: 33.33%; padding: 0 4pt; }
        .checklist table td { padding: 1.8pt 2pt; border-bottom: 1px solid #e4e7eb; font-size: 8.3pt; }
        .checklist .grupo td { font-weight: bold; background: #e4e7eb; border-bottom: 0; padding-top: 2pt; }
        .checklist .valor { text-align: right; white-space: nowrap; font-weight: bold; }
        .checklist .malo { color: #b42318; }
        .checklist .nota { font-weight: normal; color: #616e7c; font-size: 7.2pt; }
        .fotos td { width: 25%; padding: 3pt; text-align: center; font-size: 7pt; }
        .fotos img { width: 100%; border: 1px solid #cbd2d9; }
        .lista td, .lista th { padding: 2pt 4pt; border: 1px solid #cbd2d9; text-align: left; font-size: 7.8pt; }
        .lista th { background: #e4e7eb; }
        .firma img { height: 2.2cm; }
        .firma td { padding-top: 4pt; }
        .linea { border-top: 1px solid #1f2933; padding-top: 2pt; text-align: center; }
        .salto { page-break-before: always; }
        .pie { position: fixed; bottom: -0.6cm; left: 0; right: 0; font-size: 6.5pt; color: #9aa5b1; text-align: right; }
    </style>
</head>
<body>
    <div class="pie">{{ $orden->folio }} · {{ $titulo }}</div>

    {{-- ── Hoja 1: datos y checklist ─────────────────────────────── --}}
    <table class="encabezado">
        <tr>
            <td>
                <h1>{{ $titulo }}</h1>
                <span class="tenue">{{ config('app.name') }} · {{ $orden->sucursal->nombre }}
                    @if ($orden->sucursal->direccion) · {{ $orden->sucursal->direccion }} @endif
                    @if ($orden->sucursal->telefono) · Tel. {{ $orden->sucursal->telefono }} @endif</span>
            </td>
            <td style="text-align: right; width: 32%;">
                <strong style="font-size: 12pt;">Orden {{ $orden->folio }}</strong><br>
                <span class="tenue">{{ $inspeccion->completada_at->format('d/m/Y H:i') }}</span>
            </td>
        </tr>
    </table>

    <h2>Vehículo y cliente</h2>
    <table class="datos">
        <tr>
            <td><span class="etq">Vehículo</span>{{ $vehiculo->marca->nombre }} {{ $vehiculo->modelo->nombre }} {{ $vehiculo->anio }}</td>
            <td><span class="etq">Placas</span>{{ $inspeccion->sin_placas ? 'Sin placas' : ($inspeccion->placas ?? $vehiculo->placas ?? '—') }}</td>
            <td><span class="etq">Color</span>{{ $vehiculo->color ?? '—' }}</td>
            <td><span class="etq">VIN</span>{{ $vehiculo->vin ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="etq">Cliente</span>{{ $orden->cliente->nombre_completo }}</td>
            <td><span class="etq">Teléfono</span>{{ $orden->cliente->telefono ?? '—' }}</td>
            <td><span class="etq">{{ $inspeccion->esRecepcion() ? 'Entregó el vehículo' : 'Recogió el vehículo' }}</span>{{ $inspeccion->persona_nombre }}</td>
            <td><span class="etq">Atendió</span>{{ $inspeccion->realizadaPor->name }}</td>
        </tr>
        <tr>
            <td><span class="etq">Kilometraje</span>{{ number_format($inspeccion->km) }} km</td>
            <td><span class="etq">Gasolina</span>{{ $inspeccion->combustibleTexto() }}</td>
            <td colspan="2"><span class="etq">Motivo de ingreso</span>{{ $orden->motivo_ingreso }}</td>
        </tr>
    </table>

    <h2>Checklist</h2>
    <table class="checklist">
        <tr>
            @foreach ($columnas as $columna)
                <td>
                    <table>
                        @foreach ($columna as $renglon)
                            @isset($renglon['grupo'])
                                <tr class="grupo"><td colspan="2">{{ $renglon['grupo'] }}</td></tr>
                            @else
                                @php
                                    $r = $renglon['respuesta'];
                                    $texto = $r->checklistItem?->tipo_respuesta->valores()[$r->valor] ?? $r->valor;
                                @endphp
                                <tr>
                                    <td>{{ $r->item_nombre }}
                                        @if ($r->comentario)<br><span class="nota">{{ $r->comentario }}</span>@endif</td>
                                    <td @class(['valor', 'malo' => $r->valor === 'malo'])>{{ $texto }}</td>
                                </tr>
                            @endisset
                        @endforeach
                    </table>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- ── Hoja 2: fotos, daños y firma ──────────────────────────── --}}
    <div class="salto"></div>

    <h2>Fotos ({{ $fotos->count() }})</h2>
    @if ($fotos->isEmpty())
        <p class="tenue">Sin fotos.</p>
    @else
        <table class="fotos">
            @foreach ($fotos->chunk(4) as $fila)
                <tr>
                    @foreach ($fila as $foto)
                        <td>
                            @if ($uri = $foto['adjunto']->dataUri(360))
                                <img src="{{ $uri }}" alt="">
                            @else
                                <div class="tenue" style="height: 3.2cm; border: 1px dashed #cbd2d9;">Archivo no disponible</div>
                            @endif
                            {{ $foto['pie'] }}
                        </td>
                    @endforeach
                    @for ($i = $fila->count(); $i < 4; $i++)<td></td>@endfor
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Daños</h2>
    @if ($inspeccion->danios->isEmpty())
        <p>{{ $inspeccion->sin_danios ? 'Sin daños visibles.' : 'No se registraron daños.' }}</p>
    @else
        <table class="lista">
            <tr><th>Vista</th><th>Zona</th><th>Tipo</th><th>Severidad</th><th>Medida</th><th>Descripción</th></tr>
            @foreach ($inspeccion->danios as $d)
                <tr>
                    <td>{{ $d->vista->label() }}</td>
                    <td>{{ $d->zona?->nombre ?? '—' }}</td>
                    <td>{{ $d->tipo->label() }}</td>
                    <td>{{ $d->severidad->label() }}</td>
                    <td>{{ $d->medida_cm ? $d->medida_cm . ' cm' : '—' }}</td>
                    <td>{{ $d->descripcion ?? '' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Objetos de valor y observaciones</h2>
    <table class="datos">
        <tr>
            <td style="width: 30%;"><span class="etq">¿Retiró sus objetos de valor?</span>{{ $siNo($inspeccion->cliente_retiro_valores) }}</td>
            <td><span class="etq">Objetos que dejó en el vehículo</span>{{ $inspeccion->objetos_personales ?: 'Ninguno' }}</td>
        </tr>
        @if ($inspeccion->observaciones)
            <tr><td colspan="2"><span class="etq">Observaciones</span>{{ $inspeccion->observaciones }}</td></tr>
        @endif
    </table>

    @if ($inspeccion->esRecepcion() && $terminos)
        <h2>Términos aceptados</h2>
        <p style="font-size: 7.5pt; margin: 0;">{{ $terminos }}</p>
    @endif

    <table class="firma">
        <tr>
            <td style="width: 45%; text-align: center;">
                @if ($inspeccion->firma && ($firma = $inspeccion->firma->dataUri()))
                    <img src="{{ $firma }}" alt=""><br>
                @else
                    <div style="height: 2.2cm;"></div>
                @endif
                <div class="linea">{{ $inspeccion->firmante_nombre ?? $inspeccion->persona_nombre }}<br>
                    <span class="tenue">Firma del cliente @if ($inspeccion->firmado_at) · {{ $inspeccion->firmado_at->format('d/m/Y H:i') }} @endif</span></div>
            </td>
            <td></td>
            <td style="width: 45%; text-align: center; vertical-align: bottom;">
                <div class="linea">{{ $inspeccion->realizadaPor->name }}<br><span class="tenue">Por el taller</span></div>
            </td>
        </tr>
    </table>
</body>
</html>
