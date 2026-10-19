<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Model
{
    use HasApiTokens;
    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'fecha_registro',
        'estado',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'password' => 'hashed',
    ];

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }

    /** Préstamos en estado activo de este usuario */
    public function prestamosActivos(): HasMany
    {
        return $this->prestamos()->where('estado', 'activo');
    }

    /** Accesor útil para UI/reportes */
    public function getDiasRetrasoMaximoAttribute(): int
    {
        return (int) $this->prestamos()
            ->where('estado', 'activo')
            ->where('fecha_devolucion_estimada', '<', now())
            ->get()
            ->max(fn (Prestamo $p) => $p->dias_retraso) ?? 0;
    }
}
