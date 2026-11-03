@extends('layouts.app')

@section('titulo', 'Editar usuario')

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'administracion'])
    <h1>Editar usuario</h1>
    <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="tarjeta">
        @method('PUT')
        @include('usuarios._form')
    </form>
@endsection
