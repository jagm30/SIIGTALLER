@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <div class="tarjeta" style="max-width: 420px; margin: 3rem auto;">
        <h1>🔧 {{ config('app.name') }}</h1>
        <p class="tenue">Inicia sesión para continuar.</p>

        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <div class="campo">
                <label for="email">Correo</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>
            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <div class="campo">
                <label class="casilla"><input type="checkbox" name="recordarme" value="1"> Mantener la sesión abierta</label>
            </div>
            <button class="boton" style="width: 100%;">Entrar</button>
        </form>
    </div>
@endsection
