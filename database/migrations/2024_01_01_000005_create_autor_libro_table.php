<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla pivote N:M autores <-> libros (Parte 3.3: incluye orden_autor)
        Schema::create('autor_libro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('autor_id')->constrained('autores')->cascadeOnDelete();
            $table->foreignId('libro_id')->constrained('libros')->cascadeOnDelete();
            $table->unsignedInteger('orden_autor')->default(1);
            $table->timestamps();
            $table->unique(['autor_id', 'libro_id']);
            $table->index('libro_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autor_libro');
    }
};
