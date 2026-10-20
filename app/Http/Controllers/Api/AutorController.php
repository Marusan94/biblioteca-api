<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Autor;
use Illuminate\Http\JsonResponse;

class AutorController extends Controller
{
    /**
     * GET /api/autores - catálogo simple para el filtro combinable del frontend.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Autor::query()
                ->orderBy('nombre')
                ->orderBy('apellido')
                ->get(['id', 'nombre', 'apellido']),
        ]);
    }
}
