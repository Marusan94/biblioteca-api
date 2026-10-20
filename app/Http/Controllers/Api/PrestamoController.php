<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrestamoRequest;
use App\Models\Prestamo;
use App\Services\PrestamoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * GET /api/mis-prestamos - los préstamos del usuario autenticado,
     * con filtro opcional por estado (pestaña "Mis préstamos" del frontend).
     */
    public function misPrestamos(Request $request): JsonResponse
    {
        $estado = $request->validate(['estado' => ['nullable', 'in:activo,vencido,devuelto']])['estado'] ?? null;

        $prestamos = Prestamo::query()
            ->where('usuario_id', $request->user()->id)
            ->with(['libro:id,titulo,isbn,anio_publicacion'])
            ->when($estado, fn ($q) => $estado === 'vencido'
                ? $q->vencidos()
                : $q->where('estado', $estado))
            ->orderByDesc('fecha_prestamo')
            ->get(['id', 'usuario_id', 'libro_id', 'fecha_prestamo', 'fecha_devolucion_estimada', 'fecha_devolucion_real', 'estado']);

        return response()->json(['data' => $prestamos]);
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
     * GET /api/mis-prestamos/exportar - descarga CSV del historial
     * de préstamos del usuario autenticado.
     */
    public function exportarCsv(Request $request): StreamedResponse
    {
        $nombre = 'prestamos_' . now()->toDateString() . '.csv';

        $responseHeaders = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
        ];

        $usuarioId = $request->user()->id;

        $callback = function () use ($usuarioId) {
            // BOM para que Excel lea bien los acentos.
            echo "\xEF\xBB\xBF";
            $salida = fopen('php://output', 'wb');
            fputcsv($salida, ['Libro', 'Estado', 'Fecha de préstamo', 'Devolución estimada', 'Devolución real']);

            Prestamo::query()
                ->where('usuario_id', $usuarioId)
                ->with('libro:id,titulo')
                ->orderByDesc('fecha_prestamo')
                ->get(['id', 'libro_id', 'estado', 'fecha_prestamo', 'fecha_devolucion_estimada', 'fecha_devolucion_real'])
                ->each(function (Prestamo $p) use ($salida) {
                    fputcsv($salida, [
                        $p->libro->titulo,
                        $p->estado,
                        (string) $p->fecha_prestamo,
                        (string) $p->fecha_devolucion_estimada,
                        (string) ($p->fecha_devolucion_real ?? ''),
                    ]);
                });

            fclose($salida);
        };

        return new StreamedResponse($callback, 200, $responseHeaders);
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
