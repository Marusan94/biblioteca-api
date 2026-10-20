# Biblioteca API Laravel + PostgreSQL (+ frontend Blade/JS)

Sistema de gestión de biblioteca con Laravel y PostgreSQL. La API va
autenticada con Sanctum y las reglas de préstamo viven en un servicio
propio; el frontend es Blade + JS sin frameworks. Las decisiones de
camino (y qué pagué por cada una) están en la sección «Decisiones».

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

### Endpoints añadidos (mejoras)

| Método | Ruta | Descripción |
|---|---|---|
| GET | /api/prestamos/mios | préstamos del usuario autenticado, filtro opcional `estado` |
| GET | /api/autores | catálogo de autores (filtro de libros y formulario) |
| POST | /api/asistente | asistente local: consultas del catálogo, disponibilidad y préstamos del usuario |

`GET /api/libros` acepta combinado `autor_id`, `anio`, `disponibles` y `orden`, y devuelve los `anios` disponibles en `meta`.

## Entregables (docs/)

Todos los entregables que no son codigo del proyecto estan en la carpeta [docs/](docs/):

- docs/01_sql_y_diagramas/   -> Parte 1: SQL + diagrama ER (draw.io, mermaid, HTML, PNG)
- docs/02..06_*/             -> Partes 2-6: README por parte con las rutas exactas del codigo en el proyecto
- docs/07_postman/           -> Parte 7: coleccion Postman + environment (importar en la app)
- docs/08_teoria/            -> Respuestas teoricas en Word (.docx) y Markdown (.md)
- docs/Informe_Biblioteca.pdf -> Documento global con la explicacion de cada punto


## Interfaz (mejoras)

- Pestaña «Mis préstamos»: tabla con estados y devolución en línea (con confirmación).
- Modal con el detalle de cada libro.
- Filtros de libros combinables: autor, año, disponibilidad y orden.
- Modo oscuro persistido en `localStorage`.
- Dashboard con dos gráficas (préstamos por mes y top libros).
- CSV del historial descargable desde «Mis préstamos».

## Decisiones

- Las reglas de negocio (stock, tope de 3 préstamos activos, usuarios inactivos)
  están en `PrestamoService` y no en el controller. Para un CRUD pequeño es un
  archivo extra, pero así las reutilizo sin duplicarlas desde el comando que
  marca vencidos y desde el asistente.
- El asistente funciona con reglas PHP (se interpreta el mensaje con regex) y no
  con un LLM. Me alcanza para lo que se consulta en el chat (copias de un
  título, catálogo, préstamos propios), pero es rígido: si la pregunta usa un
  sinónimo que no contemplé, cae al "no entendí". Está anotado en un TODO.
- PostgreSQL corre como servidor portable que arranca `iniciar.ps1`, en vez de
  exigir un servicio instalado. Es cómodo en una máquina limpia, pero los
  puertos son dinámicos: el script verifica que el puerto esté escuchando antes
  de escribir `.env` (un port file viejo me mordió una vez).
- Los gráficos del dashboard cargan Chart.js por CDN. Sin internet el dashboard
  muestra solo las tarjetas y un aviso; preferí no incluir el bundle a mano.
- Responder con Postman en vez de Swagger: la colección cubre los casos del
  proyecto y no agrego dependencias nuevas.
- Algunas líneas viejas del README tenían acentos dañados (edición por consola
  con UTF-8 leído como latin-1). Ya está limpio, pero si ves algo raro, la
  fuente de verdad es el código.


## Capturas (validación visual)

La carpeta [docs/capturas/](docs/capturas/) contiene las pantallas de la app para validar cada funcionalidad:

| Archivo | Qué valida |
|---|---|
| ![Login](docs/capturas/01_login.png) `01_login.png` | Login con credenciales demo (Sanctum) |
| ![Libros](docs/capturas/02_libros.png) `02_libros.png` | Listado de libros: autores en orden de pivote, stock, avisos "sin stock" |
| ![Búsqueda](docs/capturas/03_busqueda.png) `03_busqueda.png` | Búsqueda por título (filtro del API) |
| ![Préstamo](docs/capturas/04_nuevo_prestamo.png) `04_nuevo_prestamo.png` | Formulario de préstamo: solo libros con stock, fecha estimada hoy +15 días |
| ![Regla de negocio](docs/capturas/05_error_regla_negocio.png) `05_error_regla_negocio.png` | Regla de negocio desde el backend: toast HTTP 409 "el usuario ya tiene 3 activos" |
| ![Dashboard](docs/capturas/06_dashboard.png) `06_dashboard.png` | Dashboard con estadísticas de `/api/estadisticas` |
| ![Mis préstamos](docs/capturas/07_mis_prestamos.png) `07_mis_prestamos.png` | Pestaña «Mis préstamos»: préstamos del usuario autenticado (`GET /prestamos/mios`) con badges de estado |
| ![Devolución](docs/capturas/08_devolucion.png) `08_devolucion.png` | Botón «Devolver» con confirmación: toast de éxito y stock repuesto |
| ![Detalle del libro](docs/capturas/09_detalle_libro.png) `09_detalle_libro.png` | Modal de detalle del libro: autores, ISBN, año, stock y estado |
| ![Filtros](docs/capturas/10_filtros.png) `10_filtros.png` | Filtros combinables de libros: autor + año + disponibilidad + ordenación |
| ![Asistente](docs/capturas/11_chatbot.png) `11_chatbot.png` | Asistente integrado (chatbot local sobre la BD): disponibilidad, «mis préstamos», «catálogo» |
| ![Modo oscuro](docs/capturas/12_modo_oscuro.png) `12_modo_oscuro.png` | Tema oscuro con persistencia en `localStorage` |
| ![Dashboard con gráficos](docs/capturas/13_dashboard_graficos.png) `13_dashboard_graficos.png` | Dashboard con gráficos: préstamos por mes y top libros más prestados (Chart.js) |
_dashboard_graficos.png\ | Dashboard con gráficos: préstamos por mes y top libros más prestados, gracias a Chart.js |

El diagrama entidad-relación está en [docs/01_sql_y_diagramas/diagrama_er.png](docs/01_sql_y_diagramas/diagrama_er.png).

## Autor

Santiago Marulanda Leguizamo
Desarrollador de Software | Datos e IA aplicada

- GitHub: https://github.com/Marusan94
- Email: santiago.marulandal@udea.edu.co
- Portfolio: https://capidevo.vercel.app/