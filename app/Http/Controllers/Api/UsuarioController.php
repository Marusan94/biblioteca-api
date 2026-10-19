<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;

/**
 * Lista básica de usuarios activos para el formulario de préstamos (Parte 6.1).
 */
class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Usuario::query()
                ->where('estado', 'activo')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'email']),
        ]);
    }
}
