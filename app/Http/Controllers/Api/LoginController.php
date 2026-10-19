<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Login para la API (Parte 4.4): devuelve un token personal de Sanctum.
     * POST /api/login
     */
    public function login(LoginRequest $request)
    {
        $usuario = Usuario::where('email', $request->validated('email'))->first();

        if (! $usuario || ! Hash::check($request->validated('password'), $usuario->password)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        if ($usuario->estado !== 'activo') {
            return response()->json(['message' => 'El usuario está inactivo.'], 403);
        }

        return response()->json([
            'token' => $usuario->createToken('api')->plainTextToken,
            'user' => $usuario->only(['id', 'nombre', 'email']),
        ]);
    }
}
