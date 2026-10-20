<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLibroRequest;
use App\Http\Requests\UpdateLibroRequest;
use App\Models\Libro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibroController extends Controller
{
    /**
     * GET /api/libros - listar con paginación y filtros (título, autor, año).
     * Eager loading de autores para evitar N+1 (Parte 4.1 / criterio de calidad).
     */
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'titulo' => ['nullable', 'string', 'max:255'],
            'autor' => ['nullable', 'string', 'max:255'],
            'autor_id' => ['nullable', 'integer', 'min:1'],
            'anio' => ['nullable', 'integer', 'min:1450', 'max:2100'],
            'disponibles' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'in:titulo_asc,titulo_desc,anio_asc,anio_desc,stock_desc'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $ordenes = [
            'titulo_asc' => ['titulo', 'asc'],
            'titulo_desc' => ['titulo', 'desc'],
            'anio_asc' => ['anio_publicacion', 'asc'],
            'anio_desc' => ['anio_publicacion', 'desc'],
            'stock_desc' => ['stock_disponible', 'desc'],
        ];

        $libros = Libro::query()
            ->with('autores')
            ->when($filtros['titulo'] ?? null, fn ($q, $t) => $q->whereRaw('LOWER(titulo) LIKE ?', ['%'.mb_strtolower($t, 'UTF-8').'%']))
            ->when($filtros['autor'] ?? null, fn ($q, $a) => $q->porAutor($a))
            ->when($filtros['autor_id'] ?? null, fn ($q, $id) => $q->whereHas('autores', fn ($qa) => $qa->where('autores.id', $id)))
            ->when($filtros['anio'] ?? null, fn ($q, $a) => $q->delAnio((int) $a))
            ->when($filtros['disponibles'] ?? null, fn ($q, $d) => $d ? $q->disponibles() : $q)
            ->orderBy(...$ordenes[$filtros['orden'] ?? 'titulo_asc'])
            ->paginate($filtros['por_pagina'] ?? 10)
            ->withQueryString();

        $anios = Libro::query()
            ->select('anio_publicacion')
            ->distinct()
            ->orderByDesc('anio_publicacion')
            ->pluck('anio_publicacion');

        return response()->json([
            'data' => $libros->items(),
            'meta' => [
                'pagina_actual' => $libros->currentPage(),
                'ultima_pagina' => $libros->lastPage(),
                'por_pagina' => $libros->perPage(),
                'total' => $libros->total(),
                'anios' => $anios,
            ],
        ]);
    }

    /**
     * GET /api/libros/{id} - libro específico con autores.
     */
    public function show(Libro $libro): JsonResponse
    {
        $libro->load('autores');

        return response()->json([
            'data' => $libro->setAttribute('prestamos_count', $libro->prestamos()->count()),
        ]);
    }

    /**
     * POST /api/libros - crea libro y sincroniza autores con orden de firma.
     */
    public function store(StoreLibroRequest $request): JsonResponse
    {
        $libro = DB::transaction(function () use ($request) {
            $libro = Libro::create($request->safe()->except('autores'));

            $this->sincronizarAutores($libro, $request->validated('autores'));

            return $libro;
        });

        return response()->json(['data' => $libro->fresh('autores')], 201);
    }

    /**
     * PUT /api/libros/{id} - actualiza libro (y autores si vienen).
     */
    public function update(UpdateLibroRequest $request, Libro $libro): JsonResponse
    {
        DB::transaction(function () use ($request, $libro) {
            $libro->update($request->safe()->except('autores'));

            if ($request->has('autores')) {
                $this->sincronizarAutores($libro, $request->validated('autores'));
            }
        });

        return response()->json(['data' => $libro->fresh('autores')]);
    }

    /**
     * DELETE /api/libros/{id} - soft delete (Parte 4.1).
     */
    public function destroy(Libro $libro): JsonResponse
    {
        $libro->delete();

        return response()->json(['message' => 'Libro eliminado (soft delete).']);
    }

    /** Adjunta los autores guardando orden_autor 1..N según como vinieron */
    private function sincronizarAutores(Libro $libro, array $autores): void
    {
        $pivot = [];

        foreach (array_values($autores) as $orden => $autorId) {
            $pivot[$autorId] = ['orden_autor' => $orden + 1];
        }

        $libro->autores()->sync($pivot);
    }
}
