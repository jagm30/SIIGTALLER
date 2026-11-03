<?php

namespace App\Http\Requests;

use App\Enums\Combustible;
use App\Enums\TipoCarroceria;
use App\Enums\Transmision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación para crear y editar vehículos.
 *
 * El modelo se elige de la lista, o se escribe uno nuevo (marca + modelo + carrocería)
 * si el usuario tiene permiso de alta rápida. Ese modelo queda "por verificar".
 */
class VehiculoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // El VIN se guarda en mayúsculas y sin espacios; lo normalizamos antes de revisar si ya existe.
        if ($this->filled('vin')) {
            $this->merge(['vin' => strtoupper(preg_replace('/\s+/', '', $this->input('vin')))]);
        }
    }

    public function rules(): array
    {
        $vehiculo = $this->route('vehiculo');
        $nuevoModelo = $this->filled('nuevo_modelo');

        return [
            'cliente_id' => ['required', 'exists:clientes,id'],
            'modelo_vehiculo_id' => [Rule::requiredIf(! $nuevoModelo), 'nullable', 'exists:modelos_vehiculo,id'],

            // Alta rápida de un modelo que no está en el catálogo
            'nuevo_modelo' => ['nullable', 'string', 'max:80'],
            'nueva_marca' => [Rule::requiredIf($nuevoModelo), 'nullable', 'string', 'max:60'],
            'tipo_carroceria' => [Rule::requiredIf($nuevoModelo), 'nullable', Rule::enum(TipoCarroceria::class)],

            'anio' => ['nullable', 'integer', 'between:1950,' . (now()->year + 1)],
            'version' => ['nullable', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:40'],
            'placas' => ['nullable', 'string', 'max:15'],
            // El VIN tiene 17 caracteres y nunca usa I, O ni Q.
            'vin' => ['nullable', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/',
                Rule::unique('vehiculos', 'vin')->ignore($vehiculo)],
            'numero_motor' => ['nullable', 'string', 'max:30'],
            'combustible' => ['nullable', Rule::enum(Combustible::class)],
            'transmision' => ['nullable', Rule::enum(Transmision::class)],
            'km_actual' => ['nullable', 'integer', 'min:0', 'max:3000000'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cliente_id' => 'cliente',
            'modelo_vehiculo_id' => 'modelo',
            'nueva_marca' => 'marca',
            'tipo_carroceria' => 'tipo de carrocería',
            'anio' => 'año',
            'vin' => 'número de serie (VIN)',
            'km_actual' => 'kilometraje',
        ];
    }

    public function messages(): array
    {
        return [
            'vin.unique' => 'Ya existe un vehículo con ese número de serie.',
            'vin.regex' => 'El número de serie solo lleva letras y números, sin I, O ni Q.',
        ];
    }
}
