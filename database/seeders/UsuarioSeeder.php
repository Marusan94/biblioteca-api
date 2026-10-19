<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * 15 usuarios de prueba (Parte 2.2).
     * Contraseña demo para todos: "password" (datos ficticios).
     */
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            Usuario::create([
                'nombre' => "Usuario {$i}",
                'email' => "usuario{$i}@example.com",
                'telefono' => '30010010' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'fecha_registro' => now()->subDays($i * 5),
                'estado' => $i === 15 ? 'inactivo' : 'activo',
                'password' => 'password',
            ]);
        }
    }
}
