<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')->constrained('libros')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->timestamp('fecha_prestamo')->useCurrent();
            $table->date('fecha_devolucion_estimada');
            $table->timestamp('fecha_devolucion_real')->nullable();
            $table->string('estado')->default('activo'); // activo / devuelto / vencido
            $table->timestamps();
            $table->index(['estado', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
