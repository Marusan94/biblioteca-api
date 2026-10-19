<?php

namespace Database\Seeders;

use App\Models\Libro;
use Illuminate\Database\Seeder;

class LibroSeeder extends Seeder
{
    /**
     * 20 libros con relaciones autor-libro (Parte 2.2).
     * "autores" guarda los ids creados en AutorSeeder, en orden de firma.
     */
    public function run(): void
    {
        $catalogo = [
            ['titulo' => 'El río oscuro', 'anio' => 2010, 'paginas' => 320, 'stock' => 1, 'autores' => [1, 2]],
            ['titulo' => 'Sombras de medianoche', 'anio' => 2015, 'paginas' => 280, 'stock' => 3, 'autores' => [2]],
            ['titulo' => 'Poemas del alba', 'anio' => 2018, 'paginas' => 120, 'stock' => 2, 'autores' => [3]],
            ['titulo' => 'La ciudad entrerrejada', 'anio' => 2007, 'paginas' => 410, 'stock' => 1, 'autores' => [4, 5]],
            ['titulo' => 'Cuentos para viarjar', 'anio' => 2012, 'paginas' => 190, 'stock' => 5, 'autores' => [5]],
            ['titulo' => 'Crónicas del sur', 'anio' => 2001, 'paginas' => 350, 'stock' => 1, 'autores' => [6, 7]],
            ['titulo' => 'La casa de los espejos', 'anio' => 2019, 'paginas' => 260, 'stock' => 2, 'autores' => [7]],
            ['titulo' => 'Tierra adentro', 'anio' => 2020, 'paginas' => 300, 'stock' => 4, 'autores' => [8, 9]],
            ['titulo' => 'Nota de invierno', 'anio' => 2021, 'paginas' => 150, 'stock' => 2, 'autores' => [9]],
            ['titulo' => 'Vidas menores', 'anio' => 2014, 'paginas' => 380, 'stock' => 3, 'autores' => [10, 1]],
            ['titulo' => 'Los días contados', 'anio' => 2016, 'paginas' => 220, 'stock' => 0, 'autores' => [2, 3]],
            ['titulo' => 'Alergia al mundo', 'anio' => 2022, 'paginas' => 170, 'stock' => 6, 'autores' => [3]],
            ['titulo' => 'El mapa de tus manos', 'anio' => 2023, 'paginas' => 290, 'stock' => 2, 'autores' => [4]],
            ['titulo' => 'Humo sobre la nieve', 'anio' => 2013, 'paginas' => 310, 'stock' => 3, 'autores' => [5, 6, 7]],
            ['titulo' => 'Vecinos del silencio', 'anio' => 2009, 'paginas' => 240, 'stock' => 1, 'autores' => [8]],
            ['titulo' => 'Amores prestados', 'anio' => 2017, 'paginas' => 330, 'stock' => 2, 'autores' => [9, 10]],
            ['titulo' => 'El legado del agua', 'anio' => 2005, 'paginas' => 420, 'stock' => 0, 'autores' => [1]],
            ['titulo' => 'Nadie sale de herejía', 'anio' => 2024, 'paginas' => 270, 'stock' => 5, 'autores' => [2, 4]],
            ['titulo' => 'Historia de un legado', 'anio' => 1996, 'paginas' => 500, 'stock' => 2, 'autores' => [5, 9]],
            ['titulo' => 'El campo y las estrellas', 'anio' => 2008, 'paginas' => 200, 'stock' => 3, 'autores' => [6, 8, 10]],
        ];

        foreach ($catalogo as $i => $fila) {
            $libro = Libro::create([
                'titulo' => $fila['titulo'],
                'isbn' => sprintf('978-0-00%d-%04d-%d', $i, $i * 7 % 9999, $i % 10),
                'anio_publicacion' => $fila['anio'],
                'numero_paginas' => $fila['paginas'],
                'descripcion' => 'Ejemplar de demostración de la colección biblioteca.',
                'stock_disponible' => $fila['stock'],
            ]);

            // Relación N:M con orden_autor según el orden de firma (Parte 3.3)
            $pivot = [];
            foreach ($fila['autores'] as $orden => $autorId) {
                $pivot[$autorId] = ['orden_autor' => $orden + 1];
            }
            $libro->autores()->attach($pivot);
        }
    }
}
