@extends('layouts.app')

@php
    $titulo = $inspeccion->esRecepcion() ? 'Recepción del vehículo' : 'Entrega del vehículo';
    $orden = $inspeccion->orden;
    // Opciones de respuesta según el tipo de punto del checklist
    $opciones = collect(\App\Enums\TipoRespuesta::cases())
        ->mapWithKeys(fn ($t) => [$t->value => $t->valores()])->filter()->all();
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

    {{-- Lo que falta: se actualiza cada vez que se guarda. Los errores al guardar salen abajo, junto a los botones. --}}
    @if ($faltantes)
        <details class="aviso alerta" @if (count($faltantes) <= 8) open @endif>
            <summary><strong>Faltan {{ count($faltantes) }} datos para completar</strong></summary>
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
        (fotos, firma, términos y botones finales) usando el atributo form="principal", porque
        entre ellos está el formulario de daños y HTML no permite formularios anidados.
    --}}
    <form method="POST" action="{{ route('inspecciones.update', $inspeccion) }}" id="principal" enctype="multipart/form-data">
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

        {{-- Checklist compacto: cada grupo es una tabla con las opciones como columnas.
             Tocar el título de una columna marca esa opción en todos los puntos que falten del grupo. --}}
        <div class="tarjeta">
            <div class="encabezado" style="margin-bottom: .25rem;">
                <h2 style="margin: 0;">2. Revisión del vehículo</h2>
                <span class="progreso" id="progreso-total"></span>
            </div>
            <p class="ayuda">Toca el título de una columna (<strong>Sí</strong>, <strong>Bueno</strong>…) para marcar todo el bloque (puedes cambiarlo las veces que quieras mientras no guardes; lo ya guardado se respeta) y luego corrige solo las excepciones. ✎ agrega un comentario. * obligatorio.</p>

            <div class="grupos-check">
                @foreach ($grupos as $grupo => $items)
                    @php($porTipo = $items->groupBy(fn ($i) => $i->tipo_respuesta->value))
                    {{-- Número de columnas de opciones del grupo (para que los campos de texto ocupen el mismo ancho) --}}
                    @php($columnas = max(1, $porTipo->keys()->map(fn ($t) => count($opciones[$t] ?? []))->max()))
                    <section class="grupo-check" data-grupo>
                        <header>
                            <h3>{{ $grupo }}</h3>
                            <span class="progreso" data-progreso></span>
                            <button type="button" class="boton-link chico" data-limpiar>Limpiar</button>
                        </header>
                        @if ($grupo === 'Tablero')
                            <p class="ayuda" style="margin: 0 0 .25rem;"><strong>Sí</strong> = el testigo enciende con el motor en marcha.</p>
                        @endif
                        <table class="matriz">
                            @foreach ($porTipo as $tipo => $puntos)
                                @if (isset($opciones[$tipo]))
                                    {{-- "Marcar todos" en bloques de 2 o más puntos; con uno solo, las columnas solo llevan su nombre --}}
                                    @php($masivo = $puntos->count() >= 2)
                                    <tr class="cabecera">
                                        <td class="tenue chico">{{ $masivo ? 'Marcar todos:' : '' }}</td>
                                        @foreach ($opciones[$tipo] as $valor => $texto)
                                            <th>
                                                @if ($masivo)
                                                    <button type="button" data-marcar="{{ $valor }}" data-tipo="{{ $tipo }}" title="Marcar «{{ $texto }}» en todos los que no estén guardados">{{ $texto }}</button>
                                                @else
                                                    <span class="titulo-columna">{{ $texto }}</span>
                                                @endif
                                            </th>
                                        @endforeach
                                        @for ($i = count($opciones[$tipo]); $i < $columnas; $i++)<td></td>@endfor
                                        <td></td>
                                    </tr>
                                @endif
                                @foreach ($puntos as $item)
                                    @php($actual = old("respuestas.{$item->id}.valor", $respuestas[$item->id]->valor ?? $item->valor_defecto))
                                    @php($comentario = old("respuestas.{$item->id}.comentario", $respuestas[$item->id]->comentario ?? null))
                                    <tr data-item data-tipo="{{ $tipo }}" @class(['obligatorio' => $item->obligatorio])>
                                        <th scope="row">
                                            {{ $item->nombre }}@if ($item->obligatorio)<span class="requerido"> *</span>@endif
                                            {{-- Va antes de las opciones: si se limpia el punto se envía "" y la respuesta se borra al guardar --}}
                                            @isset($opciones[$tipo])<input type="hidden" name="respuestas[{{ $item->id }}][valor]" value="">@endisset
                                        </th>
                                        @if (isset($opciones[$tipo]))
                                            @foreach ($opciones[$tipo] as $valor => $texto)
                                                <td class="celda">
                                                    <label title="{{ $texto }}">
                                                        <input type="radio" name="respuestas[{{ $item->id }}][valor]" value="{{ $valor }}" @checked($actual === $valor) aria-label="{{ $item->nombre }}: {{ $texto }}">
                                                        <span class="marca" data-valor="{{ $valor }}"></span>
                                                    </label>
                                                </td>
                                            @endforeach
                                            @for ($i = count($opciones[$tipo]); $i < $columnas; $i++)<td></td>@endfor
                                        @else
                                            <td colspan="{{ $columnas }}">
                                                <input type="{{ $tipo === 'numero' ? 'number' : 'text' }}" class="valor" name="respuestas[{{ $item->id }}][valor]" value="{{ $actual }}"
                                                       @if ($tipo === 'numero') min="0" inputmode="numeric" @endif aria-label="{{ $item->nombre }}">
                                            </td>
                                        @endif
                                        <td class="celda-nota">
                                            <button type="button" data-nota @class(['nota', 'tiene' => $comentario]) aria-label="Comentario de {{ $item->nombre }}">✎</button>
                                        </td>
                                    </tr>
                                    <tr class="renglon-nota" @unless ($comentario) hidden @endunless>
                                        <td colspan="{{ $columnas + 2 }}">
                                            <input type="text" name="respuestas[{{ $item->id }}][comentario]" value="{{ $comentario }}" placeholder="Comentario: {{ $item->nombre }}">
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </table>
                    </section>
                @endforeach
            </div>
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
    <div class="tarjeta">
        <h2>4. Fotos del vehículo</h2>
        <p class="ayuda">En la tableta, cada botón abre la cámara. La foto se guarda sola en cuanto la tomas.</p>
        {{-- Cada foto se sube al elegirla (JavaScript). Si la subida falla, el campo conserva el archivo
             y se vuelve a enviar con «Guardar avance» o «Guardar y completar». --}}
        <div class="fotos" data-subir="{{ route('inspecciones.fotos', $inspeccion) }}">
            @foreach ($angulos as $angulo)
                <figure>
                    @if ($foto = $fotosPorAngulo->get($angulo->id)?->last())
                        <a href="{{ route('adjuntos.show', $foto) }}" target="_blank"><img src="{{ route('adjuntos.show', $foto) }}" alt="{{ $angulo->nombre }}"></a>
                    @endif
                    <figcaption><strong>{{ $angulo->nombre }}</strong>@if ($angulo->obligatorio) <span class="tenue">*</span>@endif
                        <br><span class="tenue">{{ $angulo->descripcion }}</span>
                        <span class="estado-foto" aria-live="polite"></span></figcaption>
                    <input type="file" name="fotos[{{ $angulo->id }}]" form="principal" accept="image/*" capture="environment" style="width: 100%; margin-top: .35rem;">
                </figure>
            @endforeach
        </div>
    </div>

    {{-- Daños: solo se muestra si marcaron «Sí, tiene daños». Si ya hay daños registrados no se oculta,
         para que se puedan ver o eliminar (marcar «sin daños» con daños registrados sale en los faltantes). --}}
    @php($tieneDanios = old('sin_danios') !== null ? old('sin_danios') === '0' : $inspeccion->sin_danios === false)
    <div class="tarjeta" id="seccion-danios" data-registrados="{{ $inspeccion->danios->count() }}"
         @unless ($tieneDanios || $inspeccion->danios->isNotEmpty()) hidden @endunless>
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

        <canvas id="lienzo-firma" class="firma"></canvas>
        {{-- Se llena al terminar cada trazo; viaja con el formulario principal --}}
        <input type="hidden" name="firma" id="firma" form="principal">
        <div class="fila" style="margin-top: .5rem;">
            <button type="button" class="boton secundario chico" id="limpiar-firma">Borrar</button>
            <span class="ayuda" style="margin: 0;">La firma se guarda con los botones de abajo.</span>
        </div>
    </div>

    {{-- Botones finales del formulario principal --}}
    <div class="tarjeta">
        <div class="fila" style="justify-content: flex-end;">
            <button class="boton secundario" form="principal">Guardar avance</button>
            <button class="boton exito" form="principal" name="completar" value="1">Guardar y completar {{ $inspeccion->esRecepcion() ? 'recepción' : 'entrega' }}</button>
        </div>

        {{-- Errores al guardar o al completar: aquí, donde se presionó el botón --}}
        @if ($errors->any())
            @php([$errChecklist, $errOtros] = collect($errors->all())->partition(fn ($m) => str_starts_with($m, 'Checklist: falta')))
            <div class="aviso error" style="margin: .75rem 0 0;" id="errores-guardado">
                <strong>{{ $errors->has('inspeccion') ? 'Todavía no se puede completar.' : 'Revisa lo siguiente:' }}</strong>
                <ul>
                    @if ($errChecklist->isNotEmpty())
                        <li>Checklist: faltan {{ $errChecklist->count() }} {{ $errChecklist->count() === 1 ? 'punto' : 'puntos' }} por responder (marcados con * en la sección 2).</li>
                    @endif
                    @foreach ($errOtros as $mensaje)
                        <li>{{ $mensaje }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection

{{-- Esta pantalla muestra sus errores junto a los botones de guardar, no arriba --}}
@section('errores_en_pagina', '1')

@push('scripts')
<script>
    // Restaura la posición de scroll después de cualquier guardado de avance.
    (function () {
        const CLAVE = 'scroll:' + location.pathname;

        const y = sessionStorage.getItem(CLAVE);
        sessionStorage.removeItem(CLAVE);
        // requestAnimationFrame espera a que el DOM esté pintado antes de desplazarse.
        // Si el guardado trajo errores, se muestran junto a los botones; si no, regresa a donde estaba.
        const errores = document.getElementById('errores-guardado');
        if (errores) {
            requestAnimationFrame(() => errores.scrollIntoView({ block: 'center' }));
        } else if (y !== null) {
            requestAnimationFrame(() => window.scrollTo(0, +y));
        }

        // Formularios normales (Guardar avance, Guardar y completar).
        document.addEventListener('submit', function () {
            sessionStorage.setItem(CLAVE, window.scrollY);
        });

        // form.submit() no dispara el evento submit; se expone esta función
        // para que el manejador de guarda-avance la llame antes de form.submit().
        window.__scrollGuardar = function () {
            sessionStorage.setItem(CLAVE, window.scrollY);
        };
    })();

    // Firma con el dedo o el mouse, sin librerías: dibuja en un <canvas> y, al terminar cada trazo,
    // la deja como imagen PNG en el campo oculto que viaja con el formulario principal.
    (function () {
        const lienzo = document.getElementById('lienzo-firma');
        const ctx = lienzo.getContext('2d');
        const campo = document.getElementById('firma');
        let dibujando = false;
        let trazando = false; // hubo movimiento en el trazo actual

        // Cambiar el tamaño del canvas lo borra; el campo se vacía para no guardar una firma que ya no se ve.
        // Solo si cambió el ancho: en la tableta, al hacer scroll la barra del navegador cambia el alto y dispara "resize".
        function ajustarTamano() {
            const r = lienzo.getBoundingClientRect();
            if (Math.round(r.width) === lienzo.width) return;
            campo.value = '';
            lienzo.width = Math.round(r.width);
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

        // La sección de daños aparece al marcar «Sí, tiene daños» (o si ya hay daños registrados).
        const seccionDanios = document.getElementById('seccion-danios');
        document.querySelectorAll('input[name=sin_danios]').forEach((radio) => {
            radio.addEventListener('change', () => {
                const conDanios = document.querySelector('input[name=sin_danios][value="0"]').checked;
                seccionDanios.hidden = ! conDanios && seccionDanios.dataset.registrados === '0';
            });
        });

        // Al elegir una zona, la vista se ajusta sola.
        document.getElementById('zona_vehiculo_id').addEventListener('change', (e) => {
            const vista = e.target.selectedOptions[0]?.dataset.vista;
            if (vista) document.getElementById('vista').value = vista;
        });

        ajustarTamano();
        window.addEventListener('resize', ajustarTamano);

        // Antes de registrar un daño, guarda todo lo capturado (incluidas fotos y firma).
        // Si no, al recargar la página se perdería lo que no se había guardado.
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
                    window.__scrollGuardar(); // submit() no vuelve a disparar el evento submit
                    form.submit();
                }
            });
        });
    })();
    // ── Checklist compacto ──────────────────────────────────────
    (function () {
        const grupos = document.querySelectorAll('[data-grupo]');
        if (! grupos.length) return;

        function estaContestado(renglon) {
            const radio = renglon.querySelector('input[type=radio]:checked');
            const campo = renglon.querySelector('input.valor');
            return radio !== null || (campo !== null && campo.value.trim() !== '');
        }

        // Cuenta lo contestado por grupo y en total, y marca los renglones pendientes.
        function contar() {
            let total = 0, hechos = 0;
            grupos.forEach(function (grupo) {
                const renglones = grupo.querySelectorAll('[data-item]');
                let contestados = 0;
                renglones.forEach(function (r) {
                    const listo = estaContestado(r);
                    r.classList.toggle('contestado', listo);
                    if (listo) contestados++;
                });
                grupo.querySelector('[data-progreso]').textContent = contestados + '/' + renglones.length;
                grupo.classList.toggle('completo', contestados === renglones.length);
                total += renglones.length;
                hechos += contestados;
            });
            document.getElementById('progreso-total').textContent = hechos + ' de ' + total + ' contestados';
        }

        document.addEventListener('click', function (e) {
            // Título de columna: marca esa opción en todos los puntos del bloque. Solo se respetan
            // las respuestas ya guardadas (defaultChecked = venía marcada al cargar la página);
            // lo que se marcó sin guardar se puede volver a cambiar en general.
            const marcar = e.target.closest('[data-marcar]');
            if (marcar) {
                const grupo = marcar.closest('[data-grupo]');
                grupo.querySelectorAll('[data-item][data-tipo="' + marcar.dataset.tipo + '"]').forEach(function (r) {
                    const actual = r.querySelector('input[type=radio]:checked');
                    if (actual && actual.defaultChecked) return;
                    const opcion = r.querySelector('input[type=radio][value="' + marcar.dataset.marcar + '"]');
                    if (opcion) opcion.checked = true;
                });
                contar();
                return;
            }

            // Limpiar: quita las respuestas del grupo (al guardar se borran).
            const limpiar = e.target.closest('[data-limpiar]');
            if (limpiar) {
                const grupo = limpiar.closest('[data-grupo]');
                grupo.querySelectorAll('input[type=radio]').forEach(function (i) { i.checked = false; });
                grupo.querySelectorAll('input.valor').forEach(function (i) { i.value = ''; });
                contar();
                return;
            }

            // ✎: muestra u oculta el comentario del punto.
            const nota = e.target.closest('[data-nota]');
            if (nota) {
                const renglon = nota.closest('tr').nextElementSibling;
                renglon.hidden = ! renglon.hidden;
                if (! renglon.hidden) renglon.querySelector('input').focus();
            }
        });

        document.addEventListener('change', function (e) { if (e.target.closest('[data-grupo]')) contar(); });
        document.addEventListener('input', function (e) {
            if (e.target.matches('.renglon-nota input')) {
                const boton = e.target.closest('tr').previousElementSibling.querySelector('[data-nota]');
                boton.classList.toggle('tiene', e.target.value.trim() !== '');
            }
            if (e.target.matches('[data-grupo] input.valor')) contar();
        });

        contar();
    })();
    // ── Fotos: se suben solas al tomarlas ────────────────────────
    // Al elegir una foto (cámara o galería) se muestra de inmediato y se sube en segundo plano.
    // Si se sube bien, el campo se vacía para que «Guardar» no la mande otra vez; si falla,
    // el archivo se queda en el campo y viaja con «Guardar avance» o «Guardar y completar».
    (function () {
        const contenedor = document.querySelector('.fotos[data-subir]');
        if (! contenedor) return;
        const token = document.querySelector('#principal input[name=_token]').value;

        contenedor.querySelectorAll('input[type=file]').forEach(function (input) {
            let envio = 0; // si eligen otra foto antes de que termine la anterior, solo cuenta la última

            input.addEventListener('change', async function () {
                const fig = input.closest('figure');
                const estado = fig.querySelector('.estado-foto');
                const archivo = input.files && input.files[0];
                let guardada = fig.querySelector('a');
                let preview = fig.querySelector('.foto-preview');

                fig.classList.remove('con-preview', 'subiendo', 'error-foto');
                estado.textContent = '';

                if (! archivo) {
                    // Canceló la cámara: vuelve a mostrar la foto que ya estaba
                    if (preview) preview.remove();
                    if (guardada) guardada.hidden = false;
                    return;
                }

                if (! preview) {
                    preview = document.createElement('img');
                    preview.className = 'foto-preview';
                    preview.alt = 'Vista previa';
                    fig.prepend(preview);
                }
                preview.src = URL.createObjectURL(archivo);
                if (guardada) guardada.hidden = true;

                const miEnvio = ++envio;
                fig.classList.add('subiendo');
                estado.textContent = 'Subiendo…';

                const datos = new FormData();
                datos.append('_token', token);
                datos.append(input.name, archivo);

                let mensaje = null;
                try {
                    const r = await fetch(contenedor.dataset.subir, {
                        method: 'POST',
                        body: datos,
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const json = await r.json().catch(() => ({}));
                    if (miEnvio !== envio) return;

                    if (r.ok) {
                        // La foto subida reemplaza a la vista previa como foto guardada
                        if (! guardada) {
                            guardada = document.createElement('a');
                            guardada.target = '_blank';
                            guardada.appendChild(document.createElement('img'));
                            fig.prepend(guardada);
                        }
                        guardada.href = json.fotos[0].url;
                        guardada.querySelector('img').src = json.fotos[0].url;
                        guardada.querySelector('img').alt = fig.querySelector('strong').textContent;
                        guardada.hidden = false;
                        preview.remove();
                        input.value = '';
                    } else if (r.status === 422) {
                        // Foto rechazada (no es imagen, ya firmó…): reenviarla haría fallar todo el guardado
                        mensaje = (Object.values(json.errors || {})[0]?.[0] || json.message) + ' Toma otra foto.';
                        input.value = '';
                    } else {
                        mensaje = (json.message || 'No se pudo subir.') + ' Se intentará de nuevo al guardar.';
                    }
                } catch (e) {
                    if (miEnvio !== envio) return;
                    mensaje = 'Sin conexión. Se intentará de nuevo al guardar.';
                }

                fig.classList.remove('subiendo');
                if (mensaje) {
                    fig.classList.add('error-foto');
                    estado.textContent = mensaje;
                } else {
                    fig.classList.add('con-preview');
                    estado.textContent = 'Guardada';
                }
            });
        });
    })();
</script>
@endpush
