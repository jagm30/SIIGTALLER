{{-- Datos generales de la orden. Usa las variables de ordenes/show ($user, $editable, $verPrecios…). --}}
<form method="POST" action="{{ route('ordenes.update', $orden) }}" class="tarjeta">
    @csrf @method('PATCH')
    <h2>Datos de la orden</h2>
    <div class="rejilla">
        @unless ($user->esMecanico())
            <div class="campo">
                <label for="mecanico_responsable_id">Mecánico responsable</label>
                <select id="mecanico_responsable_id" name="mecanico_responsable_id" @disabled(! $editable)>
                    <option value="">— Sin asignar —</option>
                    @foreach ($mecanicos as $m)
                        <option value="{{ $m->id }}" @selected($orden->mecanico_responsable_id === $m->id)>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="campo">
                <label for="fecha_promesa">Fecha promesa</label>
                <input type="date" id="fecha_promesa" name="fecha_promesa" value="{{ old('fecha_promesa', $orden->fecha_promesa?->format('Y-m-d')) }}" @disabled(! $editable)>
            </div>
            @if ($verPrecios)
                <div class="campo">
                    {{-- El descuento puede ser en pesos o en porcentaje del subtotal --}}
                    @php($enPorcentaje = old('descuento_tipo', $orden->descuento_porcentaje !== null ? 'porcentaje' : 'monto') === 'porcentaje')
                    <label for="descuento">Descuento</label>
                    <div class="fila" style="flex-wrap: nowrap;">
                        <select name="descuento_tipo" aria-label="Tipo de descuento" style="width: auto; flex-shrink: 0;" @disabled(! $editable)>
                            <option value="monto" @selected(! $enPorcentaje)>$</option>
                            <option value="porcentaje" @selected($enPorcentaje)>%</option>
                        </select>
                        <input type="number" step="0.01" min="0" id="descuento" name="descuento" @disabled(! $editable)
                               style="flex: 1; min-width: 0;"
                               value="{{ old('descuento', $enPorcentaje ? (float) $orden->descuento_porcentaje : $orden->descuento) }}">
                    </div>
                </div>
            @endif
        @endunless
    </div>
    <div class="campo">
        <label for="observaciones">Observaciones</label>
        <textarea id="observaciones" name="observaciones" @disabled(! $editable)>{{ old('observaciones', $orden->observaciones) }}</textarea>
    </div>
    @if ($editable)
        <button class="boton secundario">Guardar datos</button>
    @endif
</form>
