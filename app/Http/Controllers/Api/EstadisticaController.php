<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Libro;
use App\Models\Prestamo;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;

/**
 * Datos del dashboard (Parte 6.1): GET /api/estadisticas
 */
class EstadisticaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_libros' => Libro::count(),
                'libros_sin_stock' => Libro::where('stock_disponible', '=', 0)->count(),
                'total_usuarios' => Usuario::count(),
                'prestamos_activos' => Prestamo::where('estado', 'activo')->count(),
                'prestamos_vencidos' => Prestamo::where('estado', 'activo')
                    ->where('fecha_devolucion_estimada', '<', now()->subDays(15)->toDateString())->count(),
                'prestamos_devueltos' => Prestamo::where('estado', 'devuelto')->count(),

                // Datos para los gráficos del dashboard (mejoras).
                // Sin SQL específico del motor: se cuentan los préstamos por rango de mes.
                'graficos' => $this->graficos(),
            ],
        ]);
    }

    /**
     * Préstamos por mes (últimos 6, con meses vacíos rellenados) y
     * top 5 de libros más prestados.
     */
    private function graficos(): array
    {
        $mesesLabels = [];
        $mesesDatos = [];

        for ($i = 5; $i >= 0; $i--) {
            $inicio = now()->subMonths($i)->startOfMonth();
            $fin = $inicio->copy()->endOfMonth();

            $mesesLabels[] = $inicio->translatedFormat('M Y');
            $mesesDatos[] = Prestamo::whereBetween('fecha_prestamo', [$inicio, $fin])->count();
        }

        $top = Prestamo::query()
            ->join('libros', 'libros.id', '=', 'prestamos.libro_id')
            ->selectRaw('libros.titulo as titulo, count(*) as total')
            ->groupBy('libros.id', 'libros.titulo')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'prestamos_por_mes' => [
                'labels' => $mesesLabels,
                'datos' => $mesesDatos,
            ],
            'top_libros' => [
                'labels' => $top->pluck('titulo')->all(),
                'datos' => $top->pluck('total')->all(),
            ],
        ];
    }
}
