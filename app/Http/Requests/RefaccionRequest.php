<?php

namespace App\Http\Requests;

use App\Enums\UnidadRefaccion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefaccionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'numero_parte' => ['nullable', 'string', 'max:50',
                // El mismo número de parte puede existir en dos marcas distintas.
                Rule::unique('refacciones')->where('marca', $this->input('marca'))->ignore($this->route('refaccion'))],
            'descripcion' => ['required', 'string', 'max:200'],
            'marca' => ['nullable', 'string', 'max:60'],
            'categoria' => ['nullable', 'string', 'max:40'],
            'unidad' => ['required', Rule::enum(UnidadRefaccion::class)],
            'costo' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'precio_venta' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'activo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['numero_parte' => 'número de parte', 'precio_venta' => 'precio de venta'];
    }

    public function messages(): array
    {
        return ['numero_parte.unique' => 'Ya existe una refacción con ese número de parte y marca.'];
    }
}
