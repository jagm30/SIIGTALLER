@extends('layouts.app')

@section('titulo', 'Nuevo servicio')

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'catalogos'])
    <h1>Nuevo servicio</h1>
    <form method="POST" action="{{ route('servicios.store') }}" class="tarjeta">
        @include('servicios._form')
    </form>
@endsection
