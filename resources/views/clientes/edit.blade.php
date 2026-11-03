@extends('layouts.app')

@section('titulo', 'Editar cliente')

@section('contenido')
    <div class="encabezado">
        <h1>Editar cliente</h1>
        <a href="{{ route('clientes.show', $cliente) }}">← {{ $cliente->nombre_completo }}</a>
    </div>
    <form method="POST" action="{{ route('clientes.update', $cliente) }}" class="tarjeta">
        @method('PUT')
        @include('clientes._form')
    </form>
@endsection
