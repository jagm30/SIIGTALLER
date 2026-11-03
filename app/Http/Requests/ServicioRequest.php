<?php

namespace App\Http\Requests;

use App\Enums\TipoCobro;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServicioRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'clave' => ['nullable', 'string', 'max:20', Rule::unique('servicios', 'clave')->ignore($this->route('servicio'))],
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'categoria' => ['nullable', 'string', 'max:40'],
            'tipo_cobro' => ['required', Rule::enum(TipoCobro::class)],
            // Con IVA incluido. Si el taller opera sin precios, el formulario no lo muestra.
            'precio' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'tiempo_estimado_min' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'intervalo_meses' => ['nullable', 'integer', 'min:1', 'max:120'],
            'intervalo_km' => ['nullable', 'integer', 'min:100', 'max:500000'],
            'activo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_cobro' => 'tipo de cobro',
            'tiempo_estimado_min' => 'tiempo estimado',
            'intervalo_meses' => 'intervalo en meses',
            'intervalo_km' => 'intervalo en kilómetros',
        ];
    }
}
