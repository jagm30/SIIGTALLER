@extends('layouts.app')

@section('titulo', 'Editar vehículo')

@section('contenido')
    <div class="encabezado">
        <h1>Editar vehículo</h1>
        <a href="{{ route('vehiculos.show', $vehiculo) }}">← Volver</a>
    </div>
    <form method="POST" action="{{ route('vehiculos.update', $vehiculo) }}" class="tarjeta">
        @method('PUT')
        @include('vehiculos._form')
    </form>
@endsection
