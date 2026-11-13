{{-- Detalle de una orden en el portal QR. Nunca mostrar costos del taller ni datos del dueño. --}}

@if ($orden->diagnostico)
    <p class="qr-sublabel">Diagnóstico del mecánico</p>
    <p class="qr-diagnostico">{{ $orden->diagnostico }}</p>
@endif

@if ($orden->trabajos->isNotEmpty())
    <p class="qr-sublabel">Trabajos</p>
    @foreach ($orden->trabajos as $t)
        <div class="qr-trabajo">
            <span class="qr-punto qr-punto-{{ $t->estado->value }}"></span>
            <span class="qr-trabajo-nombre">{{ $t->descripcion }}</span>
            <span class="qr-trabajo-tag qr-tag-{{ $t->estado->value }}">{{ $t->estado->label() }}</span>
        </div>
    @endforeach
@endif

@if ($orden->refacciones->isNotEmpty())
    <p class="qr-sublabel">Piezas utilizadas</p>
    <ul class="qr-ul">
        @foreach ($orden->refacciones as $r)
            <li>{{ $r->descripcion }}
                @if ($r->proporcionada_por_cliente)
                    <span style="font-size:.78rem; color:var(--tenue);">· la trajo usted</span>
                @endif
            </li>
        @endforeach
    </ul>
@endif
