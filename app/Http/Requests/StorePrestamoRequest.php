<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrestamoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libro_id' => ['required', 'integer', 'exists:libros,id'],
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'fecha_devolucion_estimada' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_devolucion_estimada.after_or_equal' => 'La fecha estimada no puede ser anterior a hoy.',
            'libro_id.exists' => 'El libro indicado no existe.',
            'usuario_id.exists' => 'El usuario indicado no existe.',
        ];
    }
}
