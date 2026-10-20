# Parte 4 — API REST (30 pts)

Copias de los entregables (código en `biblioteca-api/app/Http/...` y `routes/api.php`).

## 4.1 Endpoints

Todos requieren `Authorization: Bearer <token>` salvo `POST /api/login`.

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/login` | Token Sanctum |
| GET | `/api/libros` | Paginación + filtros (`titulo`, `autor`, `anio`, `disponibles`, `por_pagina`) |
| GET | `/api/libros/{id}` | Libro con autores ordenados por pivote |
| POST | `/api/libros` | Crear libro con autores (recibe `autores: [1,2,...]`) |
| PUT | `/api/libros/{id}` | Actualizar (parcial) + re-sincroniza autores |
| DELETE | `/api/libros/{id}` | **Soft delete** |
| GET | `/api/prestamos` | Con libro y usuario en eager loading (sin N+1) |
| POST | `/api/prestamos` | Valida reglas de negocio (Parte 5) |
| PUT | `/api/prestamos/{id}/devolver` | Marca devuelto + repone stock |
| GET | `/api/estadisticas` | Métricas del dashboard |
| GET | `/api/usuarios` | Usuarios activos (para el formulario web) |

## 4.2 Form Requests

`StoreLibroRequest`/`UpdateLibroRequest` (título obligatorio, ISBN único, año 1450–2100, autores existentes y sin repetir), `StorePrestamoRequest` (FKs existen, fecha estimada ≥ hoy) y `LoginRequest`.

Errores de validación con mensajes en JSON (`422` + `errors`), verificado en Postman.

## 4.3 Respuestas JSON consistentes

Formato uniforme: `{"data": ...}` y `{"meta": {...}}` con paginación; estados HTTP correctos (`200`, `201`, `401`, `404`, `409`, `422`).

## 4.4 Autenticación con Sanctum

`laravel/sanctum` + `HasApiTokens` en `Usuario` + middleware `auth:sanctum` en el grupo de rutas. `POST /api/login` devuelve el token; un endpoint sin token responde `401`.
