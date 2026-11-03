@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
    @include('layouts._subnav', ['seccion' => 'administracion'])
    <div class="encabezado">
        <h1>Usuarios</h1>
        <a href="{{ route('usuarios.create') }}" class="boton">+ Nuevo usuario</a>
    </div>

    <div class="tarjeta tabla-scroll">
        <table>
            <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th class="ocultar-movil">Sucursal</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @foreach ($usuarios as $u)
                <tr @if (! $u->activo) class="tenue" @endif>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->rol->label() }}</td>
                    <td class="ocultar-movil">{{ $u->sucursal?->nombre ?? 'Todas' }}</td>
                    <td>{{ $u->activo ? 'Activo' : 'Desactivado' }}</td>
                    <td class="derecha"><a href="{{ route('usuarios.edit', $u) }}">Editar</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
