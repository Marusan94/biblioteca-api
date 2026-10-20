# ðŸ“š docs/ â€” Entregables (sistema de biblioteca)

Este folder centraliza todos los entregables que **no son cÃ³digo del proyecto Laravel**
(para que el repo no tenga cÃ³digo duplicado); cada aqui tiene su propia sub-carpeta.

**Proyecto live:** Laravel 12 + PostgreSQL 16 + Sanctum Â· Correr con `iniciar.ps1` (ver README raÃ­z).

| Folder | Parte del proyecto | Contenido |
|---|---|---|
| `01_sql_y_diagramas/` | **Parte 1** | Consultas SQL (esquema, lÃ³gica de negocio, datos), diagrama ER en draw.io + HTML + mermaid + PNG |
| `02_migraciones_seeders/` | **Parte 2** | ExplicaciÃ³n + rutas exactas: el cÃ³digo real vive en `database/migrations/` y `database/seeders/` |
| `03_modelos/` | **Parte 3** | ExplicaciÃ³n + rutas exactas: cÃ³digo real en `app/Models/` |
| `04_api_rest/` | **Parte 4** | ExplicaciÃ³n + rutas exactas: cÃ³digo real en `app/Http/Controllers/`, `app/Http/Requests/`, `routes/api.php` |
| `05_logica_negocio/` | **Parte 5** | ExplicaciÃ³n + rutas exactas: cÃ³digo real en `app/Services/PrestamoService.php`, `app/Exceptions/`, `app/Console/Commands/` |
| `06_frontend/` | **Parte 6** | ExplicaciÃ³n + rutas exactas: cÃ³digo real en `resources/views/biblioteca.blade.php`, `public/js/app.js`, `public/css/app.css` |
| `07_postman/` | **Parte 7** | ColecciÃ³n Postman + environment (importables) |
| `08_teoria/` | **Preguntas teÃ³ricas** | `Respuestas_Teoricas.docx` (Word) + `Respuestas_Teoricas.md` |
| `Informe_Biblioteca.pdf` | Documento global | Resumen de cada punto + verificaciones + cÃ³mo correr la app |

En el proyecto Laravel **no hay cÃ³digo duplicado**: cada `README.md` de las partes 2â€“6 describe quÃ© archivos implementan la parte y en quÃ© carpeta residir del proyecto Fotosdo el repositorio.

