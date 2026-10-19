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
            ],
        ]);
    }
}
