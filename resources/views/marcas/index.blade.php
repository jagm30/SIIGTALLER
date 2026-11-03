@extends('layouts.app')

@section('titulo', 'Marcas y modelos')

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'catalogos'])
    <h1>Marcas y modelos</h1>

    {{-- Modelos que recepción dio de alta rápida y hay que revisar --}}
    @if ($pendientes->isNotEmpty())
        <div class="tarjeta" style="border-color: #f0d48a;">
            <h2>Por verificar ({{ $pendientes->count() }})</h2>
            <p class="ayuda">Corrige el nombre y confírmalo, o fusiónalo con el modelo correcto si está duplicado.</p>
            @foreach ($pendientes as $m)
                <div class="rejilla-2" style="border-bottom: 1px solid var(--borde); padding: .5rem 0;">
                    <form method="POST" action="{{ route('modelos.update', $m) }}" class="fila">
                        @csrf @method('PATCH')
                        <span class="tenue">{{ $m->marca->nombre }}</span>
                        <input type="text" name="nombre" value="{{ $m->nombre }}" style="width: 160px;">
                        <select name="tipo_carroceria" style="width: auto;">
                            @foreach (\App\Enums\TipoCarroceria::opciones() as $valor => $texto)
                                <option value="{{ $valor }}" @selected($m->tipo_carroceria->value === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="activo" value="1">
                        <button class="boton chico">Confirmar</button>
                        <span class="tenue chico">{{ $m->vehiculos_count }} vehículo(s)</span>
                    </form>
                    <form method="POST" action="{{ route('modelos.fusionar', $m) }}" class="fila" onsubmit="return confirm('Los vehículos pasarán al modelo elegido y este se borrará. ¿Continuar?')">
                        @csrf
                        <select name="destino_id" style="flex: 1;">
                            <option value="">Fusionar con…</option>
                            @foreach ($marcas as $marca)
                                <optgroup label="{{ $marca->nombre }}">
                                    @foreach ($marca->modelos->where('id', '!=', $m->id)->where('verificado', true) as $destino)
                                        <option value="{{ $destino->id }}">{{ $marca->nombre }} {{ $destino->nombre }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <button class="boton chico secundario">Fusionar</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('marcas.store') }}" class="tarjeta fila">
        @csrf
        <input type="text" name="nombre" placeholder="Nueva marca" style="flex: 1;" required>
        <button class="boton">Agregar marca</button>
    </form>

    @foreach ($marcas as $marca)
        <details class="tarjeta">
            <summary><strong>{{ $marca->nombre }}</strong> <span class="tenue">· {{ $marca->modelos->count() }} modelos</span></summary>
            <p class="chico" style="margin-top: .5rem;">
                @foreach ($marca->modelos as $m)
                    <span class="etiqueta">{{ $m->nombre }} · {{ $m->tipo_carroceria->label() }}</span>
                @endforeach
            </p>
            <form method="POST" action="{{ route('modelos.store', $marca) }}" class="fila">
                @csrf
                <input type="text" name="nombre" placeholder="Nuevo modelo" style="flex: 1; min-width: 160px;" required>
                <select name="tipo_carroceria" style="width: auto;" required>
                    @foreach (\App\Enums\TipoCarroceria::opciones() as $valor => $texto)
                        <option value="{{ $valor }}">{{ $texto }}</option>
                    @endforeach
                </select>
                <button class="boton chico">Agregar modelo</button>
            </form>
        </details>
    @endforeach
@endsection
