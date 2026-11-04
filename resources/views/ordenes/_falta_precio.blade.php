{{-- Campo pequeño para poner el precio a un trabajo o pieza que quedó sin precio. Recibe $accion (la URL). --}}
<form method="POST" action="{{ $accion }}" class="fila" style="justify-content: flex-end; flex-wrap: nowrap; gap: .25rem;">
    @csrf @method('PATCH')
    <input type="number" name="precio_unitario" step="0.01" min="0" required placeholder="Falta precio" aria-label="Precio con IVA"
           style="width: 120px; min-height: 36px; padding: .25rem .5rem; border-color: var(--alerta);">
    <button class="boton chico secundario">OK</button>
</form>
