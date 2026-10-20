# Parte 1: Análisis y Diseño de Base de Datos — Sistema de Biblioteca

Entregables de la Parte 1 de la prueba técnica (PostgreSQL + diagrama ER).

## Archivos

| Archivo | Contenido |
|---|---|
| `01_esquema.sql` | DDL: 5 tablas (autores, libros, usuarios, prestamos + autor_libro), tipos ENUM, FK/CHECK/UNIQUE e índices |
| `02_logica_negocio.sql` | Triggers de negocio, `updated_at` automático y vistas de consulta |
| `03_datos_ejemplo.sql` | Datos ficticios de demostración |
| `diagrama_er.drawio` | Diagrama ER (editable en app.diagrams.net) |
| `diagrama_er.png` | Diagrama exportado para referencia rápida |

## Cómo ejecutar

```bash
createdb biblioteca
psql -d biblioteca -f 01_esquema.sql
psql -d biblioteca -f 02_logica_negocio.sql
psql -d biblioteca -f 03_datos_ejemplo.sql   # opcional (demo)
```

## Diseño

- **Relaciones**: autor N:M libro (tabla pivote `autor_libro` con `orden_autor` para el orden de firmas), usuario 1:N prestamos, libro 1:N prestamos.
- **ENUMs**: `estado_usuario ('activo','inactivo')`, `estado_prestamo ('activo','devuelto','vencido')`.
- **Soft deletes**: `libros.deleted_at` listo para la Parte 4.
- **Reglas con triggers**: solo usuarios activos prestan; máximo 3 préstamos activos por usuario; el stock se descuenta al prestar y se repone al devolver; `updated_at` automático.
- **Vistas**: `vw_prestamos_activos` (con días de retraso) y `vw_prestamos_vencidos` (retraso > 15 días, como pide la Parte 5.1).
- **Índices** en FKs, estado, año, título y stock.

## Correcciones al esquema de referencia (el original falla tal cual)

1. **`año_publicacion` vs `anio_publicacion`**: la columna del modelo de referencia se llama `año_publicacion` pero su CHECK usa `anio_publicacion` → error al crear la tabla. Se unificó como `anio_publicacion` (también más seguro con Eloquent).
2. **`EXTRACT(YEAR FROM CURRENT_DATE)` en CHECK**: PostgreSQL lo rechaza porque `CURRENT_DATE` no es inmutable. Se usa un límite constante (1450–2100).
3. **`fecha_prestamo::DATE` en CHECK**: el cast de timestamptz a date depende de la zona horaria (no inmutable) → también fallaría. Se compara directamente `fecha_devolucion_estimada >= fecha_prestamo`.

> Los datos de ejemplo son ficticios (correos `@example.com`, ISBN ilustrativos).
