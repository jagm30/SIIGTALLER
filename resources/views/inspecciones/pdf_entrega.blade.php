@php
    $vehiculo = $orden->vehiculo;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta de entrega · {{ $orden->folio }}</title>
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
        .lista td, .lista th { padding: 2.5pt 4pt; border: 1px solid #cbd2d9; text-align: left; font-size: 8pt; }
        .lista th { background: #e4e7eb; font-size: 7.8pt; }
        .lista .derecha { text-align: right; }
        .firma img { height: 2.2cm; }
        .firma td { padding-top: 4pt; }
        .linea { border-top: 1px solid #1f2933; padding-top: 2pt; text-align: center; }
        .pie { position: fixed; bottom: -0.6cm; left: 0; right: 0; font-size: 6.5pt; color: #9aa5b1; text-align: right; }
    </style>
</head>
<body>
    <div class="pie">{{ $orden->folio }} · Acta de entrega del vehículo</div>

    <table class="encabezado">
        <tr>
            <td>
                <h1>Acta de entrega del vehículo</h1>
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
            <td><span class="etq">Placas</span>{{ $vehiculo->placas ?? 'Sin placas' }}</td>
            <td><span class="etq">Color</span>{{ $vehiculo->color ?? '—' }}</td>
            <td><span class="etq">VIN</span>{{ $vehiculo->vin ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="etq">Cliente</span>{{ $orden->cliente->nombre_completo }}</td>
            <td><span class="etq">Teléfono</span>{{ $orden->cliente->telefono ?? '—' }}</td>
            <td><span class="etq">Recogió el vehículo</span>{{ $inspeccion->firmante_nombre ?? '—' }}</td>
            <td><span class="etq">Entregó</span>{{ $inspeccion->realizadaPor->name }}</td>
        </tr>
        @if ($inspeccion->observaciones)
            <tr>
                <td colspan="4"><span class="etq">Observaciones finales</span>{{ $inspeccion->observaciones }}</td>
            </tr>
        @endif
    </table>

    <h2>Trabajos realizados</h2>
    @if ($orden->trabajos->isEmpty())
        <p class="tenue">No se registraron trabajos.</p>
    @else
        <table class="lista">
            <tr>
                <th>Trabajo</th>
                <th>Mecánicos</th>
                <th>Avance</th>
            </tr>
            @foreach ($orden->trabajos as $t)
                <tr>
                    <td>{{ $t->descripcion }}@if ($t->notas)<br><span class="tenue">{{ $t->notas }}</span>@endif</td>
                    <td>{{ $t->mecanicos->map(fn ($m) => $m->name)->join(', ') ?: '—' }}</td>
                    <td>{{ $t->estado->label() }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Piezas utilizadas</h2>
    @if ($orden->refacciones->isEmpty())
        <p class="tenue">No se utilizaron piezas.</p>
    @else
        <table class="lista">
            <tr>
                <th>Pieza</th>
                <th>Para el trabajo</th>
                <th class="derecha">Cantidad</th>
            </tr>
            @foreach ($orden->refacciones as $r)
                <tr>
                    <td>{{ $r->descripcion }}@if ($r->numero_parte) <span class="tenue">· {{ $r->numero_parte }}</span>@endif</td>
                    <td>{{ $r->trabajo?->descripcion ?? '—' }}</td>
                    <td class="derecha">{{ (float) $r->cantidad }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <br>
    <table class="firma">
        <tr>
            <td style="width: 45%; text-align: center;">
                @if ($inspeccion->firma && ($firma = $inspeccion->firma->dataUri()))
                    <img src="{{ $firma }}" alt="">
                @else
                    <div style="height: 2.2cm;"></div>
                @endif
                <div class="linea">{{ $inspeccion->firmante_nombre ?? $orden->cliente->nombre_completo }}<br>
                    <span class="tenue">Firma de conformidad del cliente
                        @if ($inspeccion->completada_at) · {{ $inspeccion->completada_at->format('d/m/Y H:i') }} @endif
                    </span>
                </div>
            </td>
            <td></td>
            <td style="width: 45%; text-align: center; vertical-align: bottom;">
                <div class="linea">{{ $inspeccion->realizadaPor->name }}<br><span class="tenue">Por el taller</span></div>
            </td>
        </tr>
    </table>
</body>
</html>
