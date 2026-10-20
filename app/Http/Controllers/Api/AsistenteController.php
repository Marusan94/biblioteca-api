<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Libro;
use App\Models\Prestamo;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Asistente / chatbot LOCAL del sistema (frontend → /api/asistente).
 *
 * No usa servicios externos: interpreta el mensaje con reglas hechas en
 * PHP y responde consultando la base de datos real (libros, préstamos,
 * usuarios). Mantiene todo el stack del examen: Laravel + PostgreSQL.
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
        // Saludo / menú inicial
        if (preg_match('/(^|\s)(hola|buenas|hey|ayuda|menu|que puedes hacer|como funcionas)(\s|$)/', $m)) {
            return "¡Hola! Soy el asistente de la biblioteca. Puedo ayudarte con:\n"
                . "• «¿cuántas copias quedan de *título*?» → disponibilidad real\n"
                . "• «catálogo» → resumen de libros y sin stock\n"
                . "• «mis préstamos» → lo que tienes activo / vencido\n"
                . "• «usuarios» → resumen de usuarios\n"
                . "• «préstamos activos» → estado global de préstamos";
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

            return "👥 Hay {$total} usuarios registrados y {$activos} activos. "
                . "Los usuarios inactivos no pueden pedir préstamos nuevos.";
        }

        // Catálogo general
        if (str_contains($m, 'catalogo') || str_contains($m, 'cuantos libro') || str_contains($m, 'libros hay')) {
            $total = Libro::count();
            $sinStock = Libro::where('stock_disponible', 0)->count();

            return "📚 Hay {$total} libros en el catálogo; {$sinStock} están sin stock. "
                . "Usa la pestaña «Libros» para filtrar por autor, año y disponibilidad.";
        }

        // Disponibilidad de un libro puntual
        if (str_contains($m, 'disponib') || str_contains($m, 'queda') || str_contains($m, 'copias') || str_contains($m, 'stock')) {
            return $this->disponibilidad($m);
        }

        return "No entendí esa consulta. Prueba con «¿cuántas copias quedan de El río oscuro?», "
            . "«catálogo», «mis préstamos» o «ayuda» para ver todo lo que puedo hacer.";
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

        $lineas = "📋 Tienes {$activos->count()} préstamo(s) activo(s)"
            . ($vencidos->count() ? " y {$vencidos->count()} vencido(s)." : '.')
            . "\n";

        foreach ($activos as $p) {
            $lineas .= "• {$p->libro->titulo} (devolver antes: {$p->fecha_devolucion_estimada})\n";
        }

        foreach ($vencidos as $p) {
            $lineas .= "⚠️ {$p->libro->titulo} — VENCIDO (desde {$p->fecha_devolucion_estimada})\n";
        }

        return rtrim($lineas);
    }

    private function resumenPrestamos(): string
    {
        $activos = Prestamo::where('estado', 'activo')->count();
        $vencidos = Prestamo::vencidos()->count();
        $devueltos = Prestamo::where('estado', 'devuelto')->count();

        return "📖 Préstamos: {$activos} activos, {$vencidos} vencidos y {$devueltos} devueltos. "
            . "El máximo por usuario es 3 activos y las devoluciones reponen el stock automáticamente.";
    }

    private function disponibilidad(string $m): string
    {
        // Quita palabras de relleno para quedarnos con el título buscado
        $tokens = collect(preg_split('/[^a-z0-9áéíóúñ]+/', $m, -1, PREG_SPLIT_NO_EMPTY))
            ->reject(fn ($t) => in_array($t, ['cuantas', 'cuantos', 'copias', 'quedan', 'quedan', 'hay', 'del', 'de', 'la', 'el', 'los', 'las', 'un', 'una', 'en', 'el', 'libro', 'disponibles', 'disponible', 'stock', 'esta', 'existe', 'se', 'puede', 'pedir', 'para', 'como']))
            ->values();

        if ($tokens->isEmpty()) {
            return '¿De qué libro quieres saber la disponibilidad? Escribe, por ejemplo: «¿cuántas copias quedan de El río oscuro?»';
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

            return "📗 {$libro->titulo} — ".($stock > 0 ? "{$stock} copia(s) disponible(s)" : 'sin stock disponible');
        })->implode("\n");
    }
}
