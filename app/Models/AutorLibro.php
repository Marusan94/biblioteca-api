<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Modelo de la tabla pivote autores <-> libros (Parte 3.3).
 * Incluye el campo orden_autor para el orden de firmas.
 */
class AutorLibro extends Pivot
{
    protected $table = 'autor_libro';

    public $timestamps = true;

    protected $fillable = [
        'autor_id',
        'libro_id',
        'orden_autor',
    ];

    protected $casts = [
        'orden_autor' => 'integer',
    ];

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Autor::class);
    }

    public function libro(): BelongsTo
    {
        return $this->belongsTo(Libro::class);
    }
}
