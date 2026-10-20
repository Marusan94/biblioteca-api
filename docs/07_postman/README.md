# Parte 7 — Testing con Postman (10 pts)

## Archivos

- `Biblioteca API.postman_collection.json` — colección para importar en Postman.
- `Biblioteca - Local.postman_environment.json` — entorno con las variables.

## 7.1 Qué incluye la colección

- **Todos los endpoints** de la API: login, libros (CRUD completo), préstamos (listar/crear/devolver) y estadísticas.
- **Variables de entorno**: `base_url`, `token`, credenciales demo, ids de libro/usuario para los casos.
- **Tests automatizados** en cada request (script `test` en Postman): estado HTTP esperado, estructura del JSON y persistencia del token en variables tras el login.
- **Casos de éxito y error**:
  - Login correcto (200) e inválido (401).
  - Crear libro (201) y libro sin título (422).
  - Préstamo normal (201), préstamo **sin stock** (409) y **usuario con 3 activos** (409).
  - Devolución (200) y doble devolución (409).

## 7.2 Documentación por endpoint

Cada request lleva la pestaña Description con ejemplo de REQUEST y RESPONSE ( cuerpos completos ). Se guardan igualmente en `biblioteca-api` (código).

## Flujo de ejecución

1. `composer install && php artisan migrate --seed && php artisan serve` en `biblioteca-api`.
2. Importar colección + entorno (lado derecho: entorno activo "Biblioteca - Local").
3. Ejecutar `POST login`: los tests guardan el token en la variable `token` y todo el resto ya funciona.
4. Ejecutar las demás carpetas → ver los "assertion passed" de cada test.
