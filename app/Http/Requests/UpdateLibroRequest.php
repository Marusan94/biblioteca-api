<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLibroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'string', 'max:255'],
            'isbn' => ['sometimes', 'nullable', 'string', 'max:20',
                Rule::unique('libros', 'isbn')->ignore($this->route('libro'))],
            'anio_publicacion' => ['sometimes', 'integer', 'min:1450', 'max:2100'],
            'numero_paginas' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50000'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'stock_disponible' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'autores' => ['sometimes', 'array', 'min:1', 'max:10'],
            'autores.*' => ['integer', 'distinct', 'exists:autores,id'],
        ];
    }
}
