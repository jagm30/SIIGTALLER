<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Guardado de la inspección (recepción o entrega) mientras está en borrador.
 *
 * Aquí todo es opcional a propósito: recepción puede guardar a medias si llega otro
 * cliente. Lo obligatorio se revisa al final, en Inspeccion::completar().
 */
class InspeccionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'km' => ['nullable', 'integer', 'min:0', 'max:3000000'],
            'km_justificacion' => ['nullable', 'string', 'max:255'],
            'nivel_combustible' => ['nullable', 'integer', 'between:0,8'],
            'placas' => ['nullable', 'string', 'max:15'],
            'sin_placas' => ['boolean'],
            'persona_nombre' => ['nullable', 'string', 'max:150'],
            'persona_telefono' => ['nullable', 'string', 'max:20'],
            'persona_es_cliente' => ['boolean'],
            'sin_danios' => ['nullable', 'boolean'],
            'objetos_personales' => ['nullable', 'string', 'max:2000'],
            'cliente_retiro_valores' => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'acepta_terminos' => ['boolean'],
            'firmante_nombre' => ['nullable', 'string', 'max:150'],

            // respuestas[<id del punto>][valor] y [comentario]
            'respuestas' => ['array'],
            'respuestas.*.valor' => ['nullable', 'string', 'max:30'],
            'respuestas.*.comentario' => ['nullable', 'string', 'max:255'],

            // fotos[<id del ángulo>] = archivo; la firma llega como PNG en base64 desde el canvas
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], // 10 MB
            'firma' => ['nullable', 'string', 'starts_with:data:image/png;base64,', 'max:700000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'km' => 'kilometraje',
            'km_justificacion' => 'justificación del kilometraje',
            'nivel_combustible' => 'nivel de gasolina',
            'persona_nombre' => 'nombre de quien entrega',
            'firmante_nombre' => 'nombre de quien firma',
            'fotos.*' => 'foto',
        ];
    }
}
