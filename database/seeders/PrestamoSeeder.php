<?php

namespace Database\Seeders;

use App\Models\Prestamo;
use Illuminate\Database\Seeder;

class PrestamoSeeder extends Seeder
{
    /**
     * 10 préstamos de prueba (Parte 2.2):
     * - 6 activos (ningún usuario pasa de 3 activos)
     * - 2 vencidos (activos con fecha estimada vencida hace más de 15 días)
     * - 2 devueltos
     * El stock se descuenta según los préstamos vivos para ser consistente.
     */
    public function run(): void
    {
        $activos = [
            ['libro_id' => 1, 'usuario_id' => 1],
            ['libro_id' => 2, 'usuario_id' => 1],
            ['libro_id' => 3, 'usuario_id' => 2],
            ['libro_id' => 8, 'usuario_id' => 2],
            ['libro_id' => 14, 'usuario_id' => 3],
            ['libro_id' => 18, 'usuario_id' => 3],
        ];

        $vencidos = [
            ['libro_id' => 4, 'usuario_id' => 4], // con 0 libros prestados de stock 0... no, libro 4 sin stock se ajusta abajo
            ['libro_id' => 7, 'usuario_id' => 5],
        ];

        $devueltos = [
            ['libro_id' => 9, 'usuario_id' => 6],
            ['libro_id' => 10, 'usuario_id' => 7],
        ];

        // Préstamos activos recientes
        foreach ($activos as $p) {
            Prestamo::create([
                'libro_id' => $p['libro_id'],
                'usuario_id' => $p['usuario_id'],
                'fecha_prestamo' => now()->subDays(3),
                'fecha_devolucion_estimada' => now()->addDays(14)->toDateString(),
                'estado' => 'activo',
            ]);
            $this->descontarStock($p['libro_id']);
        }

        // Préstamos vencidos (se los lleva abajo el comando prestamos:marcar-vencidos)
        foreach ($vencidos as $p) {
            Prestamo::create([
                'libro_id' => $p['libro_id'],
                'usuario_id' => $p['usuario_id'],
                'fecha_prestamo' => now()->subDays(30),
                'fecha_devolucion_estimada' => now()->subDays(20)->toDateString(),
                'estado' => 'activo',
            ]);
            $this->descontarStock($p['libro_id']);
        }

        // Préstamos ya devueltos (no afectan el stock)
        foreach ($devueltos as $p) {
            Prestamo::create([
                'libro_id' => $p['libro_id'],
                'usuario_id' => $p['usuario_id'],
                'fecha_prestamo' => now()->subDays(40),
                'fecha_devolucion_estimada' => now()->subDays(26)->toDateString(),
                'fecha_devolucion_real' => now()->subDays(22),
                'estado' => 'devuelto',
            ]);
        }
    }

    private function descontarStock(int $libroId): void
    {
        \Illuminate\Support\Facades\DB::table('libros')
            ->where('id', $libroId)
            ->decrement('stock_disponible');
    }
}
