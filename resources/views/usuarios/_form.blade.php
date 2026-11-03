@csrf
<div class="rejilla">
    <div class="campo">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" value="{{ old('name', $usuario->name) }}" required>
    </div>
    <div class="campo">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" value="{{ old('email', $usuario->email) }}" required>
    </div>
    <div class="campo">
        <label for="rol">Rol</label>
        <select id="rol" name="rol">
            @foreach (\App\Enums\Rol::opciones() as $valor => $texto)
                <option value="{{ $valor }}" @selected(old('rol', $usuario->rol?->value) === $valor)>{{ $texto }}</option>
            @endforeach
        </select>
    </div>
    <div class="campo">
        <label for="sucursal_id">Sucursal</label>
        <select id="sucursal_id" name="sucursal_id">
            <option value="">Todas (solo administrador)</option>
            @foreach ($sucursales as $s)
                <option value="{{ $s->id }}" @selected((int) old('sucursal_id', $usuario->sucursal_id) === $s->id)>{{ $s->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="campo">
        <label for="telefono">Teléfono</label>
        <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $usuario->telefono) }}" inputmode="tel">
    </div>
</div>
<div class="rejilla">
    <div class="campo">
        <label for="password">Contraseña @if ($usuario->exists)<span class="tenue">(déjala vacía para no cambiarla)</span>@endif</label>
        <input type="password" id="password" name="password" autocomplete="new-password" @required(! $usuario->exists)>
    </div>
    <div class="campo">
        <label for="password_confirmation">Confirmar contraseña</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
    </div>
</div>
<label class="casilla campo"><input type="checkbox" name="activo" value="1" @checked(old('activo', $usuario->activo))> Activo (puede iniciar sesión)</label>
<button class="boton">Guardar</button>
