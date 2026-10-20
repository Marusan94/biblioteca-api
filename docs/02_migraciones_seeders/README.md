# Parte 2 — Configuración Laravel y Migraciones (20 pts)

El código está en el repo `biblioteca-api/`; aquí hay copias de los entregables.

## 2.1 Migraciones (`database/migrations/`)

| Archivo | Tabla |
|---|---|
| `2024_01_01_000001_create_autores_table.php` | autores |
| `2024_01_01_000002_create_libros_table.php` | libros (+ `softDeletes`) |
| `2024_01_01_000003_create_usuarios_table.php` | usuarios (+ `password` para Sanctum) |
| `2024_01_01_000004_create_prestamos_table.php` | prestamos (FKs con cascade) |
| `2024_01_01_000005_create_autor_libro_table.php` | pivote con `orden_autor` y UNIQUE(autor, libro) |

## 2.2 Seeders (`database/seeders/`)

- `AutorSeeder` — 10 autores.
- `LibroSeeder` — 20 libros con relaciones autor-libro (algunos con 2 o 3 autores, con `orden_autor`).
- `UsuarioSeeder` — 15 usuarios (el 15 está inactivo, contraseña demo `password`).
- `PrestamoSeeder` — 10 préstamos: 6 activos (ningún usuario supera el límite de 3), 2 vencidos, 2 devueltos; el stock se descuenta para ser consistente.
- `DatabaseSeeder` — orquesta los 4 en orden de dependencia.

## 2.3 Configuración PostgreSQL

`.env` (copia incluida, es el del proyecto):

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=biblioteca
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

> El `DB_PORT` corresponde al PostgreSQL portable usado para las pruebas; en
> un equipo con PostgreSQL estándar el puerto es `5432` (ver `.env.example`).

## Verificación

```bash
php artisan migrate:fresh --seed
```

Resultado real sobre PostgreSQL: 5 tablas + 10/20/15/10 registros.
