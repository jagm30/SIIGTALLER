@csrf
<div class="rejilla">
    <div class="campo">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $servicio->nombre) }}" required>
    </div>
    <div class="campo">
        <label for="clave">Clave</label>
        <input type="text" id="clave" name="clave" value="{{ old('clave', $servicio->clave) }}">
    </div>
    <div class="campo">
        <label for="categoria">Categoría</label>
        <input type="text" id="categoria" name="categoria" value="{{ old('categoria', $servicio->categoria) }}" placeholder="Motor, frenos, suspensión…">
    </div>
    <div class="campo">
        <label for="tipo_cobro">Tipo de cobro</label>
        <select id="tipo_cobro" name="tipo_cobro">
            @foreach (\App\Enums\TipoCobro::opciones() as $valor => $texto)
                <option value="{{ $valor }}" @selected(old('tipo_cobro', $servicio->tipo_cobro?->value) === $valor)>{{ $texto }}</option>
            @endforeach
        </select>
    </div>
    {{-- Si el taller opera sin precios, este campo no aparece --}}
    @can('precios.ver')
        <div class="campo">
            <label for="precio">Precio con IVA <span class="tenue">(por hora si el cobro es por hora)</span></label>
            <input type="number" step="0.01" min="0" id="precio" name="precio" value="{{ old('precio', $servicio->precio) }}">
        </div>
    @endcan
    <div class="campo">
        <label for="tiempo_estimado_min">Tiempo estimado (minutos)</label>
        <input type="number" min="1" id="tiempo_estimado_min" name="tiempo_estimado_min" value="{{ old('tiempo_estimado_min', $servicio->tiempo_estimado_min) }}">
    </div>
</div>
<h3>Próximo servicio</h3>
<p class="ayuda">Si lo dejas vacío se usa el intervalo general de Preferencias ({{ \App\Models\PreferenciaOperacion::int('recordatorio_meses_default') ?: '—' }} meses o {{ number_format(\App\Models\PreferenciaOperacion::int('recordatorio_km_default')) }} km).</p>
<div class="rejilla">
    <div class="campo">
        <label for="intervalo_meses">Cada cuántos meses</label>
        <input type="number" min="1" id="intervalo_meses" name="intervalo_meses" value="{{ old('intervalo_meses', $servicio->intervalo_meses) }}">
    </div>
    <div class="campo">
        <label for="intervalo_km">Cada cuántos kilómetros</label>
        <input type="number" min="100" step="100" id="intervalo_km" name="intervalo_km" value="{{ old('intervalo_km', $servicio->intervalo_km) }}">
    </div>
</div>
<div class="campo">
    <label for="descripcion">Descripción</label>
    <textarea id="descripcion" name="descripcion">{{ old('descripcion', $servicio->descripcion) }}</textarea>
</div>
<label class="casilla campo"><input type="checkbox" name="activo" value="1" @checked(old('activo', $servicio->activo))> Activo</label>
<button class="boton">Guardar</button>
