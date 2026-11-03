@extends('layouts.app')

@section('titulo', 'Nuevo cliente')

@section('contenido')
    <div class="encabezado">
        <h1>Nuevo cliente</h1>
        <a href="{{ route('clientes.index') }}">← Clientes</a>
    </div>
    <form method="POST" action="{{ route('clientes.store') }}" class="tarjeta">
        @include('clientes._form')
    </form>
@endsection
