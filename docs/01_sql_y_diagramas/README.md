# Parte 1: AnÃ¡lisis y DiseÃ±o de Base de Datos â€” Sistema de Biblioteca

Entregables de la Parte 1 de la prueba tÃ©cnica (PostgreSQL + diagrama ER).

## Archivos

| Archivo | Contenido |
|---|---|
| `01_esquema.sql` | DDL: 5 tablas (autores, libros, usuarios, prestamos + autor_libro), tipos ENUM, FK/CHECK/UNIQUE e Ã­ndices |
| `02_logica_negocio.sql` | Triggers de negocio, `updated_at` automÃ¡tico y vistas de consulta |
| `03_datos_ejemplo.sql` | Datos ficticios de demostraciÃ³n |
| `diagrama_er.drawio` | Diagrama ER (editable en app.diagrams.net) |
| `diagrama_er.png` | Diagrama exportado para referencia rÃ¡pida |

## CÃ³mo ejecutar

```bash
createdb biblioteca
psql -d biblioteca -f 01_esquema.sql
psql -d biblioteca -f 02_logica_negocio.sql
psql -d biblioteca -f 03_datos_ejemplo.sql   # opcional (demo)
```

## DiseÃ±o

- **Relaciones**: autor N:M libro (tabla pivote `autor_libro` con `orden_autor` para el orden de firmas), usuario 1:N prestamos, libro 1:N prestamos.
- **ENUMs**: `estado_usuario ('activo','inactivo')`, `estado_prestamo ('activo','devuelto','vencido')`.
- **Soft deletes**: `libros.deleted_at` listo para la Parte 4.
- **Reglas con triggers**: solo usuarios activos prestan; mÃ¡ximo 3 prÃ©stamos activos por usuario; el stock se descuenta al prestar y se repone al devolver; `updated_at` automÃ¡tico.
- **Vistas**: `vw_prestamos_activos` (con dÃ­as de retraso) y `vw_prestamos_vencidos` (retraso > 15 dÃ­as, como pide la Parte 5.1).
- **Ãndices** en FKs, estado, aÃ±o, tÃ­tulo y stock.

## Correcciones al esquema de referencia (el original falla tal cual)

1. **`aÃ±o_publicacion` vs `anio_publicacion`**: la columna del modelo de referencia se llama `aÃ±o_publicacion` pero su CHECK usa `anio_publicacion` â†’ error al crear la tabla. Se unificÃ³ como `anio_publicacion` (tambiÃ©n mÃ¡s seguro con Eloquent).
2. **`EXTRACT(YEAR FROM CURRENT_DATE)` en CHECK**: PostgreSQL lo rechaza porque `CURRENT_DATE` no es inmutable. Se usa un lÃ­mite constante (1450â€“2100).
3. **`fecha_prestamo::DATE` en CHECK**: el cast de timestamptz a date depende de la zona horaria (no inmutable) â†’ tambiÃ©n fallarÃ­a. Se compara directamente `fecha_devolucion_estimada >= fecha_prestamo`.

> Los datos de ejemplo son ficticios (correos `@example.com`, ISBN ilustrativos).

