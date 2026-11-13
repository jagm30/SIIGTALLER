@extends('layouts.app')

@section('titulo', 'Orden ' . $orden->folio)

@php
    $user = auth()->user();
    $verPrecios = $user->can('precios.ver', $orden);
    $editable = ! $orden->estaCerrada();
    $puedeTrabajar = $user->tienePermiso('ordenes.trabajar') && $editable && $orden->estado !== \App\Enums\EstadoOrden::Recibido;
    $avanceBloqueado = $orden->estado === \App\Enums\EstadoOrden::PorAutorizar;
    $estadosDisponibles = collect(\App\Enums\EstadoOrden::enUsoTodos())
        ->filter(fn ($e) => $e !== $orden->estado && ! $orden->estaCerrada()
            && $user->tieneRol(...$e->rolesQuePuedenEntrar()));
    $puedeIniciarDiagnostico =
        $orden->estado === \App\Enums\EstadoOrden::Recibido
        && $orden->recepcion?->estaCompletada()
        && $user->tieneRol(...\App\Enums\EstadoOrden::PorAutorizar->rolesQuePuedenEntrar());
    $puedeAutorizar =
        $orden->estado === \App\Enums\EstadoOrden::PorAutorizar
        && $user->tieneRol(...\App\Enums\EstadoOrden::EnReparacion->rolesQuePuedenEntrar());
    $hayPresupuesto = $orden->trabajos->isNotEmpty() || $orden->refacciones->isNotEmpty();
    $puedeTerminar =
        in_array($orden->estado, [\App\Enums\EstadoOrden::EnReparacion, \App\Enums\EstadoOrden::EsperaRefacciones])
        && $user->tieneRol(...\App\Enums\EstadoOrden::Terminado->rolesQuePuedenEntrar());
    $puedeEntregar =
        $orden->estado === \App\Enums\EstadoOrden::Terminado
        && $user->tienePermiso('entrega.realizar');
    $todosTerminados = $orden->trabajos->isNotEmpty()
        && $orden->trabajos->every(fn ($t) => $t->estado === \App\Enums\EstadoTrabajo::Terminado);
    $dinero = fn ($v) => $v === null ? '—' : '$' . number_format((float) $v, 2);
@endphp

@section('contenido')
    {{-- Encabezado --}}
    <div class="encabezado">
        <div>
            <h1>Orden {{ $orden->folio }} <span class="etiqueta {{ $orden->estado->value }}">{{ $orden->estado->label() }}</span></h1>
            <p class="tenue">
                {{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo->nombre }} {{ $orden->vehiculo->anio }}
                · {{ $orden->vehiculo->placas ?? 'Sin placas' }}
                · {{ $orden->cliente->nombre_completo }} · {{ $orden->cliente->telefono }}
            </p>
        </div>
        <a href="{{ route('vehiculos.show', $orden->vehiculo) }}" class="boton secundario">Historial del vehículo</a>
    </div>

    {{-- ── Barra de acciones ──────────────────────────────────────────────────── --}}
    @if ($puedeTrabajar || $puedeIniciarDiagnostico || $puedeAutorizar || $puedeTerminar || $puedeEntregar || $estadosDisponibles->isNotEmpty() || $orden->recepcion?->estaCompletada())
        <div class="acciones-orden" role="toolbar" aria-label="Acciones de la orden">
            @if ($puedeIniciarDiagnostico)
                <button type="button" class="accion-btn accion-destacado" data-panel="panel-diagnostico">
                    <span class="accion-icono">🔍</span> Ir al diagnóstico
                </button>
            @endif
            @if ($puedeAutorizar)
                <button type="button" class="accion-btn accion-destacado accion-exito"
                        data-panel="panel-autorizar"
                        @unless($hayPresupuesto) disabled title="Registra al menos un trabajo o una refacción antes de autorizar" @endunless>
                    <span class="accion-icono">✓</span> Cliente autorizó
                </button>
            @endif
            @if ($puedeEntregar)
                <button type="button" class="accion-btn accion-destacado accion-exito" data-panel="panel-entregar">
                    <span class="accion-icono">🚗</span> Entregar vehículo
                </button>
            @endif
            @if ($puedeTerminar)
                <button type="button" class="accion-btn accion-destacado accion-exito"
                        data-panel="panel-terminado"
                        @unless($todosTerminados) disabled title="Marca todos los trabajos como «Terminado» antes de cerrar la orden" @endunless>
                    <span class="accion-icono">🏁</span> Trabajo terminado
                </button>
            @endif
            @if ($puedeTrabajar)
                <button type="button" class="accion-btn" data-panel="panel-trabajo">
                    <span class="accion-icono">＋</span> Agregar trabajo
                </button>
                <button type="button" class="accion-btn" data-panel="panel-refaccion">
                    <span class="accion-icono">🔧</span> Agregar refacción
                </button>
            @endif
            @if ($estadosDisponibles->isNotEmpty())
                <button type="button" class="accion-btn" data-panel="panel-estado">
                    <span class="accion-icono">↕</span> Cambiar estado
                </button>
            @endif
            @if ($orden->recepcion?->estaCompletada())
                <a href="{{ route('inspecciones.pdf', $orden->recepcion) }}" class="accion-btn" target="_blank">
                    <span class="accion-icono">📄</span> PDF recepción
                </a>
                <a href="{{ route('ordenes.qr', $orden) }}" class="accion-btn" target="_blank">
                    <span class="accion-icono">◼</span> Imprimir QR
                </a>
            @endif
            @if ($orden->estado === \App\Enums\EstadoOrden::Entregado && $orden->entrega?->estaCompletada())
                <a href="{{ route('inspecciones.pdf', $orden->entrega) }}" class="accion-btn" target="_blank">
                    <span class="accion-icono">📄</span> PDF entrega
                </a>
            @endif
        </div>

        {{-- Panel: Agregar trabajo --}}
        @if ($puedeTrabajar)
            <div id="panel-trabajo" class="panel-accion" hidden>
                <form method="POST" action="{{ route('trabajos.store', $orden) }}">
                    @csrf
                    <h3>Agregar trabajo</h3>
                    @php($serviciosEnOrden = $orden->trabajos->pluck('servicio_id')->filter())
                    <div class="rejilla">
                        <div class="campo">
                            <label for="servicio_id">Servicio</label>
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
                    <div class="fila" style="margin-top: .75rem;">
                        <button class="boton">Guardar trabajo</button>
                        <button type="button" class="boton secundario" data-cerrar-panel>Cancelar</button>
                    </div>
                </form>
            </div>

            {{-- Panel: Agregar refacción --}}
            <div id="panel-refaccion" class="panel-accion" hidden>
                <form method="POST" action="{{ route('refacciones-usadas.store', $orden) }}">
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
                    <label class="casilla campo" style="margin-top: .5rem;"><input type="checkbox" name="proporcionada_por_cliente" value="1"> La trajo el cliente</label>
                    <div class="fila" style="margin-top: .75rem;">
                        <button class="boton">Guardar refacción</button>
                        <button type="button" class="boton secundario" data-cerrar-panel>Cancelar</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Panel: Ir al diagnóstico --}}
        @if ($puedeIniciarDiagnostico)
            <div id="panel-diagnostico" class="panel-accion" hidden>
                <form method="POST" action="{{ route('ordenes.diagnostico', $orden) }}">
                    @csrf
                    <h3>Iniciar diagnóstico</h3>
                    <div class="rejilla">
                        <div class="campo">
                            <label for="diag-mecanico">Mecánico responsable</label>
                            <select id="diag-mecanico" name="mecanico_responsable_id">
                                <option value="">— Sin asignar —</option>
                                @foreach ($mecanicos as $m)
                                    <option value="{{ $m->id }}" @selected($orden->mecanico_responsable_id === $m->id)>{{ $m->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="campo">
                        <label for="diag-notas">Diagnóstico inicial <span class="tenue">(opcional)</span></label>
                        <textarea id="diag-notas" name="diagnostico" rows="3" placeholder="Describe lo que se observó al recibir el vehículo…">{{ old('diagnostico', $orden->diagnostico) }}</textarea>
                    </div>
                    <div class="fila" style="margin-top: .75rem;">
                        <button class="boton">Iniciar diagnóstico</button>
                        <button type="button" class="boton secundario" data-cerrar-panel>Cancelar</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Panel: Autorización del cliente --}}
        @if ($puedeAutorizar)
            <div id="panel-autorizar" class="panel-accion" hidden>
                <form method="POST" action="{{ route('ordenes.estado', $orden) }}">
                    @csrf
                    <input type="hidden" name="estado" value="{{ \App\Enums\EstadoOrden::EnReparacion->value }}">
                    <h3>Autorización del cliente</h3>
                    <p class="ayuda" style="margin-bottom: .75rem;">El cliente aprobó el diagnóstico y el presupuesto. La orden pasa a reparación.</p>
                    <div class="campo">
                        <label for="comentario-autorizar">Comentario <span class="tenue">(opcional)</span></label>
                        <input type="text" id="comentario-autorizar" name="comentario" placeholder="Ej. Cliente firmó la hoja de autorización">
                    </div>
                    <div class="fila" style="margin-top: .75rem;">
                        <button class="boton exito">Confirmar autorización</button>
                        <button type="button" class="boton secundario" data-cerrar-panel>Cancelar</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Panel: Entregar vehículo --}}
        @if ($puedeEntregar)
            <div id="panel-entregar" class="panel-accion" hidden>
                <form method="POST" action="{{ route('ordenes.entrega', $orden) }}">
                    @csrf
                    <h3>Entregar vehículo al cliente</h3>
                    <p class="ayuda" style="margin-bottom: .75rem;">Se abrirá el acta de entrega con el reporte de recepción, los trabajos realizados y las refacciones utilizadas. Al finalizar el cliente firmará su conformidad.</p>
                    <div class="fila" style="margin-top: .75rem;">
                        <button class="boton exito">Ir al acta de entrega</button>
                        <button type="button" class="boton secundario" data-cerrar-panel>Cancelar</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Panel: Trabajo terminado --}}
        @if ($puedeTerminar)
            <div id="panel-terminado" class="panel-accion" hidden>
                <form method="POST" action="{{ route('ordenes.estado', $orden) }}">
                    @csrf
                    <input type="hidden" name="estado" value="{{ \App\Enums\EstadoOrden::Terminado->value }}">
                    <h3>Marcar orden como terminada</h3>
                    <p class="ayuda" style="margin-bottom: .75rem;">Todos los trabajos están completados. La orden pasa a «Terminado» y queda lista para entrega al cliente.</p>
                    <div class="campo">
                        <label for="comentario-terminado">Comentario <span class="tenue">(opcional)</span></label>
                        <input type="text" id="comentario-terminado" name="comentario" placeholder="Ej. Vehículo listo para entrega">
                    </div>
                    <div class="fila" style="margin-top: .75rem;">
                        <button class="boton exito">Confirmar</button>
                        <button type="button" class="boton secundario" data-cerrar-panel>Cancelar</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Panel: Cambiar estado --}}
        @if ($estadosDisponibles->isNotEmpty())
            <div id="panel-estado" class="panel-accion" hidden>
                <form method="POST" action="{{ route('ordenes.estado', $orden) }}">
                    @csrf
                    <h3>Cambiar estado</h3>
                    <div class="rejilla">
                        <div class="campo">
                            <label for="select-estado">Nuevo estado</label>
                            <select name="estado" id="select-estado">
                                @foreach ($estadosDisponibles as $e)
                                    <option value="{{ $e->value }}">{{ $e->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="campo">
                            <label for="comentario-estado">Comentario</label>
                            <input type="text" name="comentario" id="comentario-estado" placeholder="Obligatorio para cancelar">
                        </div>
                    </div>
                    <div class="fila" style="margin-top: .75rem;">
                        <button class="boton">Aplicar</button>
                        <button type="button" class="boton secundario" data-cerrar-panel>Cancelar</button>
                    </div>
                </form>
            </div>
        @endif
    @endif

    {{-- ── Trabajos a realizar ─────────────────────────────────────────────────── --}}
    <div class="tarjeta">
        <h2>Trabajos a realizar</h2>
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
                            @if ($puedeTrabajar && ! $avanceBloqueado)
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
    </div>

    {{-- ── Refacciones a utilizar ──────────────────────────────────────────────── --}}
    <div class="tarjeta">
        <h2>Refacciones a utilizar</h2>
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
    </div>

    {{-- ── Totales ────────────────────────────────────────────────────────────── --}}
    @if ($verPrecios)
        <div class="tarjeta" style="max-width: 360px; margin-left: auto;">
            <table>
                <tr><td>Subtotal</td><td class="derecha">{{ $dinero($orden->subtotal) }}</td></tr>
                <tr><td>Descuento @if ($orden->descuento_porcentaje !== null)<span class="tenue chico">({{ (float) $orden->descuento_porcentaje }} %)</span>@endif</td><td class="derecha">{{ $dinero($orden->descuento) }}</td></tr>
                <tr><td><strong>Total</strong> <span class="tenue chico">(IVA incluido)</span></td><td class="derecha"><strong>{{ $dinero($orden->total) }}</strong></td></tr>
            </table>
        </div>
    @endif

    {{-- ── Datos generales: solo cuando ya inició el diagnóstico ─────────────── --}}
    @if ($orden->estado !== \App\Enums\EstadoOrden::Recibido)
        @include('ordenes._datos')
    @endif

    {{-- ── Recepción y entrega ────────────────────────────────────────────────── --}}
    <div class="rejilla-2">
        <div class="tarjeta">
            <h2>Recepción</h2>
            @include('ordenes._inspeccion', ['inspeccion' => $orden->recepcion, 'permiso' => 'recepcion.realizar'])
            @if ($orden->recepcion->estaCompletada())
                <p><a href="{{ route('ordenes.qr', $orden) }}" class="boton chico secundario">Imprimir QR del cliente</a></p>
            @endif
            <p class="chico"><strong>Motivo de ingreso:</strong> {{ $orden->motivo_ingreso }}</p>
            <p class="chico tenue">Recibió: {{ $orden->recibidoPor->name }} · {{ $orden->fecha_ingreso->format('d/m/Y H:i') }}
                @if ($orden->fecha_promesa) · Promesa: {{ $orden->fecha_promesa->format('d/m/Y') }} @endif</p>
        </div>

        <div class="tarjeta">
            <h2>Entrega</h2>
            @if ($orden->entrega)
                @include('ordenes._inspeccion', ['inspeccion' => $orden->entrega, 'permiso' => 'entrega.realizar'])
            @elseif ($orden->estado === \App\Enums\EstadoOrden::Terminado && $user->tienePermiso('entrega.realizar'))
                <p>El vehículo está terminado. Revisa con el cliente el reporte de recepción y anota las observaciones finales.</p>
                <form method="POST" action="{{ route('ordenes.entrega', $orden) }}">
                    @csrf
                    <button class="boton">Iniciar entrega</button>
                </form>
            @else
                <p class="tenue">Se hace cuando la orden esté terminada.</p>
            @endif
            @if ($orden->fecha_entrega)
                <p class="chico tenue">Entregó: {{ $orden->entregadoPor?->name }} · {{ $orden->fecha_entrega->format('d/m/Y H:i') }}</p>
            @endif
        </div>
    </div>

    {{-- ── Bitácora ────────────────────────────────────────────────────────────── --}}
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
<style>
    .acciones-orden {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        background: #fff;
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        padding: .6rem .75rem;
        margin-bottom: 1rem;
    }
    .accion-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        min-height: 44px;
        padding: .5rem 1.1rem;
        background: #f4f5f7;
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        font: inherit;
        font-size: 1rem;
        cursor: pointer;
        color: var(--texto);
        transition: background .15s, border-color .15s;
    }
    .accion-btn:hover { background: #e8eaed; }
    .accion-btn.accion-destacado { background: var(--primario); border-color: var(--primario); color: #fff; font-weight: 700; }
    .accion-btn.accion-destacado:hover { background: var(--primario-oscuro); }
    .accion-btn.accion-exito { background: var(--exito); border-color: var(--exito); }
    .accion-btn.accion-exito:hover { background: #155c34; }
    .accion-btn:disabled { opacity: .45; cursor: not-allowed; }
    .accion-btn:disabled:hover { background: #f4f5f7; filter: none; }
    .accion-btn.activo {
        background: var(--primario);
        border-color: var(--primario);
        color: #fff;
    }
    .accion-icono { font-style: normal; font-size: 1.1em; }
    .panel-accion {
        background: #fff;
        border: 2px solid var(--primario);
        border-radius: var(--radio);
        padding: 1.25rem;
        margin-bottom: 1rem;
    }
    .panel-accion h3 { margin: 0 0 1rem; font-size: 1.1rem; }
</style>
<script>
    // ── Barra de acciones: un solo panel abierto a la vez ──────────────────────
    const botonesAccion = document.querySelectorAll('.accion-btn[data-panel]');

    botonesAccion.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const panelId = btn.dataset.panel;
            const panel = document.getElementById(panelId);
            const yaActivo = btn.classList.contains('activo');

            // Cierra todo
            botonesAccion.forEach(b => b.classList.remove('activo'));
            document.querySelectorAll('.panel-accion').forEach(p => p.hidden = true);

            // Si no estaba activo, lo abre
            if (! yaActivo) {
                btn.classList.add('activo');
                panel.hidden = false;
                panel.querySelector('input, select, textarea')?.focus();
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });

    // Botón "Cancelar" dentro de un panel
    document.querySelectorAll('[data-cerrar-panel]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            botonesAccion.forEach(b => b.classList.remove('activo'));
            document.querySelectorAll('.panel-accion').forEach(p => p.hidden = true);
        });
    });

    // Comentario obligatorio al seleccionar "Cancelado"
    (function () {
        const sel = document.getElementById('select-estado');
        const com = document.getElementById('comentario-estado');
        if (! sel || ! com) return;
        function actualizar() {
            const esCancelado = sel.value === '{{ \App\Enums\EstadoOrden::Cancelado->value }}';
            com.placeholder = esCancelado ? 'Motivo de cancelación (obligatorio)' : 'Comentario (opcional)';
            com.required = esCancelado;
        }
        sel.addEventListener('change', actualizar);
        actualizar();
    })();

    // Selectores con opción "Otro/Otra"
    document.querySelectorAll('select[data-otro]').forEach(function (select) {
        const grupo = document.querySelector(select.dataset.otro);

        function actualizar() {
            const esOtro = select.value === select.dataset.valorOtro;
            grupo.hidden = ! esOtro;
            grupo.querySelectorAll('input, select').forEach(c => c.disabled = ! esOtro);
            grupo.querySelectorAll('[data-requerido]').forEach(c => c.required = esOtro);
        }

        select.addEventListener('change', function () {
            actualizar();
            if (select.value === select.dataset.valorOtro) grupo.querySelector('input').focus();
        });
        actualizar();
    });

    // Al elegir un servicio: avisa si ya está y decide casilla de sugeridas
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
            marcar.checked = tieneSugeridas && ! yaEsta;
        }

        servicio.addEventListener('change', actualizar);
        actualizar();
    })();
</script>
@endpush
