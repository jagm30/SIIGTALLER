@extends('layouts.app')

@section('titulo', 'Editar refacción')

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'catalogos'])
    <h1>Editar refacción</h1>
    <form method="POST" action="{{ route('refacciones.update', $refaccion) }}" class="tarjeta">
        @method('PUT')
        @include('refacciones._form')
    </form>
    <form method="POST" action="{{ route('refacciones.destroy', $refaccion) }}" onsubmit="return confirm('¿Eliminar esta refacción?')">
        @csrf @method('DELETE')
        <button class="boton-link">Eliminar refacción</button>
        <span class="tenue chico">Si ya se usó en órdenes, solo se desactiva.</span>
    </form>
@endsection
