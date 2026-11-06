{{-- Formulario compartido por crear y editar vehículo --}}
@csrf
<div class="rejilla">
    <div class="campo">
        <label for="cliente_id">Dueño</label>
        <select id="cliente_id" name="cliente_id" @unless ($vehiculo->exists) data-requerido-sin-nuevo @endunless @if ($vehiculo->exists) required @endif>
            <option value="">— Elige un cliente —</option>
            @foreach ($clientes as $c)
                <option value="{{ $c->id }}" @selected((int) old('cliente_id', $vehiculo->cliente_id) === $c->id)>{{ $c->nombre_completo }}</option>
            @endforeach
        </select>
        @if ($vehiculo->exists)
            <p class="ayuda">Si cambias el dueño, el historial de servicios se queda con el vehículo.</p>
        @endif
    </div>

    @unless ($vehiculo->exists)
        <details class="campo" id="nuevo-cliente-panel" @if (old('nuevo_cliente_nombre')) open @endif>
            <summary class="boton-link" style="cursor: pointer; font-weight: 600; font-size: .9rem;">¿No está en la lista? Registrar cliente nuevo</summary>
            <div class="rejilla" style="margin-top: .75rem;">
                <div class="campo">
                    <label for="nuevo_cliente_nombre">Nombre</label>
                    <input type="text" id="nuevo_cliente_nombre" name="nuevo_cliente_nombre" value="{{ old('nuevo_cliente_nombre') }}">
                </div>
                <div class="campo">
                    <label for="nuevo_cliente_apellidos">Apellidos</label>
                    <input type="text" id="nuevo_cliente_apellidos" name="nuevo_cliente_apellidos" value="{{ old('nuevo_cliente_apellidos') }}">
                </div>
                <div class="campo">
                    <label for="nuevo_cliente_telefono">Teléfono <span class="tenue">(requerido)</span></label>
                    <input type="tel" id="nuevo_cliente_telefono" name="nuevo_cliente_telefono" value="{{ old('nuevo_cliente_telefono') }}" inputmode="tel">
                </div>
            </div>
            <p class="ayuda">El cliente quedará registrado automáticamente al guardar el vehículo.</p>
        </details>
        <script>
            (function () {
                const panel = document.getElementById('nuevo-cliente-panel');
                const select = document.querySelector('[data-requerido-sin-nuevo]');
                const nombre = document.getElementById('nuevo_cliente_nombre');
                const telefono = document.getElementById('nuevo_cliente_telefono');

                function sincronizar() {
                    const abierto = panel.open;
                    select.required = ! abierto;
                    nombre.required = abierto;
                    telefono.required = abierto;
                }

                panel.addEventListener('toggle', sincronizar);
                sincronizar();
            })();
        </script>
    @endunless

    <div class="campo">
        <label for="modelo_vehiculo_id">Marca y modelo</label>
        <select id="modelo_vehiculo_id" name="modelo_vehiculo_id">
            <option value="">— Elige el modelo —</option>
            @foreach ($marcas as $marca)
                <optgroup label="{{ $marca->nombre }}">
                    @foreach ($marca->modelos as $modelo)
                        <option value="{{ $modelo->id }}" @selected((int) old('modelo_vehiculo_id', $vehiculo->modelo_vehiculo_id) === $modelo->id)>{{ $marca->nombre }} {{ $modelo->nombre }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>
</div>

@can('catalogos.alta_rapida')
    <details class="campo" @if (old('nuevo_modelo')) open @endif>
        <summary>¿No está el modelo en la lista? Escríbelo aquí</summary>
        <div class="rejilla" style="margin-top: .5rem;">
            <div class="campo">
                <label for="nueva_marca">Marca</label>
                <input type="text" id="nueva_marca" name="nueva_marca" value="{{ old('nueva_marca') }}">
            </div>
            <div class="campo">
                <label for="nuevo_modelo">Modelo</label>
                <input type="text" id="nuevo_modelo" name="nuevo_modelo" value="{{ old('nuevo_modelo') }}">
            </div>
            <div class="campo">
                <label for="tipo_carroceria">Tipo de vehículo</label>
                <select id="tipo_carroceria" name="tipo_carroceria">
                    <option value="">—</option>
                    @foreach (\App\Enums\TipoCarroceria::opciones() as $valor => $texto)
                        <option value="{{ $valor }}" @selected(old('tipo_carroceria') === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="ayuda">Quedará marcado "por verificar" para que el administrador lo revise.</p>
    </details>
@endcan

<div class="rejilla">
    <div class="campo">
        <label for="placas">Placas</label>
        <input type="text" id="placas" name="placas" value="{{ old('placas', $vehiculo->placas) }}" style="text-transform: uppercase;">
    </div>
    <div class="campo">
        <label for="vin">Número de serie (VIN)</label>
        <input type="text" id="vin" name="vin" value="{{ old('vin', $vehiculo->vin) }}" maxlength="17" style="text-transform: uppercase;">
    </div>
    <div class="campo">
        <label for="anio">Año</label>
        <input type="number" id="anio" name="anio" value="{{ old('anio', $vehiculo->anio) }}" inputmode="numeric">
    </div>
    <div class="campo">
        <label for="color">Color</label>
        <input type="text" id="color" name="color" value="{{ old('color', $vehiculo->color) }}">
    </div>
    <div class="campo">
        <label for="version">Versión</label>
        <input type="text" id="version" name="version" value="{{ old('version', $vehiculo->version) }}" placeholder="Ej. Sense 1.6 TM">
    </div>
    <div class="campo">
        <label for="numero_motor">Número de motor</label>
        <input type="text" id="numero_motor" name="numero_motor" value="{{ old('numero_motor', $vehiculo->numero_motor) }}">
    </div>
    <div class="campo">
        <label for="combustible">Combustible</label>
        <select id="combustible" name="combustible">
            <option value="">—</option>
            @foreach (\App\Enums\Combustible::opciones() as $valor => $texto)
                <option value="{{ $valor }}" @selected(old('combustible', $vehiculo->combustible?->value) === $valor)>{{ $texto }}</option>
            @endforeach
        </select>
    </div>
    <div class="campo">
        <label for="transmision">Transmisión</label>
        <select id="transmision" name="transmision">
            <option value="">—</option>
            @foreach (\App\Enums\Transmision::opciones() as $valor => $texto)
                <option value="{{ $valor }}" @selected(old('transmision', $vehiculo->transmision?->value) === $valor)>{{ $texto }}</option>
            @endforeach
        </select>
    </div>
    @unless ($vehiculo->exists)
        <div class="campo">
            <label for="km_actual">Kilometraje actual</label>
            <input type="number" id="km_actual" name="km_actual" value="{{ old('km_actual', $vehiculo->km_actual) }}" inputmode="numeric">
        </div>
    @endunless
</div>
<div class="campo">
    <label for="notas">Notas</label>
    <textarea id="notas" name="notas">{{ old('notas', $vehiculo->notas) }}</textarea>
</div>
<button class="boton">Guardar</button>
