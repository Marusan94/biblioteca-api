<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prestamo extends Model
{
    protected $fillable = [
        'libro_id',
        'usuario_id',
        'fecha_prestamo',
        'fecha_devolucion_estimada',
        'fecha_devolucion_real',
        'estado',
    ];

    protected $casts = [
        'fecha_prestamo' => 'datetime',
        'fecha_devolucion_estimada' => 'date',
        'fecha_devolucion_real' => 'datetime',
    ];

    public function libro(): BelongsTo
    {
        return $this->belongsTo(Libro::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /** Préstamos activos (aprobados y no devueltos) */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }

    /** Vencidos: activos con fecha estimada vencida hace más de 15 días */
    public function scopeVencidos(Builder $query): Builder
    {
        return $query->where('estado', 'activo')
            ->where('fecha_devolucion_estimada', '<', now()->subDays(15)->toDateString());
    }

    /** Días de retraso respecto a la fecha estimada (0 si todavía no vence) */
    public function getDiasRetrasoAttribute(): int
    {
        $estimada = $this->fecha_devolucion_estimada;

        if (! $estimada || $this->estado === 'devuelto' || $estimada->greaterThanOrEqualTo(today())) {
            return 0;
        }

        return (int) today()->diffInDays($estimada); // estimada ya vencida -> positivo
    }
}
