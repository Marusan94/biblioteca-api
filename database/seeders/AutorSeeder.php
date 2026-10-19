<?php

namespace Database\Seeders;

use App\Models\Autor;
use Illuminate\Database\Seeder;

class AutorSeeder extends Seeder
{
    /** 10 autores de prueba (ficticios) */
    public function run(): void
    {
        $autores = [
            ['nombre' => 'Ana', 'apellido' => 'Torres', 'nacionalidad' => 'Colombia', 'fecha_nacimiento' => '1980-05-12', 'biografia' => 'Escritora de novela histórica.'],
            ['nombre' => 'Carlos', 'apellido' => 'Ruiz', 'nacionalidad' => 'México', 'fecha_nacimiento' => '1975-11-30', 'biografia' => 'Autor de bestsellers de terror.'],
            ['nombre' => 'Lucía', 'apellido' => 'Fernández', 'nacionalidad' => 'España', 'fecha_nacimiento' => '1991-03-08', 'biografia' => 'Poeta y ensayista.'],
            ['nombre' => 'Jorge', 'apellido' => 'Vargas', 'nacionalidad' => 'Argentina', 'fecha_nacimiento' => '1968-07-21', 'biografia' => 'Cronista urbano.'],
            ['nombre' => 'María', 'apellido' => 'Gómez', 'nacionalidad' => 'Chile', 'fecha_nacimiento' => '1985-09-14', 'biografia' => 'Literatura infantil y juvenil.'],
            ['nombre' => 'Pedro', 'apellido' => 'Salazar', 'nacionalidad' => 'Perú', 'fecha_nacimiento' => '1972-01-25', 'biografia' => 'Editorial y crítica literaria.'],
            ['nombre' => 'Valentina', 'apellido' => 'Molina', 'nacionalidad' => 'Colombia', 'fecha_nacimiento' => '1996-12-02', 'biografia' => 'Narrativa contemporánea.'],
            ['nombre' => 'Diego', 'apellido' => 'Castro', 'nacionalidad' => 'Uruguay', 'fecha_nacimiento' => '1983-04-17', 'biografia' => 'Cuento y traducción.'],
            ['nombre' => 'Sofía', 'apellido' => 'Ríos', 'nacionalidad' => 'Ecuador', 'fecha_nacimiento' => '1989-06-09', 'biografia' => 'Novela negra.'],
            ['nombre' => 'Martín', 'apellido' => 'Duarte', 'nacionalidad' => 'Paraguay', 'fecha_nacimiento' => '1970-10-19', 'biografia' => 'Historia y biografías.'],
        ];

        foreach ($autores as $autor) {
            Autor::create($autor);
        }
    }
}
