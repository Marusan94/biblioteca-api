<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrestamoRequest;
use App\Models\Prestamo;
use App\Services\PrestamoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrestamoController extends Controller
{
    /**
     * GET /api/prestamos - listar con libro y usuario (evita N+1 con with()).
     */
    public function index(Request $request): JsonResponse
    {
        $estado = $request->validate(['estado' => ['nullable', 'in:activo,vencido,devuelto']])['estado'] ?? null;

        $prestamos = Prestamo::query()
            ->with([
                'libro:id,titulo,stock_disponible',
                'usuario:id,nombre,email,estado',
            ])
            ->when($estado, fn ($q) => $estado === 'vencido'
                ? $q->vencidos()
                : $q->where('estado', $estado))
            ->orderByDesc('fecha_prestamo')
            ->paginate(15)
            ->withQueryString();

        return response()->json([
            'data' => $prestamos->items(),
            'meta' => [
                'pagina_actual' => $prestamos->currentPage(),
                'ultima_pagina' => $prestamos->lastPage(),
                'total' => $prestamos->total(),
            ],
        ]);
    }

    /**
     * POST /api/prestamos - valida todas las reglas de negocio (Parte 5.1).
     */
    public function store(StorePrestamoRequest $request, PrestamoService $service): JsonResponse
    {
        try {
            $prestamo = $service->crear($request->validated());
        } catch (BusinessRuleException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return response()->json(['data' => $prestamo->load('libro', 'usuario')], 201);
    }

    /**
     * PUT /api/prestamos/{id}/devolver - marca devuelto y repone stock.
     */
    public function devolver(Prestamo $prestamo, PrestamoService $service): JsonResponse
    {
        try {
            $prestamo = $service->devolver($prestamo);
        } catch (BusinessRuleException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return response()->json(['data' => $prestamo]);
    }
}
