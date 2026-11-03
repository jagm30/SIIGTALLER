<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
{
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario)],
            'rol' => ['required', Rule::enum(Rol::class)],
            // El administrador puede no tener sucursal (ve todas); los demás sí la necesitan.
            'sucursal_id' => [Rule::requiredIf($this->input('rol') !== Rol::Administrador->value), 'nullable', 'exists:sucursales,id'],
            'telefono' => ['nullable', 'string', 'max:20'],
            // Al crear es obligatoria; al editar, vacía significa "no cambiarla".
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'activo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'email' => 'correo', 'sucursal_id' => 'sucursal', 'password' => 'contraseña'];
    }
}
