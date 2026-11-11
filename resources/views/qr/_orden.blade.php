{{-- Detalle de una orden en la página pública. Nunca mostrar costos del taller ni datos del dueño. --}}
@if ($orden->diagnostico)
    <p class="chico" style="margin: .4rem 0 .2rem;"><strong>Diagnóstico del mecánico</strong></p>
    <p style="margin: 0; white-space: pre-line;">{{ $orden->diagnostico }}</p>
@endif

@if ($orden->trabajos->isNotEmpty())
    <p class="chico" style="margin: .6rem 0 .2rem;"><strong>Trabajos</strong></p>
    <ul style="margin: 0; padding-left: 1.2rem;">
        @foreach ($orden->trabajos as $t)<li>{{ $t->descripcion }}</li>@endforeach
    </ul>
@endif

@if ($orden->refacciones->isNotEmpty())
    <p class="chico" style="margin: .6rem 0 .2rem;"><strong>Piezas utilizadas</strong></p>
    <ul style="margin: 0; padding-left: 1.2rem;">
        @foreach ($orden->refacciones as $r)
            <li>{{ $r->descripcion }} <span class="tenue">· {{ (float) $r->cantidad }} pza.@if ($r->proporcionada_por_cliente) · la trajo usted @endif</span></li>
        @endforeach
    </ul>
@endif
