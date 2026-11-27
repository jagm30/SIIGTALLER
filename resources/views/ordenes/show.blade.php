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

    // Color del acento según estado
    $acentoColor = match($orden->estado->value) {
        'recibido'           => ['#607d8b', '#90a4ae'],
        'diagnostico'        => ['#a86b00', '#d4a017'],
        'por_autorizar'      => ['#c27a00', '#e8a800'],
        'en_reparacion'      => ['#1f5fbf', '#5b9cf6'],
        'espera_refacciones' => ['#c05621', '#ed8936'],
        'terminado'          => ['#1e7a46', '#38a169'],
        'entregado'          => ['#1e7a46', '#38a169'],
        'cancelado'          => ['#b3261e', '#e57373'],
        default              => ['#1f5fbf', '#5b9cf6'],
    };
@endphp

@push('styles')
<style>
    /* ── Hero de la orden ───────────────────────────────────────────────── */
    .or-hero {
        background: var(--tarjeta);
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .or-hero-acento { height: 4px; }
    .or-hero-cuerpo {
        display: flex;
        align-items: flex-start;
        gap: 1.1rem;
        padding: 1.2rem 1.2rem .8rem;
        flex-wrap: wrap;
    }
    .or-icono-bloque {
        flex-shrink: 0;
        width: 54px;
        height: 54px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        line-height: 1;
        user-select: none;
    }
    .or-hero-info { flex: 1; min-width: 0; }
    .or-folio-titulo {
        font-family: ui-monospace, "Cascadia Code", Consolas, monospace;
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 .3rem;
        display: flex;
        align-items: center;
        gap: .6rem;
        flex-wrap: wrap;
    }
    .or-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .3rem .9rem;
        margin-top: .35rem;
    }
    .or-chip {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .875rem;
        color: var(--tenue);
    }
    .or-chip a { color: var(--primario); text-decoration: none; }
    .or-chip a:hover { text-decoration: underline; }
    .or-hero-acciones {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        padding: 0 1.2rem .9rem;
        justify-content: flex-end;
    }

    /* Stats strip */
    .or-stats {
        display: flex;
        border-top: 1px solid var(--borde);
        background: #fafbfc;
    }
    .or-stat { flex: 1; padding: .6rem 1.1rem; }
    .or-stat + .or-stat { border-left: 1px solid var(--borde); }
    .or-stat-label {
        font-size: .75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: var(--tenue);
        margin-bottom: .1rem;
    }
    .or-stat-val { font-size: .93rem; font-weight: 600; }
    .or-stat-val.atrasado { color: var(--peligro); }

    /* ── Barra de acciones ──────────────────────────────────────────────── */
    .acciones-orden {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        background: #fff;
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        padding: .6rem .75rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
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
        text-decoration: none;
        transition: background .15s, border-color .15s;
    }
    .accion-btn:hover { background: #e8eaed; }
    .accion-btn.accion-destacado {
        background: var(--primario);
        border-color: var(--primario);
        color: #fff;
        font-weight: 700;
    }
    .accion-btn.accion-destacado:hover { background: var(--primario-oscuro); }
    .accion-btn.accion-exito { background: var(--exito); border-color: var(--exito); }
    .accion-btn.accion-exito:hover { background: #155c34; }
    .accion-btn:disabled { opacity: .45; cursor: not-allowed; }
    .accion-btn:disabled:hover { background: #f4f5f7; }
    .accion-btn.activo { background: var(--primario); border-color: var(--primario); color: #fff; }
    .accion-icono { font-style: normal; font-size: 1.1em; }

    /* ── Paneles de acción ──────────────────────────────────────────────── */
    .panel-accion {
        background: #fff;
        border: 2px solid var(--primario);
        border-radius: var(--radio);
        padding: 1.25rem;
        margin-bottom: 1rem;
    }
    .panel-accion h3 { margin: 0 0 1rem; font-size: 1.1rem; }

    /* ── Bitácora timeline ──────────────────────────────────────────────── */
    .or-bitacora { padding: .25rem 0; }
    .or-evento {
        display: flex;
        gap: .9rem;
        position: relative;
    }
    .or-evento + .or-evento { margin-top: .1rem; }
    .or-evento-linea {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-shrink: 0;
        width: 20px;
    }
    .or-evento-punto {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--borde);
        border: 2px solid var(--tarjeta);
        outline: 2px solid var(--borde);
        margin-top: .35rem;
        flex-shrink: 0;
        z-index: 1;
    }
    .or-evento:first-child .or-evento-punto { background: var(--primario); outline-color: var(--primario); }
    .or-evento-linea::after {
        content: '';
        flex: 1;
        width: 2px;
        background: var(--borde);
        margin-top: 3px;
        min-height: 18px;
    }
    .or-evento:last-child .or-evento-linea::after { display: none; }
    .or-evento-cuerpo {
        padding: .2rem 0 .85rem;
        min-width: 0;
        flex: 1;
    }
    .or-evento-cabeza {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: .2rem .5rem;
        margin-bottom: .1rem;
    }
    .or-evento-fecha {
        font-size: .8rem;
        color: var(--tenue);
        white-space: nowrap;
        font-family: ui-monospace, Consolas, monospace;
    }
    .or-evento-transicion {
        font-size: .88rem;
        font-weight: 600;
    }
    .or-evento-usuario {
        font-size: .82rem;
        color: var(--tenue);
    }
    .or-evento-comentario {
        font-size: .85rem;
        color: var(--tenue);
        display: block;
        margin-top: .1rem;
    }

    /* ── Tarjetas de selección de servicio ──────────────────────────────── */
    #tarjetas-servicios {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
        gap: .4rem;
        max-height: 270px;
        overflow-y: auto;
        padding: 2px;
    }
    .tarj-srv {
        padding: .65rem .75rem;
        border: 2px solid var(--borde);
        border-radius: var(--radio);
        background: #fff;
        cursor: pointer;
        text-align: left;
        display: flex;
        flex-direction: column;
        gap: .2rem;
        font: inherit;
        transition: border-color .15s, background .15s;
        min-height: 56px;
    }
    .tarj-srv:hover { border-color: #a0aec0; background: #f7f9fc; }
    .tarj-srv.tarj-sel {
        border-color: var(--primario);
        background: #e8edf9;
        box-shadow: 0 0 0 1px var(--primario);
    }
    .tarj-srv-nombre { font-weight: 600; font-size: .9rem; color: var(--texto); line-height: 1.3; }
    .tarj-srv-info { font-size: .77rem; color: var(--tenue); }
    .tarj-srv[data-ya-esta] { opacity: .75; }

    /* ── Lista buscable de refacciones ───────────────────────────────────── */
    #lista-refacciones-sel {
        border: 1px solid var(--borde);
        border-radius: var(--radio);
        max-height: 220px;
        overflow-y: auto;
        background: #fff;
    }
    .ref-opcion {
        padding: .6rem .75rem;
        cursor: pointer;
        border-bottom: 1px solid #f0f1f3;
        font-size: .93rem;
        color: var(--texto);
        transition: background .1s;
        min-height: 44px;
        display: flex;
        align-items: center;
    }
    .ref-opcion:last-child { border-bottom: none; }
    .ref-opcion:hover { background: #eef2fb; }
    .ref-opcion.ref-sel { background: #e8edf9; color: var(--primario); font-weight: 600; }
    .ref-opcion-otra { color: var(--tenue); font-style: italic; border-top: 1px solid var(--borde) !important; }
    .ref-sin-resultado { padding: .6rem .75rem; color: var(--tenue); font-size: .9rem; }

    /* ── Responsive ─────────────────────────────────────────────────────── */
    @media (max-width: 600px) {
        .or-folio-titulo { font-size: 1.25rem; }
        .or-icono-bloque { width: 44px; height: 44px; font-size: 1.3rem; border-radius: 9px; }
        .or-hero-acciones { justify-content: flex-start; padding-top: 0; }
    }
    @media (max-width: 480px) {
        .or-stats { flex-direction: column; }
        .or-stat + .or-stat { border-left: 0; border-top: 1px solid var(--borde); }
        .or-hero-cuerpo { gap: .75rem; }
    }
</style>
@endpush

@section('contenido')

    {{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
    <div class="or-hero">
        <div class="or-hero-acento" style="background: linear-gradient(90deg, {{ $acentoColor[0] }} 0%, {{ $acentoColor[1] }} 100%);"></div>

        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start;">
            <div class="or-hero-cuerpo" style="flex:1; min-width:0;">
                <div class="or-icono-bloque" style="background: linear-gradient(135deg, {{ $acentoColor[0] }} 0%, {{ $acentoColor[1] }} 100%);">🔧</div>
                <div class="or-hero-info">
                    <div class="or-folio-titulo">
                        {{ $orden->folio }}
                        <span class="etiqueta {{ $orden->estado->value }}">{{ $orden->estado->label() }}</span>
                    </div>
                    <div class="or-chips">
                        <span class="or-chip">🚗 <a href="{{ route('vehiculos.show', $orden->vehiculo) }}">{{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo->nombre }} {{ $orden->vehiculo->anio }}</a>
                            @if ($orden->vehiculo->placas)<span class="tenue">· {{ $orden->vehiculo->placas }}</span>@endif
                        </span>
                        <span class="or-chip">👤 <a href="{{ route('clientes.show', $orden->cliente) }}">{{ $orden->cliente->nombre_completo }}</a>
                            <span class="tenue">· {{ $orden->cliente->telefono }}</span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="or-hero-acciones">
                <a href="{{ route('vehiculos.show', $orden->vehiculo) }}" class="boton secundario">Historial del vehículo</a>
            </div>
        </div>

        <div class="or-stats">
            <div class="or-stat">
                <div class="or-stat-label">Ingreso</div>
                <div class="or-stat-val">{{ $orden->fecha_ingreso->format('d/m/Y') }}</div>
            </div>
            <div class="or-stat">
                <div class="or-stat-label">Fecha promesa</div>
                @if ($orden->fecha_promesa)
                    <div class="or-stat-val {{ $orden->fecha_promesa->isPast() && $editable ? 'atrasado' : '' }}">
                        {{ $orden->fecha_promesa->format('d/m/Y') }}
                        @if ($orden->fecha_promesa->isPast() && $editable) · atrasada @endif
                    </div>
                @else
                    <div class="or-stat-val tenue">Sin definir</div>
                @endif
            </div>
            <div class="or-stat">
                <div class="or-stat-label">Mecánico</div>
                <div class="or-stat-val">{{ $orden->mecanico_responsable_id ? $orden->mecanicoResponsable->name : '—' }}</div>
            </div>
            <div class="or-stat">
                <div class="or-stat-label">Recibió</div>
                <div class="or-stat-val">{{ $orden->recibidoPor->name }}</div>
            </div>
        </div>
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

                    {{-- Select nativo oculto: lo usa el formulario y los listeners de sugeridas/otro --}}
                    <select id="servicio_id" name="servicio_id" data-otro="#trabajo-otro" data-valor-otro="otro" style="display:none" aria-hidden="true">
                        <option value="">Elige un servicio…</option>
                        @foreach ($servicios as $s)
                            <option value="{{ $s->id }}"
                                data-sugeridas="{{ $s->refacciones_sugeridas_count }}"
                                @if ($serviciosEnOrden->contains($s->id)) data-ya-esta @endif>
                                {{ $s->nombre }}
                            </option>
                        @endforeach
                        <option value="otro">Otro trabajo (escribirlo)…</option>
                    </select>

                    {{-- Selector visual: tarjetas de servicio --}}
                    <div class="campo">
                        <label>Elige el servicio</label>
                        <input type="search" id="buscar-servicio-grilla" placeholder="Buscar servicio…" autocomplete="off" style="margin-bottom:.5rem;">
                        <div id="tarjetas-servicios">
                            @foreach ($servicios as $s)
                                <button type="button" class="tarj-srv"
                                        data-id="{{ $s->id }}"
                                        @if ($serviciosEnOrden->contains($s->id)) data-ya-esta @endif>
                                    <span class="tarj-srv-nombre">{{ $s->nombre }}</span>
                                    <span class="tarj-srv-info">{{ $s->tipo_cobro->label() }}@if ($verPrecios && $s->precio !== null) · ${{ number_format($s->precio, 2) }}@endif @if ($serviciosEnOrden->contains($s->id))· (ya está en la orden)@endif</span>
                                </button>
                            @endforeach
                            <button type="button" class="tarj-srv" data-id="otro">
                                <span class="tarj-srv-nombre">Otro trabajo…</span>
                                <span class="tarj-srv-info">Escribir descripción</span>
                            </button>
                        </div>
                    </div>

                    <p id="aviso-repetido" class="aviso alerta" hidden>
                        Este servicio ya está en la orden. Si es el mismo trabajo, mejor cambia la cantidad del que ya existe.
                        Si es otro (por ejemplo, en otra pieza del auto), puedes agregarlo.
                    </p>
                    <div id="seccion-sugeridas" hidden style="margin-top:.6rem;">
                        <div class="fila" style="justify-content:space-between; align-items:baseline; margin-bottom:.45rem;">
                            <p class="chico tenue" style="margin:0;">Refacciones sugeridas — desmarca las que no se van a usar:</p>
                            <span style="display:flex; gap:.6rem; white-space:nowrap;">
                                <button type="button" class="boton-link chico" id="btn-marcar-todas">Marcar todas</button>
                                <span class="tenue">·</span>
                                <button type="button" class="boton-link chico" id="btn-desmarcar-todas">Desmarcar todas</button>
                            </span>
                        </div>
                        <div id="lista-sugeridas" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:.3rem .75rem;"></div>
                    </div>

                    <div class="rejilla" style="margin-top:.75rem;">
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

                    {{-- Select nativo oculto: lo usa el formulario y el listener data-otro --}}
                    <select id="refaccion_id" name="refaccion_id" data-otro="#refaccion-otra" data-valor-otro="otra" style="display:none" aria-hidden="true">
                        <option value="">— Elige una refacción —</option>
                        @foreach ($refaccionesCatalogo as $rc)
                            <option value="{{ $rc->id }}">{{ $rc->descripcion }}@if ($rc->marca) · {{ $rc->marca }}@endif</option>
                        @endforeach
                        <option value="otra">Otra refacción (escribirla)…</option>
                    </select>

                    {{-- Selector visual: lista buscable --}}
                    <div class="campo" style="margin-bottom:.6rem;">
                        <label>Refacción</label>
                        <input type="search" id="buscar-refaccion-lista" placeholder="Buscar por nombre o marca…" autocomplete="off" style="margin-bottom:.4rem;">
                        <div id="lista-refacciones-sel"></div>
                    </div>

                    <div class="rejilla">
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
                        <button class="boton">Guardar diagnóstico</button>
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
        <div class="tarjeta tarjeta-totales">
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

    {{-- ── Bitácora timeline ───────────────────────────────────────────────────── --}}
    <div class="tarjeta">
        <h2>Historial de la orden</h2>
        <div class="or-bitacora">
            @foreach ($orden->bitacoraEstados as $b)
                <div class="or-evento">
                    <div class="or-evento-linea">
                        <div class="or-evento-punto"></div>
                    </div>
                    <div class="or-evento-cuerpo">
                        <div class="or-evento-cabeza">
                            <span class="or-evento-fecha">{{ $b->created_at->format('d/m/Y H:i') }}</span>
                            <span class="or-evento-transicion">
                                @if ($b->estado_anterior)
                                    <span class="etiqueta {{ $b->estado_anterior->value }}" style="font-size:.75rem;">{{ $b->estado_anterior->label() }}</span>
                                    →
                                @endif
                                <span class="etiqueta {{ $b->estado_nuevo->value }}" style="font-size:.75rem;">{{ $b->estado_nuevo->label() }}</span>
                            </span>
                            <span class="or-evento-usuario">{{ $b->usuario->name }}</span>
                        </div>
                        @if ($b->comentario)
                            <em class="or-evento-comentario">{{ $b->comentario }}</em>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Mapa servicioId → lista de refacciones sugeridas (precargar sin AJAX)
    const sugeridas = {!! $sugeridasJson !!};

    // ── Barra de acciones: un solo panel abierto a la vez ──────────────────────
    const botonesAccion = document.querySelectorAll('.accion-btn[data-panel]');

    botonesAccion.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const panelId = btn.dataset.panel;
            const panel = document.getElementById(panelId);
            const yaActivo = btn.classList.contains('activo');

            botonesAccion.forEach(b => b.classList.remove('activo'));
            document.querySelectorAll('.panel-accion').forEach(p => p.hidden = true);

            if (! yaActivo) {
                btn.classList.add('activo');
                panel.hidden = false;
                Array.from(panel.querySelectorAll('input, select, textarea'))
                    .find(el => el.offsetParent !== null)?.focus();
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });

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

    // Al elegir un servicio: avisa si ya está y muestra las refacciones sugeridas con checkboxes
    (function () {
        const servicio = document.getElementById('servicio_id');
        if (! servicio) return;
        const aviso   = document.getElementById('aviso-repetido');
        const seccion = document.getElementById('seccion-sugeridas');
        const lista   = document.getElementById('lista-sugeridas');

        function renderizarSugeridas(opcion) {
            const yaEsta  = opcion.hasAttribute('data-ya-esta');
            const items   = sugeridas[opcion.value] || [];

            seccion.hidden = items.length === 0;
            lista.innerHTML = '';

            items.forEach(function (r) {
                const label = document.createElement('label');
                label.className = 'casilla campo';

                const cb    = document.createElement('input');
                cb.type     = 'checkbox';
                cb.name     = 'refacciones_sugeridas[]';
                cb.value    = r.id;
                cb.checked  = ! yaEsta;

                label.appendChild(cb);
                label.appendChild(document.createTextNode(
                    ' ' + r.descripcion + ' · ' + r.cantidad + ' ' + r.unidad.toLowerCase()
                ));
                lista.appendChild(label);
            });
        }

        function actualizar() {
            const opcion = servicio.selectedOptions[0];
            aviso.hidden = ! opcion.hasAttribute('data-ya-esta');
            renderizarSugeridas(opcion);
        }

        servicio.addEventListener('change', actualizar);
        actualizar();

        document.getElementById('btn-marcar-todas')?.addEventListener('click', function () {
            lista.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = true);
        });
        document.getElementById('btn-desmarcar-todas')?.addEventListener('click', function () {
            lista.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false);
        });
    })();

    // ── Tarjetas de selección de servicio ────────────────────────────────────
    (function () {
        const sel    = document.getElementById('servicio_id');
        const buscar = document.getElementById('buscar-servicio-grilla');
        if (! sel) return;

        const tarjetas = Array.from(document.querySelectorAll('#tarjetas-servicios .tarj-srv'));

        function seleccionarTarjeta(tarjeta) {
            tarjetas.forEach(t => t.classList.remove('tarj-sel'));
            if (tarjeta) tarjeta.classList.add('tarj-sel');
            sel.value = tarjeta ? tarjeta.dataset.id : '';
            sel.dispatchEvent(new Event('change', { bubbles: true }));
        }

        tarjetas.forEach(function (t) {
            t.addEventListener('click', function () { seleccionarTarjeta(t); });
        });

        if (buscar) {
            buscar.addEventListener('input', function () {
                const q = buscar.value.toLowerCase();
                tarjetas.forEach(function (t) {
                    const nombre = t.querySelector('.tarj-srv-nombre').textContent.toLowerCase();
                    t.style.display = (! q || nombre.includes(q)) ? '' : 'none';
                });
            });
        }

        // Resetear selección y búsqueda al abrir el panel
        document.querySelectorAll('.accion-btn[data-panel="panel-trabajo"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (buscar) buscar.value = '';
                tarjetas.forEach(t => { t.style.display = ''; t.classList.remove('tarj-sel'); });
                sel.value = '';
                sel.dispatchEvent(new Event('change', { bubbles: true }));
                setTimeout(function () { if (buscar) buscar.focus(); }, 60);
            });
        });
    })();

    // ── Lista buscable de refacciones ─────────────────────────────────────────
    (function () {
        const sel    = document.getElementById('refaccion_id');
        const buscar = document.getElementById('buscar-refaccion-lista');
        const lista  = document.getElementById('lista-refacciones-sel');
        if (! sel || ! lista) return;

        // Captura las opciones del select nativo
        const opciones = Array.from(sel.options).map(o => ({ value: o.value, texto: o.textContent.trim() }));

        function renderizar(q) {
            lista.innerHTML = '';
            const qLower   = q.toLowerCase();
            const catálogo = opciones.filter(o => o.value !== '' && o.value !== 'otra');
            const otra     = opciones.find(o => o.value === 'otra');

            const visibles = q ? catálogo.filter(o => o.texto.toLowerCase().includes(qLower)) : catálogo;

            if (visibles.length === 0) {
                const p = document.createElement('p');
                p.className = 'ref-sin-resultado';
                p.textContent = 'Sin resultados';
                lista.appendChild(p);
            } else {
                visibles.forEach(function (o) {
                    const div = document.createElement('div');
                    div.className = 'ref-opcion';
                    div.dataset.value = o.value;
                    div.textContent = o.texto;
                    if (o.value === sel.value) div.classList.add('ref-sel');
                    div.addEventListener('click', function () {
                        lista.querySelectorAll('.ref-opcion').forEach(d => d.classList.remove('ref-sel'));
                        div.classList.add('ref-sel');
                        sel.value = o.value;
                        sel.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    lista.appendChild(div);
                });
            }

            // "Otra refacción" siempre al final, sin importar el filtro
            if (otra) {
                const div = document.createElement('div');
                div.className = 'ref-opcion ref-opcion-otra';
                div.dataset.value = otra.value;
                div.textContent = otra.texto;
                if (otra.value === sel.value) div.classList.add('ref-sel');
                div.addEventListener('click', function () {
                    lista.querySelectorAll('.ref-opcion').forEach(d => d.classList.remove('ref-sel'));
                    div.classList.add('ref-sel');
                    sel.value = otra.value;
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                });
                lista.appendChild(div);
            }
        }

        if (buscar) {
            buscar.addEventListener('input', function () { renderizar(buscar.value.trim()); });
        }

        renderizar('');

        // Resetear al abrir el panel
        document.querySelectorAll('.accion-btn[data-panel="panel-refaccion"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (buscar) buscar.value = '';
                renderizar('');
                sel.value = '';
                setTimeout(function () { if (buscar) buscar.focus(); }, 60);
            });
        });
    })();
</script>
@endpush
