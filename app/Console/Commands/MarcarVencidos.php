<?php

namespace App\Console\Commands;

use App\Models\Prestamo;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Parte 5.1: los préstamos vencidos (más de 15 días) se marcan automáticamente.
 * Uso: php artisan prestamos:marcar-vencidos
 */
class MarcarVencidos extends Command
{
    protected $signature = 'prestamos:marcar-vencidos';

    protected $description = 'Marca como "vencido" los préstamos activos que superan los 15 días de retraso';

    public function handle(): int
    {
        $afectados = Prestamo::where('estado', 'activo')
            ->where('fecha_devolucion_estimada', '<', Carbon::now()->subDays(15)->toDateString())
            ->update(['estado' => 'vencido']);

        $this->info("Se marcaron {$afectados} préstamos como vencidos (+15 días de retraso).");

        return self::SUCCESS;
    }
}
