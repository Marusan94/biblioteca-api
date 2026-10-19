<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Libro;
use App\Models\Prestamo;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de negocio de préstamos (Parte 5.1):
 *  1. No se presta sin stock disponible.
 *  2. Máximo 3 préstamos activos por usuario.
 *  3. Solo usuarios activos pueden pedir préstamos.
 *  4. Al devolver se repone el stock y se registra la fecha real.
 */
class PrestamoService
{
    public function crear(array $datos): Prestamo
    {
        return DB::transaction(function () use ($datos) {
            $libro = Libro::lockForUpdate()->findOrFail($datos['libro_id']);
            $usuario = Usuario::findOrFail($datos['usuario_id']);

            if ($usuario->estado !== 'activo') {
                throw new BusinessRuleException('El usuario está inactivo y no puede pedir préstamos.', 409);
            }

            if ($libro->stock_disponible < 1) {
                throw new BusinessRuleException("No hay stock disponible del libro '{$libro->titulo}'.", 409);
            }

            $activos = $usuario->prestamos()->where('estado', 'activo')->count();

            if ($activos >= 3) {
                throw new BusinessRuleException("El usuario YA tiene 3 préstamos activos: no puede pedir otro (máximo: 3).", 409);
            }

            $libro->decrement('stock_disponible');

            return Prestamo::create([
                'libro_id' => $libro->id,
                'usuario_id' => $usuario->id,
                'fecha_prestamo' => now(),
                'fecha_devolucion_estimada' => $datos['fecha_devolucion_estimada'],
                'estado' => 'activo',
            ]);
        });
    }

    public function devolver(Prestamo $prestamo): Prestamo
    {
        $prestamo = DB::transaction(function () use ($prestamo) {
            $prestamo = Prestamo::lockForUpdate()->findOrFail($prestamo->id);

            if ($prestamo->estado === 'devuelto') {
                throw new BusinessRuleException('El préstamo ya fue devuelto.', 409);
            }

            $prestamo->update([
                'estado' => 'devuelto',
                'fecha_devolucion_real' => now(),
            ]);

            $prestamo->libro()->lockForUpdate()->first()->increment('stock_disponible');

            return $prestamo;
        });

        return $prestamo->fresh(['libro', 'usuario']);
    }
}
