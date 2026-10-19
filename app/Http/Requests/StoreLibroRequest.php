<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLibroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20', 'unique:libros,isbn'],
            'anio_publicacion' => ['required', 'integer', 'min:1450', 'max:2100'],
            'numero_paginas' => ['nullable', 'integer', 'min:1', 'max:50000'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'stock_disponible' => ['required', 'integer', 'min:0', 'max:100000'],
            'autores' => ['required', 'array', 'min:1', 'max:10'],
            'autores.*' => ['integer', 'distinct', 'exists:autores,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'autores.required' => 'Un libro debe tener al menos un autor.',
            'autores.*.exists' => 'Uno de los autores no existe.',
            'isbn.unique' => 'El ISBN ya está registrado.',
        ];
    }
}
