<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Autor extends Model
{
    // El pluralizador inglés generaría "autors"; el nombre real es "autores"
    protected $table = 'autores';
    protected $fillable = [
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'nacionalidad',
        'biografia',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    /** N:M con la tabla pivote autor_libro (Parte 3.3) */
    public function libros(): BelongsToMany
    {
        return $this->belongsToMany(Libro::class, 'autor_libro', 'autor_id', 'libro_id')
            ->using(AutorLibro::class)
            ->withPivot('orden_autor')
            ->orderByPivot('orden_autor');
    }
}
