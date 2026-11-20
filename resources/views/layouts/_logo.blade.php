{{-- Logo del taller (Preferencias) o la llave si no se ha subido. Uso: @include('layouts._logo', ['clase' => 'marca-logo']) --}}
@if ($logoTaller)<img src="{{ $logoTaller }}" alt="" class="{{ $clase }}">@else🔧@endif
