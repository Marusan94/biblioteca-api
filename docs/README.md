# 📚 docs/ — Entregables del examen (sistema de biblioteca)

Este folder centraliza todos los entregables del examen que **no son código del proyecto Laravel**
(para que el repo no tenga código duplicado); cada aqui tiene su propia sub-carpeta.

**Proyecto live:** Laravel 12 + PostgreSQL 16 + Sanctum · Correr con `iniciar.ps1` (ver README raíz).

| Folder | Parte del examen | Contenido |
|---|---|---|
| `01_sql_y_diagramas/` | **Parte 1** | Consultas SQL (esquema, lógica de negocio, datos), diagrama ER en draw.io + HTML + mermaid + PNG |
| `02_migraciones_seeders/` | **Parte 2** | Explicación + rutas exactas: el código real vive en `database/migrations/` y `database/seeders/` |
| `03_modelos/` | **Parte 3** | Explicación + rutas exactas: código real en `app/Models/` |
| `04_api_rest/` | **Parte 4** | Explicación + rutas exactas: código real en `app/Http/Controllers/`, `app/Http/Requests/`, `routes/api.php` |
| `05_logica_negocio/` | **Parte 5** | Explicación + rutas exactas: código real en `app/Services/PrestamoService.php`, `app/Exceptions/`, `app/Console/Commands/` |
| `06_frontend/` | **Parte 6** | Explicación + rutas exactas: código real en `resources/views/biblioteca.blade.php`, `public/js/app.js`, `public/css/app.css` |
| `07_postman/` | **Parte 7** | Colección Postman + environment (importables) |
| `08_teoria/` | **Preguntas teóricas** | `Respuestas_Teoricas.docx` (Word) + `Respuestas_Teoricas.md` |
| `Informe_Biblioteca.pdf` | Documento global | Resumen de cada punto + verificaciones + cómo correr la app |

En el proyecto Laravel **no hay código duplicado**: cada `README.md` de las partes 2–6 describe qué archivos implementan la parte y en qué carpeta residir del proyecto Fotosdo el repositorio.
