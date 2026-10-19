<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Libro extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'titulo',
        'isbn',
        'anio_publicacion',
        'numero_paginas',
        'descripcion',
        'stock_disponible',
    ];

    protected $casts = [
        'anio_publicacion' => 'integer',
        'numero_paginas' => 'integer',
        'stock_disponible' => 'integer',
    ];

    public function autores(): BelongsToMany
    {
        return $this->belongsToMany(Autor::class, 'autor_libro', 'libro_id', 'autor_id')
            ->using(AutorLibro::class)
            ->withPivot('orden_autor')
            ->orderByPivot('orden_autor');
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }

    /* ---- Scopes (Parte 3.2) ---- */

    /** Libros con stock disponible (> 0) */
    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('stock_disponible', '>', 0);
    }

    /** Libros por año de publicación */
    public function scopeDelAnio(Builder $query, int $anio): Builder
    {
        return $query->where('anio_publicacion', $anio);
    }

    /** Libros por autor (id o nombre) */
    public function scopePorAutor(Builder $query, int|string $autor): Builder
    {
        return $query->whereHas('autores', function (Builder $q) use ($autor) {
            is_numeric($autor)
                ? $q->where('autores.id', (int) $autor)
                : $q->where(function (Builder $w) use ($autor) {
                    $patron = '%'.mb_strtolower($autor, 'UTF-8').'%';

                    $w->whereRaw('LOWER(autores.nombre) LIKE ?', [$patron])
                      ->orWhereRaw('LOWER(autores.apellido) LIKE ?', [$patron]);
                });
        });
    }
}
