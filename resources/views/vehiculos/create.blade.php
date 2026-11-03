@extends('layouts.app')

@section('titulo', 'Nuevo vehículo')

@section('contenido')
    <div class="encabezado">
        <h1>Nuevo vehículo</h1>
        <a href="{{ route('vehiculos.index') }}">← Vehículos</a>
    </div>
    <form method="POST" action="{{ route('vehiculos.store') }}" class="tarjeta">
        @include('vehiculos._form')
    </form>
@endsection
