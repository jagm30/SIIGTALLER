@extends('layouts.app')

@section('titulo', 'Editar servicio')

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'catalogos'])
    <h1>Editar servicio</h1>
    <form method="POST" action="{{ route('servicios.update', $servicio) }}" class="tarjeta">
        @method('PUT')
        @include('servicios._form')
    </form>
    <form method="POST" action="{{ route('servicios.destroy', $servicio) }}" onsubmit="return confirm('¿Eliminar este servicio?')">
        @csrf @method('DELETE')
        <button class="boton-link">Eliminar servicio</button>
        <span class="tenue chico">Si ya se usó en órdenes, solo se desactiva.</span>
    </form>
@endsection
