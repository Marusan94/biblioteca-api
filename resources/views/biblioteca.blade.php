<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Biblioteca</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<header>
    <h1>📚 Biblioteca</h1>
    <span id="quien" class="quien"></span>
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
        <button class="tab" data-tab="prestamos">Nuevo préstamo</button>
        <button class="tab" data-tab="dashboard">Dashboard</button>
    </nav>

    <!-- Libros con búsqueda en tiempo real (6.1) -->
    <section id="tab-libros" class="tab-panel">
        <input id="buscar" type="search" placeholder="Buscar libro por título..." class="search">
        <table id="tabla-libros">
            <thead>
                <tr><th>Título</th><th>Autores</th><th>Año</th><th>ISBN</th><th>Stock</th><th></th></tr>
            </thead>
            <tbody></tbody>
        </table>
        <p class="muted" id="sin-resultados" hidden>Sin resultados.</p>
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

<div id="toast" class="hidden"></div>
<script src="/js/app.js"></script>
</body>
</html>
