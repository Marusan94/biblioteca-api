# Parte 3 — Modelos Eloquent (25 pts)

Copias de los entregables (código en `biblioteca-api/app/Models/`).

## 3.1 Modelos con relaciones, fillable, casts y accesores

- **Autor** — `libros()` `belongsToMany` con `withPivot('orden_autor')`; cast `fecha_nacimiento:date`; tabla explícita `autores` (el pluralizador inglés diría `autors`).
- **Libro** — `SoftDeletes`; `autores()` ordenado por pivote; `prestamos()` `hasMany`; casts `anio_publicacion`, `numero_paginas`, `stock_disponible` enteros.
- **Usuario** — `HasApiTokens` (Sanctum); `hidden: password`; cast `password: hashed`; relacións `prestamos()`/`prestamosActivos()`; accesor `dias_retraso_maximo`.
- **Prestamo** — `libro()`/`usuario()` `belongsTo`; accesor `dias_retraso` (0 hasta que la fecha estimada vence); scopes `activos`/`vencidos` (+15 días).
- **AutorLibro** — modelo `Pivot` de la tabla `autor_libro`, con `orden_autor` como casts integer.

## 3.2 Scopes en el modelo Libro

| Scope | Qué hace |
|---|---|
| `disponibles()` | `stock_disponible > 0` |
| `delAnio($anio)` | por año de publicación |
| `porAutor($autor)` | por id del autor, o búsqueda parcial por nombre/apellido |

## 3.3 Pivot personalizado con "orden_autor"

`AutorLibro extends Pivot` con `$fillable = ['autor_id','libro_id','orden_autor']` y guardado en el mismo orden en que se manda el array de autores:

```php
$libro->autores()->sync([
    1 => ['orden_autor' => 1],
    2 => ['orden_autor' => 2],
]);
```

Los endpoints de libros guardan los autores respetando `orden_autor` (verificado E2E: `orden pivot: [(1,'Ana'),(2,'Carlos')]`).
