{{-- Formulario compartido por crear y editar cliente --}}
@csrf
<div class="rejilla">
    <div class="campo">
        <label for="tipo_persona">Tipo de persona</label>
        <select id="tipo_persona" name="tipo_persona">
            @foreach (\App\Enums\TipoPersona::opciones() as $valor => $texto)
                <option value="{{ $valor }}" @selected(old('tipo_persona', $cliente->tipo_persona?->value) === $valor)>{{ $texto }}</option>
            @endforeach
        </select>
    </div>
    <div class="campo">
        <label for="nombre">Nombre o nombre comercial</label>
        <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required>
    </div>
    <div class="campo">
        <label for="apellidos">Apellidos</label>
        <input type="text" id="apellidos" name="apellidos" value="{{ old('apellidos', $cliente->apellidos) }}">
    </div>
    <div class="campo">
        <label for="razon_social">Razón social <span class="tenue">(solo persona moral)</span></label>
        <input type="text" id="razon_social" name="razon_social" value="{{ old('razon_social', $cliente->razon_social) }}">
    </div>
    <div class="campo">
        <label for="telefono">Teléfono</label>
        <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $cliente->telefono) }}" required inputmode="tel">
    </div>
    <div class="campo">
        <label for="telefono_alt">Teléfono alterno</label>
        <input type="text" id="telefono_alt" name="telefono_alt" value="{{ old('telefono_alt', $cliente->telefono_alt) }}" inputmode="tel">
    </div>
    <div class="campo">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" value="{{ old('email', $cliente->email) }}">
    </div>
    <div class="campo">
        <label for="rfc">RFC</label>
        <input type="text" id="rfc" name="rfc" value="{{ old('rfc', $cliente->rfc) }}" style="text-transform: uppercase;">
    </div>
</div>
<div class="campo">
    <label for="direccion">Dirección</label>
    <input type="text" id="direccion" name="direccion" value="{{ old('direccion', $cliente->direccion) }}">
</div>
<div class="campo">
    <label for="notas">Notas</label>
    <textarea id="notas" name="notas">{{ old('notas', $cliente->notas) }}</textarea>
</div>
<button class="boton">Guardar</button>
