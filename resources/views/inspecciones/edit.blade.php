@extends('layouts.app')

@php
    $titulo = $inspeccion->esRecepcion() ? 'Recepción del vehículo' : 'Entrega del vehículo';
    $orden = $inspeccion->orden;
    // Opciones de respuesta según el tipo de punto del checklist
    $opciones = [
        'si_no' => ['si' => 'Sí', 'no' => 'No'],
        'estado' => ['bueno' => 'Bueno', 'regular' => 'Regular', 'malo' => 'Malo', 'no_aplica' => 'N/A'],
        'nivel' => ['lleno' => 'Lleno', 'medio' => 'Medio', 'bajo' => 'Bajo'],
    ];
    $fotosPorAngulo = $inspeccion->fotos->groupBy('angulo_foto_id');
    // Los puntos del checklist pendientes se resumen en una sola línea para que la lista no sea enorme.
    [$faltanChecklist, $faltanOtros] = collect($faltantes)->partition(fn ($f) => str_starts_with($f, 'Checklist: falta'));
@endphp

@section('titulo', $titulo . ' · ' . $orden->folio)

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>{{ $titulo }}</h1>
            <p class="tenue">Orden {{ $orden->folio }} · {{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo->nombre }}
                · {{ $orden->vehiculo->placas ?? 'Sin placas' }} · {{ $orden->cliente->nombre_completo }}</p>
        </div>
        <a href="{{ route('ordenes.show', $orden) }}" class="boton secundario">Ver orden</a>
    </div>

    {{-- Lo que falta: se actualiza cada vez que se guarda --}}
    @if ($faltantes)
        {{-- Se abre sola si son pocos o si acaban de intentar completar --}}
        <details @class(['aviso', 'alerta' => ! $errors->has('inspeccion'), 'error' => $errors->has('inspeccion')])
                 @if (count($faltantes) <= 8 || $errors->has('inspeccion')) open @endif>
            <summary><strong>
                {{ $errors->has('inspeccion') ? 'Todavía no se puede completar.' : '' }}
                Faltan {{ count($faltantes) }} datos para completar
            </strong></summary>
            <ul>
                @if ($faltanChecklist->isNotEmpty())
                    <li>Checklist: faltan {{ $faltanChecklist->count() }} {{ $faltanChecklist->count() === 1 ? 'punto' : 'puntos' }} por responder (marcados con * en la sección 2).</li>
                @endif
                @foreach ($faltanOtros as $f)<li>{{ $f }}</li>@endforeach
            </ul>
        </details>
    @else
        <div class="aviso exito">Todo está capturado. Ya puedes completar {{ $inspeccion->esRecepcion() ? 'la recepción' : 'la entrega' }}.</div>
    @endif

    {{--
        El formulario principal se llama "principal". Algunos de sus campos están más abajo
        (términos y botones finales) usando el atributo form="principal", porque entre ellos
        hay otros formularios (fotos, daños, firma) y HTML no permite formularios anidados.
    --}}
    <form method="POST" action="{{ route('inspecciones.update', $inspeccion) }}" id="principal">
        @csrf @method('PUT')

        <div class="tarjeta">
            <h2>1. Datos de llegada</h2>
            <div class="rejilla">
                <div class="campo">
                    <label for="km">Kilometraje</label>
                    <input type="number" id="km" name="km" value="{{ old('km', $inspeccion->km) }}" inputmode="numeric" min="0">
                    <p class="ayuda">Último registrado: {{ number_format($kmAnterior) }} km</p>
                </div>
                <div class="campo">
                    <label for="nivel_combustible">Nivel de gasolina</label>
                    <select id="nivel_combustible" name="nivel_combustible">
                        <option value="">—</option>
                        @for ($i = 0; $i <= 8; $i++)
                            <option value="{{ $i }}" @selected((string) old('nivel_combustible', $inspeccion->nivel_combustible) === (string) $i)>
                                {{ ['Vacío', '1/8', '1/4', '3/8', '1/2', '5/8', '3/4', '7/8', 'Lleno'][$i] }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="campo">
                    <label for="placas">Placas con las que llegó</label>
                    <input type="text" id="placas" name="placas" value="{{ old('placas', $inspeccion->placas) }}" style="text-transform: uppercase;">
                    <label class="casilla" style="margin-top: .35rem;"><input type="checkbox" name="sin_placas" value="1" @checked(old('sin_placas', $inspeccion->sin_placas))> Llegó sin placas</label>
                </div>
            </div>
            <div class="campo">
                <label for="km_justificacion">Justificación del kilometraje <span class="tenue">(solo si es menor al anterior: tablero cambiado, error de captura…)</span></label>
                <input type="text" id="km_justificacion" name="km_justificacion" value="{{ old('km_justificacion', $inspeccion->km_justificacion) }}">
            </div>
            <div class="rejilla">
                <div class="campo">
                    <label for="persona_nombre">{{ $inspeccion->esRecepcion() ? '¿Quién entrega el vehículo?' : '¿Quién recoge el vehículo?' }}</label>
                    <input type="text" id="persona_nombre" name="persona_nombre" value="{{ old('persona_nombre', $inspeccion->persona_nombre ?? $orden->cliente->nombre_completo) }}">
                    <label class="casilla" style="margin-top: .35rem;"><input type="checkbox" name="persona_es_cliente" value="1" @checked(old('persona_es_cliente', $inspeccion->persona_es_cliente))> Es el cliente</label>
                </div>
                <div class="campo">
                    <label for="persona_telefono">Teléfono de esa persona</label>
                    <input type="text" id="persona_telefono" name="persona_telefono" value="{{ old('persona_telefono', $inspeccion->persona_telefono) }}" inputmode="tel">
                </div>
            </div>
        </div>

        <div class="tarjeta">
            <h2>2. Revisión del vehículo</h2>
            @foreach ($grupos as $grupo => $items)
                <h3>{{ $grupo }}</h3>
                @if ($grupo === 'Tablero')
                    <p class="ayuda">Marca <strong>Sí</strong> si el testigo está encendido con el motor en marcha.</p>
                @endif
                @foreach ($items as $item)
                    @php($actual = old("respuestas.{$item->id}.valor", $respuestas[$item->id]->valor ?? null))
                    <div class="punto">
                        <span>{{ $item->nombre }} @if ($item->obligatorio)<span class="tenue">*</span>@endif
                            @if ($item->obligatorio && $actual === null)<span class="error-campo" style="display: inline;">· pendiente</span>@endif</span>
                        @if (isset($opciones[$item->tipo_respuesta->value]))
                            <div class="opciones" role="radiogroup" aria-label="{{ $item->nombre }}">
                                @foreach ($opciones[$item->tipo_respuesta->value] as $valor => $texto)
                                    <label><input type="radio" name="respuestas[{{ $item->id }}][valor]" value="{{ $valor }}" @checked($actual === $valor)><span>{{ $texto }}</span></label>
                                @endforeach
                            </div>
                        @else
                            <input type="{{ $item->tipo_respuesta->value === 'numero' ? 'number' : 'text' }}" name="respuestas[{{ $item->id }}][valor]" value="{{ $actual }}" style="max-width: 220px;">
                        @endif
                        @php($comentario = old("respuestas.{$item->id}.comentario", $respuestas[$item->id]->comentario ?? null))
                        <details class="comentario" @if ($comentario) open @endif>
                            <summary class="tenue chico">Agregar comentario</summary>
                            <input type="text" name="respuestas[{{ $item->id }}][comentario]" value="{{ $comentario }}"
                                   placeholder="Ej. solo tapetes delanteros" style="min-height: 36px; padding: .35rem .6rem; margin-top: .25rem;">
                        </details>
                    </div>
                @endforeach
            @endforeach
            <p class="ayuda">* Obligatorio para completar.</p>
        </div>

        <div class="tarjeta">
            <h2>3. Daños y objetos de valor</h2>
            <div class="rejilla">
                <div class="campo">
                    <label>¿El vehículo tiene daños visibles?</label>
                    <div class="opciones">
                        <label><input type="radio" name="sin_danios" value="1" @checked(old('sin_danios', $inspeccion->sin_danios) === true || old('sin_danios') === '1')><span>No, sin daños</span></label>
                        <label><input type="radio" name="sin_danios" value="0" @checked(old('sin_danios', $inspeccion->sin_danios) === false || old('sin_danios') === '0')><span>Sí, tiene daños</span></label>
                    </div>
                    <p class="ayuda">Si tiene daños, regístralos abajo con su foto.</p>
                </div>
                <div class="campo">
                    <label>¿El cliente retiró sus objetos de valor?</label>
                    <div class="opciones">
                        <label><input type="radio" name="cliente_retiro_valores" value="1" @checked(old('cliente_retiro_valores', $inspeccion->cliente_retiro_valores) === true || old('cliente_retiro_valores') === '1')><span>Sí</span></label>
                        <label><input type="radio" name="cliente_retiro_valores" value="0" @checked(old('cliente_retiro_valores', $inspeccion->cliente_retiro_valores) === false || old('cliente_retiro_valores') === '0')><span>No</span></label>
                    </div>
                </div>
            </div>
            <div class="campo">
                <label for="objetos_personales">Objetos que se quedan en el vehículo</label>
                <textarea id="objetos_personales" name="objetos_personales" placeholder="Ej. silla para bebé en asiento trasero">{{ old('objetos_personales', $inspeccion->objetos_personales) }}</textarea>
            </div>
            <div class="campo">
                <label for="observaciones">Observaciones generales</label>
                <textarea id="observaciones" name="observaciones">{{ old('observaciones', $inspeccion->observaciones) }}</textarea>
            </div>
            <button class="boton secundario">Guardar avance</button>
        </div>
    </form>

    {{-- Fotos por ángulo --}}
    <form method="POST" action="{{ route('inspecciones.fotos', $inspeccion) }}" enctype="multipart/form-data" class="tarjeta" data-guarda-avance>
        @csrf
        <h2>4. Fotos del vehículo</h2>
        <p class="ayuda">En la tableta, cada botón abre la cámara. Puedes tomar varias y guardarlas juntas.</p>
        <div class="fotos">
            @foreach ($angulos as $angulo)
                <figure>
                    @if ($foto = $fotosPorAngulo->get($angulo->id)?->last())
                        <a href="{{ route('adjuntos.show', $foto) }}" target="_blank"><img src="{{ route('adjuntos.show', $foto) }}" alt="{{ $angulo->nombre }}"></a>
                    @endif
                    <figcaption><strong>{{ $angulo->nombre }}</strong>@if ($angulo->obligatorio) <span class="tenue">*</span>@endif
                        <br><span class="tenue">{{ $angulo->descripcion }}</span></figcaption>
                    <input type="file" name="fotos[{{ $angulo->id }}]" accept="image/*" capture="environment" style="width: 100%; margin-top: .35rem;">
                </figure>
            @endforeach
        </div>
        <p style="margin-top: .75rem;"><button class="boton">Subir fotos seleccionadas</button></p>
    </form>

    {{-- Daños --}}
    <div class="tarjeta">
        <h2>5. Daños registrados</h2>
        @forelse ($inspeccion->danios as $d)
            <div class="fila" style="justify-content: space-between; border-bottom: 1px solid var(--borde); padding: .4rem 0;">
                <span>
                    <strong>{{ $d->tipo->label() }}</strong> {{ strtolower($d->severidad->label()) }}
                    · {{ $d->zona?->nombre ?? $d->vista->label() }}
                    @if ($d->medida_cm) · {{ $d->medida_cm }} cm @endif
                    @if ($d->descripcion)<br><span class="tenue chico">{{ $d->descripcion }}</span>@endif
                    @if ($d->fotos->isEmpty())<br><span class="error-campo">Falta la foto de este daño</span>@endif
                </span>
                <span class="fila">
                    @foreach ($d->fotos as $f)
                        <a href="{{ route('adjuntos.show', $f) }}" target="_blank"><img src="{{ route('adjuntos.show', $f) }}" alt="Foto del daño" style="width: 64px; height: 48px; object-fit: cover; border-radius: 4px;"></a>
                    @endforeach
                    <form method="POST" action="{{ route('danios.destroy', $d) }}" onsubmit="return confirm('¿Eliminar este daño?')">
                        @csrf @method('DELETE')
                        <button class="boton-link">Eliminar</button>
                    </form>
                </span>
            </div>
        @empty
            <p class="tenue">Sin daños registrados.</p>
        @endforelse

        <form method="POST" action="{{ route('danios.store', $inspeccion) }}" enctype="multipart/form-data" style="margin-top: 1rem;" data-guarda-avance>
            @csrf
            <h3>Agregar daño</h3>
            <div class="rejilla">
                <div class="campo">
                    <label for="zona_vehiculo_id">Zona</label>
                    <select id="zona_vehiculo_id" name="zona_vehiculo_id">
                        <option value="">—</option>
                        @foreach ($zonas->groupBy(fn ($z) => $z->vista->label()) as $vista => $lista)
                            <optgroup label="{{ $vista }}">
                                @foreach ($lista as $z)
                                    <option value="{{ $z->id }}" data-vista="{{ $z->vista->value }}">{{ $z->nombre }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="campo">
                    <label for="vista">Vista</label>
                    <select id="vista" name="vista" required>
                        @foreach (\App\Enums\VistaVehiculo::opciones() as $valor => $texto)
                            <option value="{{ $valor }}">{{ $texto }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="campo">
                    <label for="tipo">Tipo de daño</label>
                    <select id="tipo" name="tipo" required>
                        @foreach (\App\Enums\TipoDanio::opciones() as $valor => $texto)
                            <option value="{{ $valor }}">{{ $texto }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="campo">
                    <label for="severidad">Severidad</label>
                    <select id="severidad" name="severidad" required>
                        @foreach (\App\Enums\SeveridadDanio::opciones() as $valor => $texto)
                            <option value="{{ $valor }}">{{ $texto }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="campo">
                    <label for="medida_cm">Medida aproximada (cm)</label>
                    <input type="number" id="medida_cm" name="medida_cm" min="1" inputmode="numeric">
                </div>
                <div class="campo">
                    <label for="foto">Foto del daño</label>
                    <input type="file" id="foto" name="foto" accept="image/*" capture="environment">
                </div>
            </div>
            <div class="campo">
                <label for="danio_descripcion">Descripción</label>
                <input type="text" id="danio_descripcion" name="descripcion" placeholder="Ej. rayón horizontal cerca de la manija">
            </div>
            <button class="boton">Agregar daño</button>
        </form>
    </div>

    {{-- Firma --}}
    <div class="tarjeta">
        <h2>6. Conformidad y firma</h2>
        @if ($terminos)
            <p class="chico" style="background: #fafbfc; border: 1px solid var(--borde); border-radius: 6px; padding: .75rem;">{{ $terminos }}</p>
        @endif
        <div class="rejilla">
            <div class="campo">
                <label for="firmante_nombre">Nombre de quien firma</label>
                <input type="text" id="firmante_nombre" name="firmante_nombre" form="principal"
                       value="{{ old('firmante_nombre', $inspeccion->firmante_nombre ?? $inspeccion->persona_nombre) }}">
            </div>
            <div class="campo" style="align-self: end;">
                <label class="casilla"><input type="checkbox" name="acepta_terminos" value="1" form="principal" @checked(old('acepta_terminos', $inspeccion->acepta_terminos))> El cliente acepta los términos</label>
            </div>
        </div>

        @if ($inspeccion->firma)
            <p><img src="{{ route('adjuntos.show', $inspeccion->firma) }}" alt="Firma" style="max-width: 280px; border: 1px solid var(--borde); border-radius: 6px;"><br>
                <span class="tenue chico">Firma guardada. Si vuelven a firmar, se reemplaza.</span></p>
        @endif

        <form method="POST" action="{{ route('inspecciones.firma', $inspeccion) }}" id="form-firma" data-guarda-avance>
            @csrf
            <canvas id="lienzo-firma" class="firma"></canvas>
            <input type="hidden" name="firma" id="firma">
            <div class="fila" style="margin-top: .5rem;">
                <button type="button" class="boton secundario chico" id="limpiar-firma">Borrar</button>
                <button class="boton chico">Guardar firma</button>
            </div>
        </form>
    </div>

    {{-- Botones finales del formulario principal --}}
    <div class="tarjeta fila" style="justify-content: flex-end;">
        <button class="boton secundario" form="principal">Guardar avance</button>
        <button class="boton exito" form="principal" name="completar" value="1">Guardar y completar {{ $inspeccion->esRecepcion() ? 'recepción' : 'entrega' }}</button>
    </div>
@endsection

@push('scripts')
<script>
    // Firma con el dedo o el mouse, sin librerías: dibuja en un <canvas> y lo envía como imagen PNG.
    (function () {
        const lienzo = document.getElementById('lienzo-firma');
        const ctx = lienzo.getContext('2d');
        let dibujando = false;
        let trazo = false;

        function ajustarTamano() {
            const r = lienzo.getBoundingClientRect();
            lienzo.width = r.width;
            lienzo.height = r.height;
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
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
            trazo = true;
        });
        ['pointerup', 'pointerleave'].forEach((ev) => lienzo.addEventListener(ev, () => (dibujando = false)));

        document.getElementById('limpiar-firma').addEventListener('click', () => {
            ctx.clearRect(0, 0, lienzo.width, lienzo.height);
            trazo = false;
        });

        document.getElementById('form-firma').addEventListener('submit', (e) => {
            if (!trazo) {
                e.preventDefault();
                alert('Pide al cliente que firme en el recuadro.');
                return;
            }
            document.getElementById('firma').value = lienzo.toDataURL('image/png');
        });

        // Al elegir una zona, la vista se ajusta sola.
        document.getElementById('zona_vehiculo_id').addEventListener('change', (e) => {
            const vista = e.target.selectedOptions[0]?.dataset.vista;
            if (vista) document.getElementById('vista').value = vista;
        });

        ajustarTamano();
        window.addEventListener('resize', ajustarTamano);

        // Antes de subir fotos, daños o la firma, guarda lo que ya se capturó arriba.
        // Si no, al recargar la página se perderían las respuestas del checklist sin guardar.
        const principal = document.getElementById('principal');
        document.querySelectorAll('form[data-guarda-avance]').forEach((form) => {
            form.addEventListener('submit', async (e) => {
                if (e.defaultPrevented) return; // por ejemplo, la firma vacía
                e.preventDefault();
                try {
                    await fetch(principal.action, {
                        method: 'POST',
                        body: new FormData(principal),
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                } finally {
                    form.submit(); // submit() no vuelve a disparar este evento
                }
            });
        });
    })();
</script>
@endpush
