<?php

namespace App\Console\Commands;

use App\Models\Libro;
use App\Models\Usuario;
use Illuminate\Console\Command;

/**
 * Parte 5.2: reporte de biblioteca por consola.
 * Uso: php artisan reporte:biblioteca
 */
class ReporteBiblioteca extends Command
{
    protected $signature = 'reporte:biblioteca';

    protected $description = 'Reporte: libros más prestados, usuarios con préstamos vencidos y libros sin stock';

    public function handle(): int
    {
        /* 1. Libros más prestados (top 10) */
        $masPrestados = Libro::query()
            ->withCount('prestamos')
            ->orderByDesc('prestamos_count')
            ->limit(10)
            ->get(['id', 'titulo', 'stock_disponible']);

        $this->info('=== LIBROS MÁS PRESTADOS (top 10) ===');
        $this->table(
            ['ID', 'Título', 'Préstamos', 'Stock'],
            $masPrestados->map(fn (Libro $l) => [$l->id, $l->titulo, $l->prestamos_count, $l->stock_disponible])->all()
        );

        /* 2. Usuarios con préstamos vencidos (+15 días) */
        $conVencidos = Usuario::query()
            ->whereHas('prestamos', fn ($q) => $q->vencidos())
            ->withCount(['prestamos as vencidos' => fn ($q) => $q->vencidos()])
            ->get(['id', 'nombre', 'email']);

        $this->info('=== USUARIOS CON PRÉSTAMOS VENCIDOS ===');
        if ($conVencidos->isEmpty()) {
            $this->line('Ninguno.');
        } else {
            $this->table(
                ['ID', 'Nombre', 'Email', 'Vencidos'],
                $conVencidos->map(fn (Usuario $u) => [$u->id, $u->nombre, $u->email, $u->vencidos_count ?? ''])->all()
            );
        }

        /* 3. Libros sin stock */
        $sinStock = Libro::where('stock_disponible', '=', 0)->get(['id', 'titulo']);

        $this->info('=== LIBROS SIN STOCK ===');
        if ($sinStock->isEmpty()) {
            $this->line('Ninguno.');
        } else {
            $this->table(
                ['ID', 'Título'],
                $sinStock->map(fn (Libro $l) => [$l->id, $l->titulo])->all(),
            );
        }

        return self::SUCCESS;
    }
}
