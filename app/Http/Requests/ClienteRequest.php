<?php

namespace App\Http\Requests;

use App\Enums\TipoPersona;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validación para crear y editar clientes. El permiso ya lo revisa la ruta. */
class ClienteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tipo_persona' => ['required', Rule::enum(TipoPersona::class)],
            'nombre' => ['required', 'string', 'max:150'],
            'apellidos' => ['nullable', 'string', 'max:150'],
            'razon_social' => ['nullable', 'required_if:tipo_persona,moral', 'string', 'max:255'],
            // RFC mexicano: 12 caracteres (moral) o 13 (física). No es único: el genérico se repite.
            'rfc' => ['nullable', 'string', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i'],
            'telefono' => ['required', 'string', 'max:20'],
            'telefono_alt' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_persona' => 'tipo de persona',
            'razon_social' => 'razón social',
            'rfc' => 'RFC',
            'telefono' => 'teléfono',
            'telefono_alt' => 'teléfono alterno',
        ];
    }

    public function messages(): array
    {
        return [
            'rfc.regex' => 'El RFC no tiene un formato válido.',
            'razon_social.required_if' => 'La razón social es obligatoria para personas morales.',
        ];
    }
}
