@extends('layouts.app')

@section('titulo', 'Preferencias')

@php
    $nombresGrupo = ['precios' => 'Precios', 'recepcion' => 'Recepción', 'entrega' => 'Entrega', 'recordatorios' => 'Próximo servicio', 'general' => 'General'];
@endphp

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'administracion'])
    <h1>Preferencias de operación</h1>
    <p class="tenue">Así trabaja el taller. Los cambios aplican a las órdenes nuevas; las anteriores conservan lo que se capturó.</p>

    @if ($preferencias->isEmpty())
        <div class="aviso alerta">No hay preferencias guardadas. Corre <code>php artisan db:seed --class=PreferenciasOperacionSeeder</code>.</div>
    @endif

    <form method="POST" action="{{ route('preferencias.update') }}">
        @csrf @method('PUT')
        @foreach ($preferencias as $grupo => $lista)
            <div class="tarjeta">
                <h2>{{ $nombresGrupo[$grupo] ?? ucfirst($grupo) }}</h2>
                @foreach ($lista as $p)
                    <div class="campo">
                        @if ($p->tipo === \App\Enums\TipoPreferencia::Bool)
                            <label class="casilla"><input type="checkbox" name="valores[{{ $p->clave }}]" value="1" @checked(old("valores.{$p->clave}", $p->valorTipado()))> {{ $p->descripcion }}</label>
                        @elseif ($p->tipo === \App\Enums\TipoPreferencia::Text)
                            <label for="p-{{ $p->id }}">{{ $p->descripcion }}</label>
                            <textarea id="p-{{ $p->id }}" name="valores[{{ $p->clave }}]">{{ old("valores.{$p->clave}", $p->valor) }}</textarea>
                        @else
                            <label for="p-{{ $p->id }}">{{ $p->descripcion }}</label>
                            <input type="{{ in_array($p->tipo->value, ['int', 'decimal']) ? 'number' : 'text' }}" step="any" id="p-{{ $p->id }}"
                                   name="valores[{{ $p->clave }}]" value="{{ old("valores.{$p->clave}", $p->valor) }}" style="max-width: 280px;"
                                   @readonly($p->clave === 'recepcion_terminos_version')>
                        @endif
                        <p class="ayuda"><code>{{ $p->clave }}</code>@if ($p->clave === 'recepcion_terminos_version') · sube sola cuando cambias el texto de los términos @endif</p>
                    </div>
                @endforeach
            </div>
        @endforeach
        <button class="boton">Guardar preferencias</button>
    </form>
@endsection
