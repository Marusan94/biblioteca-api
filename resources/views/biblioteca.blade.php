<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Biblioteca</title>
    <link rel="stylesheet" href="/css/app.css">
    <script>/* aplica el tema guardado ANTES de pintar para evitar flash */
        document.documentElement.dataset.theme = localStorage.getItem('tema') || 'claro';</script>
</head>
<body>
<header>
    <h1>📚 Biblioteca</h1>
    <span id="quien" class="quien"></span>
    <button id="btn-tema" class="mini" title="Cambiar tema">🌙</button>
</header>

<!-- Login -->
<section id="login-box" class="card login hidden">
    <h2>Iniciar sesión</h2>
    <form id="login-form">
        <input type="email" name="email" placeholder="Email" value="usuario1@example.com" required>
        <input type="password" name="password" placeholder="Contraseña" value="password" required>
        <button type="submit">Entrar</button>
    </form>
    <p class="muted">Demo: usuario1@example.com / password</p>
</section>

<!-- App -->
<main id="app" class="hidden">
    <nav>
        <button class="tab active" data-tab="libros">Libros</button>
        <button class="tab" data-tab="mis">Mis préstamos</button>
        <button class="tab" data-tab="prestamos">Nuevo préstamo</button>
        <button class="tab" data-tab="dashboard">Dashboard</button>
    </nav>

    <!-- Libros con búsqueda en tiempo real + filtros combinables (6.1) -->
    <section id="tab-libros" class="tab-panel">
        <input id="buscar" type="search" placeholder="Buscar libro por título..." class="search">
        <div class="filtros">
            <label>Autor
                <select id="f-autor"><option value="">Todos</option></select>
            </label>
            <label>Año
                <select id="f-anio"><option value="">Todos</option></select>
            </label>
            <label class="check">
                <input type="checkbox" id="f-dispo"> Solo disponibles
            </label>
            <label>Orden
                <select id="f-orden">
                    <option value="titulo_asc">Título A→Z</option>
                    <option value="titulo_desc">Título Z→A</option>
                    <option value="anio_asc">Año ↑</option>
                    <option value="anio_desc">Año ↓</option>
                    <option value="stock_desc">Más stock</option>
                </select>
            </label>
        </div>
        <table id="tabla-libros">
            <thead>
                <tr><th>Título</th><th>Autores</th><th>Año</th><th>ISBN</th><th>Stock</th><th></th></tr>
            </thead>
            <tbody></tbody>
        </table>
        <p class="muted" id="sin-resultados" hidden>Sin resultados.</p>
    </section>

    <!-- Mis préstamos (del usuario autenticado) -->
    <section id="tab-mis" class="tab-panel hidden">
        <div class="filtros">
            <label>Estado
                <select id="f-estado-mis">
                    <option value="">Todos</option>
                    <option value="activo">Activos</option>
                    <option value="vencido">Vencidos</option>
                    <option value="devuelto">Devueltos</option>
                </select>
            </label>
        </div>
        <table id="tabla-mis">
            <thead>
                <tr><th>Libro</th><th>Estado</th><th>Prestado</th><th>Devolver antes</th><th></th></tr>
            </thead>
            <tbody></tbody>
        </table>
        <p class="muted" id="sin-mis" hidden>No tienes préstamos en este estado.</p>
    </section>

    <!-- Formulario para crear préstamo (6.1) -->
    <section id="tab-prestamos" class="tab-panel hidden">
        <form id="form-prestamo" class="card form">
            <h2>Nuevo préstamo</h2>
            <label>Usuario
                <select id="sel-usuario" name="usuario_id" required></select>
            </label>
            <label>Libro (solo con stock)
                <select id="sel-libro" name="libro_id" required></select>
            </label>
            <label>Fecha devolución estimada
                <input type="date" id="fecha_estimada" name="fecha_devolucion_estimada" required>
            </label>
            <button type="submit" class="primary">Registrar préstamo</button>
            <p id="msg-prestamo" class="hidden"></p>
        </form>
    </section>

    <!-- Dashboard con estadísticas (6.1) -->
    <section id="tab-dashboard" class="tab-panel hidden">
        <div id="stats" class="stats"></div>
    </section>
</main>

<!-- Modal detalle de libro -->
<div id="modal" class="modal-back hidden">
    <div class="modal card">
        <div class="modal-head">
            <h2 id="modal-titulo">Detalle del libro</h2>
            <button id="cerrar-modal" class="mini">✕ Cerrar</button>
        </div>
        <div id="modal-libro"></div>
    </div>
</div>

<!-- Asistente (chatbot local vía /api/asistente) -->
<button id="btn-chat" class="chat-flotante" title="Asistente de la biblioteca">🤖</button>
<section id="chat" class="chat-panel hidden">
    <header>
        <b>🤖 Asistente Biblioteca</b>
        <button id="cerrar-chat" class="mini">✕</button>
    </header>
    <div id="chat-log"></div>
    <form id="chat-form">
        <input id="chat-input" autocomplete="off" placeholder="¿Cuántas copias hay de El río oscuro?">
        <button type="submit" class="primary">➤</button>
    </form>
</section>

<div id="toast" class="hidden"></div>
<script src="/js/app.js?v=4"></script>
</body>
</html>
