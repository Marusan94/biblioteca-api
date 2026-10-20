# Parte 6 — Frontend Blade + JavaScript (15 pts)

Copias de los entregables (código en `biblioteca-api/resources/views/biblioteca.blade.php`,
`public/js/app.js` y `public/css/app.css`).

## Acceso

`php artisan serve` → `http://localhost:8000` → login (`usuario1@example.com` / `password`).

## 6.1 Funcionalidades

- **Lista de libros con búsqueda en tiempo real**: input conectado con debounce de 300 ms con AJAX a `GET /api/libros?titulo=...`.
- **Formulario para crear préstamo**: selects de usuarios activos y libros con stock, fecha estimada rellenada con +14 días, y mensajes de error/success vía toast.
- **Dashboard con estadísticas**:GET `/api/estadisticas` y tarjetas (libros, sin stock, usuarios, activos, vencidos, devueltos).

## 6.2 JavaScript vanilla (sin librerías)

- **fetch AJAX** con token Sanctum guardado en `localStorage` (login automático si el token sigue vigente).
- **Validación del lado cliente** del formulario de préstamos (fecha no anterior a hoy, campos obligatorios).
- **Manejo de errores y éxito**: `try/catch` en cada llamada con toast verde (éxito) o rojo (error de negocio o HTTP).
