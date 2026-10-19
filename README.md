# Biblioteca API — Laravel + PostgreSQL (+ frontend Blade/JS)

Sistema de gestión de biblioteca (prueba técnica): API REST autenticada con Sanctum,
reglas de negocio de préstamos y una interfaz web básica en Blade + JS vanilla.

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate

# editar .env con tus credenciales de PostgreSQL (DB_CONNECTION=pgsql)
php artisan migrate --seed
php artisan serve    # http://localhost:8000
```

- Login demo (web y API): `usuario1@example.com` / `password` (los 15 usuarios comparten contraseña de demo; el usuario 15 está inactivo).
- Si tu PostgreSQL está en otro puerto, ajusta `DB_PORT`.

## Comandos útiles

```bash
php artisan prestamos:marcar-vencidos   # marca vencidos (más de 15 días de retraso)
php artisan reporte:biblioteca          # reporte: top prestados, vencidos, sin stock
```

## API (resumen)

Todos los endpoints requieren `Authorization: Bearer <token>` salvo `POST /api/login`.

| Método | Ruta | Descripción |
|---|---|---|
| POST | /api/login | Token Sanctum |
| GET | /api/libros | paginación + filtros (titulo, autor, anio, disponibles) |
| GET | /api/libros/{id} | libro con autores (orden pivote) |
| POST/PUT | /api/libros[/{id}] | crear/actualizar (+autores con orden_autor) |
| DELETE | /api/libros/{id} | soft delete |
| GET/POST | /api/prestamos | listar (eager loading) / crear con reglas de negocio |
| PUT | /api/prestamos/{id}/devolver | marcar devuelto + reponer stock |
| GET | /api/estadisticas | métricas del dashboard |

Reglas de negocio: sin stock no se presta; máximo 3 préstamos activos por usuario;
solo usuarios activos prestan; vencidos (+15 días) se marcan automáticamente.

Front: `http://localhost:8000` (búsqueda en tiempo real, préstamos y dashboard).

Postman: colección `Parte 7 — Postman` en la carpeta superior.
