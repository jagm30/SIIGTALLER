@extends('layouts.app')

@section('titulo', 'Preferencias')

@php
    $nombresGrupo = ['precios' => 'Precios', 'trabajos' => 'Trabajos y mecánicos', 'recepcion' => 'Recepción', 'entrega' => 'Entrega', 'recordatorios' => 'Próximo servicio', 'general' => 'General'];
@endphp

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'administracion'])
    <h1>Preferencias de operación</h1>
    <p class="tenue">Así trabaja el taller. Los cambios aplican a las órdenes nuevas; las anteriores conservan lo que se capturó.</p>

    @if ($preferencias->isEmpty())
        <div class="aviso alerta">No hay preferencias guardadas. Corre <code>php artisan db:seed --class=PreferenciasOperacionSeeder</code>.</div>
    @endif

    <form method="POST" action="{{ route('preferencias.taller') }}" enctype="multipart/form-data" class="tarjeta">
        @csrf @method('PUT')
        <h2>Datos del taller</h2>
        <p class="tenue chico">Aparecen en la barra superior, el inicio de sesión, la página y el ticket del QR, y en las actas PDF.</p>

        <div class="campo">
            <label for="taller-nombre">Nombre del taller</label>
            <input type="text" id="taller-nombre" name="nombre" value="{{ old('nombre', $nombreTaller) }}" maxlength="100" required style="max-width: 420px;">
        </div>

        <div class="campo">
            <label for="taller-logo">Logo</label>
            @if ($logoTaller)
                <img src="{{ $logoTaller }}" alt="Logo actual" style="display: block; max-height: 80px; max-width: 220px; margin-bottom: .5rem; border: 1px solid var(--borde); border-radius: 6px; padding: 4px; background: #fff;">
            @endif
            <input type="file" id="taller-logo" name="logo" accept="image/png,image/jpeg,image/webp">
            <p class="ayuda">PNG, JPG o WEBP de hasta 2 MB. De preferencia con fondo blanco o transparente.</p>
            @if ($logoTaller)
                <label class="casilla"><input type="checkbox" name="quitar_logo" value="1"> Quitar el logo actual</label>
            @endif
        </div>

        <button class="boton">Guardar datos del taller</button>
    </form>

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
