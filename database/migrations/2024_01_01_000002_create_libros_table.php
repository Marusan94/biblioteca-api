<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libros', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('isbn', 20)->nullable()->unique();
            $table->integer('anio_publicacion');
            $table->integer('numero_paginas')->nullable();
            $table->text('descripcion')->nullable();
            $table->integer('stock_disponible')->default(0);
            $table->timestamps();
            $table->softDeletes(); // Parte 4.1: eliminación lógica
            $table->index(['titulo', 'anio_publicacion', 'stock_disponible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libros');
    }
};
