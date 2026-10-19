<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Pobla la base de datos en orden de dependencia (Parte 2.2):
     * 10 autores, 20 libros con autores, 15 usuarios y 10 préstamos.
     */
    public function run(): void
    {
        $this->call([
            AutorSeeder::class,
            LibroSeeder::class,
            UsuarioSeeder::class,
            PrestamoSeeder::class,
        ]);
    }
}
