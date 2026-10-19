<?php

use App\Http\Controllers\Api\EstadisticaController;
use App\Http\Controllers\Api\LibroController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\PrestamoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST Biblioteca (Parte 4)
|--------------------------------------------------------------------------
| Login público con Sanctum; el resto de endpoints requieren token
| (Authorization: Bearer <token>).
*/

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('libros', LibroController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy']);

    Route::get('/prestamos', [PrestamoController::class, 'index']);
    Route::post('/prestamos', [PrestamoController::class, 'store']);
    Route::put('/prestamos/{prestamo}/devolver', [PrestamoController::class, 'devolver']);

    Route::get('/estadisticas', [EstadisticaController::class, 'index']);
    Route::get('/usuarios', [\App\Http\Controllers\Api\UsuarioController::class, 'index']);
});
