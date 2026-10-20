<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Libro;
use App\Models\Prestamo;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Asistente / chatbot local del sistema (frontend → /api/asistente).
 *
 * No usa servicios externos: interpreta el mensaje con reglas hechas en
 * PHP y responde consultando la base de datos real (libros, préstamos,
 * usuarios). Mantiene todo el stack del proyecto: Laravel + PostgreSQL.
 */
class AsistenteController extends Controller
{
    public function responder(Request $request): JsonResponse
    {
        $datos = $request->validate(['mensaje' => ['required', 'string', 'max:500']]);

        $mensaje = mb_strtolower(trim($datos['mensaje']));
        $mensaje = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', '¿', '?', '¡', '!'],
            ['a', 'e', 'i', 'o', 'u', 'u', '', '', '', ''],
            $mensaje
        );

        $respuesta = $this->interpretar($mensaje, $request->user());

        return response()->json(['data' => ['respuesta' => $respuesta]]);
    }

    private function interpretar(string $m, Usuario $usuario): string
    {
        // TODO: el matching es por frases exactas; con sinónimos o plurales
        // que no imagine se cae al fallback de abajo.
        // Saludo / menú inicial
        if (preg_match('/(^|\s)(hola|buenas|hey|ayuda|menu|que puedes hacer|como funcionas)(\s|$)/', $m)) {
            return "Puedo responder sobre:\n"
                . "- copias de un libro: «cuántas quedan de El río oscuro»\n"
                . "- catálogo: cuántos libros hay y cuáles sin stock\n"
                . "- «mis préstamos»: los tuyos, activos y vencidos\n"
                . "- usuarios registrados y estado global de préstamos";
        }

        // Mis préstamos (los del usuario autenticado)
        if (str_contains($m, 'mis prestamo')) {
            return $this->misPrestamos($usuario);
        }

        // Préstamos globales
        if (str_contains($m, 'prestamo')) {
            return $this->resumenPrestamos();
        }

        // Usuarios
        if (str_contains($m, 'usuario') && ! str_contains($m, 'mi')) {
            $total = Usuario::count();
            $activos = Usuario::where('estado', 'activo')->count();

            return "Usuarios registrados: {$total} (activos: {$activos}). "
                . "Los inactivos no pueden pedir préstamos nuevos.";
        }

        // Catálogo general
        if (str_contains($m, 'catalogo') || str_contains($m, 'cuantos libro') || str_contains($m, 'libros hay')) {
            $total = Libro::count();
            $sinStock = Libro::where('stock_disponible', 0)->count();

            return "Libros en catálogo: {$total}; sin stock: {$sinStock}. "
                . "En la pestaña «Libros» se pueden filtrar por autor, año y disponibilidad.";
        }

        // Disponibilidad de un libro puntual
        if (str_contains($m, 'disponib') || str_contains($m, 'queda') || str_contains($m, 'copias') || str_contains($m, 'stock')) {
            return $this->disponibilidad($m);
        }

        return "No entendí esa consulta. Prueba con «cuántas copias quedan de El río oscuro», "
            . "«catálogo», «mis préstamos» o «ayuda».";
    }

    private function misPrestamos(Usuario $usuario): string
    {
        $prestamos = Prestamo::query()
            ->where('usuario_id', $usuario->id)
            ->with('libro:id,titulo')
            ->orderByDesc('fecha_prestamo')
            ->get();

        $activos = $prestamos->filter(fn ($p) => $p->estado === 'activo');
        $vencidos = $prestamos->filter(fn ($p) => $p->estado === 'vencido');

        if ($prestamos->isEmpty()) {
            return 'No tienes préstamos registrados todavía.';
        }

        $lineas = "Préstamos activos: {$activos->count()}"
            . ($vencidos->count() ? " / vencidos: {$vencidos->count()}." : '.')
            . "\n";

        foreach ($activos as $p) {
            $lineas .= "- {$p->libro->titulo} (devolver antes: {$p->fecha_devolucion_estimada})\n";
        }

        foreach ($vencidos as $p) {
            $lineas .= "- {$p->libro->titulo} — VENCIDO (desde {$p->fecha_devolucion_estimada})\n";
        }

        return rtrim($lineas);
    }

    private function resumenPrestamos(): string
    {
        $activos = Prestamo::where('estado', 'activo')->count();
        $vencidos = Prestamo::vencidos()->count();
        $devueltos = Prestamo::where('estado', 'devuelto')->count();

        return "Préstamos: {$activos} activos, {$vencidos} vencidos y {$devueltos} devueltos. "
            . "Máximo 3 activos por usuario; devolver repone el stock.";
    }

    private function disponibilidad(string $m): string
    {
        // Quita palabras de relleno para quedarnos con el título buscado
        $tokens = collect(preg_split('/[^a-z0-9áéíóúñ]+/', $m, -1, PREG_SPLIT_NO_EMPTY))
            ->reject(fn ($t) => in_array($t, ['cuantas', 'cuantos', 'copias', 'quedan', 'hay', 'del', 'de', 'la', 'el', 'los', 'las', 'un', 'una', 'en', 'libro', 'disponibles', 'disponible', 'stock', 'esta', 'existe', 'se', 'puede', 'pedir', 'para', 'como']))
            ->values();

        if ($tokens->isEmpty()) {
            return '¿De qué libro quieres saber la disponibilidad? Escribe, por ejemplo: «cuántas copias quedan de El río oscuro».';
        }

        $libros = Libro::query()->with('autores')->where(function ($q) use ($tokens) {
            foreach ($tokens as $t) {
                $q->orWhereRaw('LOWER(titulo) LIKE ?', ['%'.mb_strtolower($t, 'UTF-8').'%']);
            }
        })->limit(3)->get();

        if ($libros->isEmpty()) {
            return 'No encontré ese título. Revisa el catálogo en la pestaña «Libros» o prueba con otra palabra.';
        }

        return $libros->map(function ($libro) {
            $stock = $libro->stock_disponible;

            return "{$libro->titulo}: ".($stock > 0 ? "{$stock} copia(s) disponible(s)" : 'sin stock');
        })->implode("\n");
    }
}
