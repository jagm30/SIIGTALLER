@extends('layouts.app')

@section('titulo', 'Nueva refacción')

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'catalogos'])
    <h1>Nueva refacción</h1>
    <form method="POST" action="{{ route('refacciones.store') }}" class="tarjeta">
        @include('refacciones._form')
    </form>
@endsection
