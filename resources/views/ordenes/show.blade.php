@extends('layouts.app')

@section('titulo', 'Orden ' . $orden->folio)

@php
    $user = auth()->user();
    $verPrecios = $user->can('precios.ver', $orden);
    $editable = ! $orden->estaCerrada();
    $editableDiagnostico = $orden->puedeEditarDiagnostico();
    $puedeTrabajar = $user->tienePermiso('ordenes.trabajar') && $editable && $orden->estado !== \App\Enums\EstadoOrden::Recibido;
    // Botones de estado: solo los siguientes pasos que el rol de este usuario puede dar.
    $siguientes = collect($orden->estado->siguientes())->filter(fn ($e) => $user->tieneRol(...$e->rolesQuePuedenEntrar()));
    $dinero = fn ($v) => $v === null ? '—' : '$' . number_format((float) $v, 2);
@endphp

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>Orden {{ $orden->folio }} <span class="etiqueta {{ $orden->estado->value }}">{{ $orden->estado->label() }}</span></h1>
            <p class="tenue">
                {{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo->nombre }} {{ $orden->vehiculo->anio }} · {{ $orden->vehiculo->placas ?? 'Sin placas' }}
                · {{ $orden->cliente->nombre_completo }} · {{ $orden->cliente->telefono }}
            </p>
        </div>
        <a href="{{ route('vehiculos.show', $orden->vehiculo) }}" class="boton secundario">Historial del vehículo</a>
    </div>

    {{-- Cambio de estado --}}
    @if ($siguientes->isNotEmpty())
        <form method="POST" action="{{ route('ordenes.estado', $orden) }}" class="tarjeta">
            @csrf
            <h2>Siguiente paso</h2>
            <div class="fila">
                <input type="text" name="comentario" placeholder="Comentario (obligatorio para cancelar)" style="flex: 1; min-width: 220px;">
                @foreach ($siguientes as $s)
                    <button name="estado" value="{{ $s->value }}" @class(['boton', 'peligro' => $s === \App\Enums\EstadoOrden::Cancelado, 'exito' => $s === \App\Enums\EstadoOrden::Entregado])>
                        {{ $s === \App\Enums\EstadoOrden::Cancelado ? 'Cancelar orden' : 'Pasar a ' . $s->label() }}
                    </button>
                @endforeach
            </div>
        </form>
    @endif

    <div class="rejilla-2">
        {{-- Recepción --}}
        <div class="tarjeta">
            <h2>Recepción</h2>
            @include('ordenes._inspeccion', ['inspeccion' => $orden->recepcion, 'permiso' => 'recepcion.realizar'])
            <p class="chico"><strong>Motivo de ingreso:</strong> {{ $orden->motivo_ingreso }}</p>
            <p class="chico tenue">Recibió: {{ $orden->recibidoPor->name }} · {{ $orden->fecha_ingreso->format('d/m/Y H:i') }}
                @if ($orden->fecha_promesa) · Promesa: {{ $orden->fecha_promesa->format('d/m/Y') }} @endif</p>
        </div>

        {{-- Entrega --}}
        <div class="tarjeta">
            <h2>Entrega</h2>
            @if ($orden->entrega)
                @include('ordenes._inspeccion', ['inspeccion' => $orden->entrega, 'permiso' => 'entrega.realizar'])
            @elseif ($orden->estado === \App\Enums\EstadoOrden::Terminado && $user->tienePermiso('entrega.realizar'))
                <p>El vehículo está terminado. Antes de entregarlo, revísalo con el cliente.</p>
                <form method="POST" action="{{ route('ordenes.entrega', $orden) }}">
                    @csrf
                    <button class="boton">Iniciar inspección de entrega</button>
                </form>
            @else
                <p class="tenue">Se hace cuando la orden esté terminada.</p>
            @endif
            @if ($orden->fecha_entrega)
                <p class="chico tenue">Entregó: {{ $orden->entregadoPor?->name }} · {{ $orden->fecha_entrega->format('d/m/Y H:i') }}</p>
            @endif
        </div>
    </div>

    {{-- Datos generales --}}
    <form method="POST" action="{{ route('ordenes.update', $orden) }}" class="tarjeta">
        @csrf @method('PATCH')
        <h2>Diagnóstico y datos de la orden</h2>
        <div class="rejilla">
            @unless ($user->esMecanico())
                <div class="campo">
                    <label for="mecanico_responsable_id">Mecánico responsable</label>
                    <select id="mecanico_responsable_id" name="mecanico_responsable_id" @disabled(! $editable)>
                        <option value="">— Sin asignar —</option>
                        @foreach ($mecanicos as $m)
                            <option value="{{ $m->id }}" @selected($orden->mecanico_responsable_id === $m->id)>{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="campo">
                    <label for="fecha_promesa">Fecha promesa</label>
                    <input type="date" id="fecha_promesa" name="fecha_promesa" value="{{ old('fecha_promesa', $orden->fecha_promesa?->format('Y-m-d')) }}" @disabled(! $editable)>
                </div>
                @if ($verPrecios)
                    <div class="campo">
                        {{-- El descuento puede ser en pesos o en porcentaje del subtotal --}}
                        @php($enPorcentaje = old('descuento_tipo', $orden->descuento_porcentaje !== null ? 'porcentaje' : 'monto') === 'porcentaje')
                        <label for="descuento">Descuento</label>
                        <div class="fila" style="flex-wrap: nowrap;">
                            <select name="descuento_tipo" aria-label="Tipo de descuento" style="width: auto; flex-shrink: 0;" @disabled(! $editable)>
                                <option value="monto" @selected(! $enPorcentaje)>$</option>
                                <option value="porcentaje" @selected($enPorcentaje)>%</option>
                            </select>
                            <input type="number" step="0.01" min="0" id="descuento" name="descuento" @disabled(! $editable)
                                   style="flex: 1; min-width: 0;"
                                   value="{{ old('descuento', $enPorcentaje ? (float) $orden->descuento_porcentaje : $orden->descuento) }}">
                        </div>
                    </div>
                @endif
            @endunless
        </div>
        <div class="campo">
            <label for="diagnostico">Diagnóstico del mecánico</label>
            <textarea id="diagnostico" name="diagnostico" @disabled(! $editableDiagnostico)>{{ old('diagnostico', $orden->diagnostico) }}</textarea>
            @unless ($editableDiagnostico || $orden->estaCerrada())
                <p class="ayuda">Disponible a partir de la fase de diagnóstico.</p>
            @endunless
        </div>
        <div class="campo">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" @disabled(! $editable)>{{ old('observaciones', $orden->observaciones) }}</textarea>
        </div>
        @if ($editable)
            <button class="boton secundario">Guardar datos</button>
        @endif
    </form>

    {{-- Trabajos --}}
    <div class="tarjeta">
        <h2>Trabajos realizados</h2>
        <div class="tabla-scroll">
            <table>
                <thead><tr><th>Trabajo</th><th>Cobro</th><th>Mecánicos</th><th>Avance</th>@if ($verPrecios)<th class="derecha">Importe</th>@endif<th></th></tr></thead>
                <tbody>
                @forelse ($orden->trabajos as $t)
                    <tr>
                        <td>{{ $t->descripcion }} @if ($t->notas)<br><span class="tenue chico">{{ $t->notas }}</span>@endif</td>
                        <td class="chico">{{ $t->tipo_cobro->label() }}@if ($t->tipo_cobro === \App\Enums\TipoCobro::PorHora) · {{ (float) $t->cantidad }} h @endif</td>
                        <td class="chico">{{ $t->mecanicos->map(fn ($m) => $m->name . ($m->pivot->horas ? " ({$m->pivot->horas} h)" : ''))->join(', ') ?: '—' }}</td>
                        <td>
                            @if ($puedeTrabajar)
                                <form method="POST" action="{{ route('trabajos.update', $t) }}" class="fila">
                                    @csrf @method('PATCH')
                                    <select name="estado" style="width: auto;" onchange="this.form.submit()">
                                        @foreach (\App\Enums\EstadoTrabajo::opciones() as $valor => $texto)
                                            <option value="{{ $valor }}" @selected($t->estado->value === $valor)>{{ $texto }}</option>
                                        @endforeach
                                    </select>
                                    @if ($user->esMecanico() && $registraHoras)
                                        <input type="number" name="horas" step="0.25" min="0" placeholder="Mis horas" style="width: 110px;">
                                        <button class="boton chico secundario">OK</button>
                                    @endif
                                </form>
                            @else
                                {{ $t->estado->label() }}
                            @endif
                        </td>
                        @if ($verPrecios)
                            <td class="derecha">
                                @if ($t->precio_unitario === null && $puedeTrabajar)
                                    {{-- Quedó sin precio (lo registró alguien que no ve precios): se completa aquí --}}
                                    @include('ordenes._falta_precio', ['accion' => route('trabajos.precio', $t)])
                                @else
                                    {{ $dinero($t->importe) }}
                                @endif
                            </td>
                        @endif
                        <td>
                            @if ($puedeTrabajar)
                                <form method="POST" action="{{ route('trabajos.destroy', $t) }}"
                                      onsubmit="return confirm('{{ $orden->refacciones->where('orden_servicio_item_id', $t->id)->isNotEmpty() ? '¿Quitar este trabajo y sus refacciones?' : '¿Quitar este trabajo?' }}')">
                                    @csrf @method('DELETE')
                                    <button class="boton-link">Quitar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="tenue">Aún no se registran trabajos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($puedeTrabajar)
            <form method="POST" action="{{ route('trabajos.store', $orden) }}" style="margin-top: 1rem;">
                @csrf
                <h3>Agregar trabajo</h3>
                @php($serviciosEnOrden = $orden->trabajos->pluck('servicio_id')->filter())
                <div class="rejilla">
                    <div class="campo">
                        <label for="servicio_id">Servicio</label>
                        {{-- "Otro trabajo" muestra los campos para escribirlo (ver script al final) --}}
                        <select id="servicio_id" name="servicio_id" required data-otro="#trabajo-otro" data-valor-otro="otro">
                            <option value="">Elige un servicio…</option>
                            @foreach ($servicios as $s)
                                <option value="{{ $s->id }}"
                                    data-sugeridas="{{ $s->refacciones_sugeridas_count }}"
                                    @if ($serviciosEnOrden->contains($s->id)) data-ya-esta @endif>
                                    {{ $s->nombre }} · {{ $s->tipo_cobro->label() }}@if ($verPrecios && $s->precio !== null) · ${{ number_format($s->precio, 2) }}@endif
                                    @if ($serviciosEnOrden->contains($s->id)) (ya está en la orden) @endif
                                </option>
                            @endforeach
                            <option value="otro">Otro trabajo (escribirlo)…</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="cantidad">Cantidad u horas</label>
                        <input type="number" id="cantidad" name="cantidad" step="0.25" min="0.25" value="1">
                    </div>
                    @unless ($user->esMecanico())
                        <div class="campo">
                            <label for="mecanicos">Mecánicos</label>
                            <select id="mecanicos" name="mecanicos[]" multiple size="3">
                                @foreach ($mecanicos as $m)
                                    <option value="{{ $m->id }}" @selected($orden->mecanico_responsable_id === $m->id)>{{ $m->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endunless
                </div>

                {{-- Solo aparece al elegir "Otro trabajo" --}}
                <div id="trabajo-otro" class="rejilla" hidden>
                    <div class="campo">
                        <label for="descripcion">Describe el trabajo</label>
                        <input type="text" id="descripcion" name="descripcion" maxlength="200" data-requerido>
                    </div>
                    <div class="campo">
                        <label for="tipo_cobro">Tipo de cobro</label>
                        <select id="tipo_cobro" name="tipo_cobro">
                            @foreach (\App\Enums\TipoCobro::opciones() as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($verPrecios)
                        <div class="campo">
                            <label for="precio_unitario">Precio con IVA <span class="tenue">(por hora si se cobra por hora)</span></label>
                            <input type="number" id="precio_unitario" name="precio_unitario" step="0.01" min="0">
                        </div>
                    @endif
                </div>

                <p id="aviso-repetido" class="aviso alerta" hidden>
                    Este servicio ya está en la orden. Si es el mismo trabajo, mejor cambia la cantidad del que ya existe.
                    Si es otro (por ejemplo, en otra pieza del auto), puedes agregarlo.
                </p>
                <label id="casilla-sugeridas" class="casilla campo" hidden>
                    <input type="checkbox" name="agregar_sugeridas" value="1" checked> Agregar las refacciones que normalmente lleva el servicio
                </label>
                <button class="boton">Agregar trabajo</button>
            </form>
        @endif
    </div>

    {{-- Refacciones --}}
    <div class="tarjeta">
        <h2>Refacciones utilizadas</h2>
        <div class="tabla-scroll">
            <table>
                <thead><tr><th>Pieza</th><th>Para el trabajo</th><th class="derecha">Cantidad</th>@if ($verPrecios)<th class="derecha">Precio</th><th class="derecha">Importe</th>@endif<th></th></tr></thead>
                <tbody>
                @forelse ($orden->refacciones as $r)
                    <tr>
                        <td>{{ $r->descripcion }} @if ($r->numero_parte)<span class="tenue chico">· {{ $r->numero_parte }}</span>@endif
                            @if ($r->proporcionada_por_cliente)<span class="etiqueta">la trajo el cliente</span>@endif</td>
                        <td class="chico">{{ $r->trabajo?->descripcion ?? '—' }}</td>
                        <td class="derecha">{{ (float) $r->cantidad }}</td>
                        @if ($verPrecios)
                            <td class="derecha">
                                @if ($r->precio_unitario === null && $puedeTrabajar)
                                    @include('ordenes._falta_precio', ['accion' => route('refacciones-usadas.precio', $r)])
                                @else
                                    {{ $dinero($r->precio_unitario) }}
                                @endif
                            </td>
                            <td class="derecha">{{ $dinero($r->importe) }}</td>
                        @endif
                        <td>
                            @if ($puedeTrabajar)
                                <form method="POST" action="{{ route('refacciones-usadas.destroy', $r) }}" onsubmit="return confirm('¿Quitar esta refacción?')">
                                    @csrf @method('DELETE')
                                    <button class="boton-link">Quitar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="tenue">Aún no se registran refacciones.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($puedeTrabajar)
            <form method="POST" action="{{ route('refacciones-usadas.store', $orden) }}" style="margin-top: 1rem;">
                @csrf
                <h3>Agregar refacción</h3>
                <p class="tenue chico">Si la pieza ya está en la orden para el mismo trabajo, se suma a ese renglón.</p>
                <div class="rejilla">
                    <div class="campo">
                        <label for="refaccion_id">Refacción</label>
                        <select id="refaccion_id" name="refaccion_id" required data-otro="#refaccion-otra" data-valor-otro="otra">
                            <option value="">Elige una refacción…</option>
                            @foreach ($refaccionesCatalogo as $rc)
                                <option value="{{ $rc->id }}">{{ $rc->descripcion }} @if ($rc->marca)· {{ $rc->marca }}@endif</option>
                            @endforeach
                            <option value="otra">Otra refacción (escribirla)…</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="ref_cantidad">Cantidad</label>
                        <input type="number" id="ref_cantidad" name="cantidad" step="any" min="0.001" value="1" required>
                    </div>
                    <div class="campo">
                        <label for="orden_servicio_item_id">Para el trabajo</label>
                        <select id="orden_servicio_item_id" name="orden_servicio_item_id">
                            <option value="">— Ninguno en particular —</option>
                            @foreach ($orden->trabajos as $t)
                                <option value="{{ $t->id }}">{{ $t->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Solo aparece al elegir "Otra refacción" --}}
                <div id="refaccion-otra" class="rejilla" hidden>
                    <div class="campo">
                        <label for="ref_descripcion">Nombre de la pieza</label>
                        <input type="text" id="ref_descripcion" name="descripcion" maxlength="200" data-requerido>
                    </div>
                    <div class="campo">
                        <label for="ref_numero_parte">Número de parte <span class="tenue">(opcional)</span></label>
                        <input type="text" id="ref_numero_parte" name="numero_parte" maxlength="50">
                    </div>
                    @if ($verPrecios)
                        <div class="campo">
                            <label for="ref_precio">Precio unitario con IVA</label>
                            <input type="number" id="ref_precio" name="precio_unitario" step="0.01" min="0">
                        </div>
                    @endif
                </div>
                <label class="casilla campo"><input type="checkbox" name="proporcionada_por_cliente" value="1"> La trajo el cliente</label>
                <button class="boton">Agregar refacción</button>
            </form>
        @endif
    </div>

    {{-- Totales: solo para quien puede ver precios --}}
    @if ($verPrecios)
        <div class="tarjeta" style="max-width: 360px; margin-left: auto;">
            <table>
                <tr><td>Subtotal</td><td class="derecha">{{ $dinero($orden->subtotal) }}</td></tr>
                <tr><td>Descuento @if ($orden->descuento_porcentaje !== null)<span class="tenue chico">({{ (float) $orden->descuento_porcentaje }} %)</span>@endif</td><td class="derecha">{{ $dinero($orden->descuento) }}</td></tr>
                <tr><td><strong>Total</strong> <span class="tenue chico">(IVA incluido)</span></td><td class="derecha"><strong>{{ $dinero($orden->total) }}</strong></td></tr>
            </table>
        </div>
    @endif

    {{-- Bitácora --}}
    <div class="tarjeta">
        <h2>Historial de la orden</h2>
        @foreach ($orden->bitacoraEstados as $b)
            <p class="chico" style="margin: .25rem 0;">
                <strong>{{ $b->created_at->format('d/m/Y H:i') }}</strong> ·
                {{ $b->estado_anterior?->label() ? $b->estado_anterior->label() . ' → ' : '' }}{{ $b->estado_nuevo->label() }}
                · {{ $b->usuario->name }}
                @if ($b->comentario) · <em>{{ $b->comentario }}</em> @endif
            </p>
        @endforeach
    </div>
@endsection

@push('scripts')
<script>
    // Selectores con opción "Otro/Otra": muestra los campos para escribirlo solo cuando se elige esa opción.
    // Los campos ocultos se desactivan para que no se envíen con datos viejos.
    document.querySelectorAll('select[data-otro]').forEach(function (select) {
        const grupo = document.querySelector(select.dataset.otro);

        function actualizar() {
            const esOtro = select.value === select.dataset.valorOtro;
            grupo.hidden = ! esOtro;
            grupo.querySelectorAll('input, select').forEach(function (campo) { campo.disabled = ! esOtro; });
            grupo.querySelectorAll('[data-requerido]').forEach(function (campo) { campo.required = esOtro; });
        }

        select.addEventListener('change', function () {
            actualizar();
            if (select.value === select.dataset.valorOtro) {
                grupo.querySelector('input').focus();
            }
        });
        actualizar();
    });

    // Al elegir un servicio: avisa si ya está en la orden y decide la casilla de refacciones sugeridas.
    (function () {
        const servicio = document.getElementById('servicio_id');
        if (! servicio) return;

        const aviso = document.getElementById('aviso-repetido');
        const casilla = document.getElementById('casilla-sugeridas');
        const marcar = casilla.querySelector('input');

        function actualizar() {
            const opcion = servicio.selectedOptions[0];
            const yaEsta = opcion.hasAttribute('data-ya-esta');
            const tieneSugeridas = Number(opcion.dataset.sugeridas || 0) > 0;

            aviso.hidden = ! yaEsta;
            casilla.hidden = ! tieneSugeridas;
            // Si el servicio se repite, no duplicamos sus piezas a menos que lo pidan.
            marcar.checked = tieneSugeridas && ! yaEsta;
        }

        servicio.addEventListener('change', actualizar);
        actualizar();
    })();
</script>
@endpush
