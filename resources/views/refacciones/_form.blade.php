@csrf
<div class="rejilla">
    <div class="campo">
        <label for="descripcion">Descripción</label>
        <input type="text" id="descripcion" name="descripcion" value="{{ old('descripcion', $refaccion->descripcion) }}" required>
    </div>
    <div class="campo">
        <label for="numero_parte">Número de parte</label>
        <input type="text" id="numero_parte" name="numero_parte" value="{{ old('numero_parte', $refaccion->numero_parte) }}">
    </div>
    <div class="campo">
        <label for="marca">Marca</label>
        <input type="text" id="marca" name="marca" value="{{ old('marca', $refaccion->marca) }}">
    </div>
    <div class="campo">
        <label for="categoria">Categoría</label>
        <input type="text" id="categoria" name="categoria" value="{{ old('categoria', $refaccion->categoria) }}" placeholder="Filtros, frenos, aceites…">
    </div>
    <div class="campo">
        <label for="unidad">Unidad</label>
        <select id="unidad" name="unidad">
            @foreach (\App\Enums\UnidadRefaccion::opciones() as $valor => $texto)
                <option value="{{ $valor }}" @selected(old('unidad', $refaccion->unidad?->value) === $valor)>{{ $texto }}</option>
            @endforeach
        </select>
    </div>
    @can('costos.ver')
        <div class="campo">
            <label for="costo">Costo de compra</label>
            <input type="number" step="0.01" min="0" id="costo" name="costo" value="{{ old('costo', $refaccion->costo) }}">
        </div>
    @endcan
    @can('precios.ver')
        <div class="campo">
            <label for="precio_venta">Precio de venta con IVA</label>
            <input type="number" step="0.01" min="0" id="precio_venta" name="precio_venta" value="{{ old('precio_venta', $refaccion->precio_venta) }}">
        </div>
    @endcan
</div>
<label class="casilla campo"><input type="checkbox" name="activo" value="1" @checked(old('activo', $refaccion->activo))> Activa</label>
<button class="boton">Guardar</button>
