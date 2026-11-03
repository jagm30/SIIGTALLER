@extends('layouts.app')

@section('titulo', 'Nuevo usuario')

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'administracion'])
    <h1>Nuevo usuario</h1>
    <form method="POST" action="{{ route('usuarios.store') }}" class="tarjeta">
        @include('usuarios._form')
    </form>
@endsection
