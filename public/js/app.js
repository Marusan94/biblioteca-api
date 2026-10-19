/* Parte 6: frontend vanilla JS que consume la API con fetch + Sanctum (6.2) */
(function () {
    'use strict';

    // API en la MISMA origen que la página (nginx, artisan serve, o cualquier puerto)
    const API = window.location.origin + '/api';
    let token = localStorage.getItem('token') || '';

    const $ = (sel) => document.querySelector(sel);

    /* ============ Cliente AJAX con manejo de errores (6.2) ============ */
    async function call(path, options = {}) {
        options.headers = Object.assign({
            Accept: 'application/json',
            'Content-Type': 'application/json',
        }, options.headers || {});

        if (token) options.headers.Authorization = 'Bearer ' + token;
        if (options.body && typeof options.body !== 'string') {
            options.body = JSON.stringify(options.body);
        }

        const res = await fetch(API + path, options);
        let data = null;

        try { data = await res.json(); } catch (e) { /* cuerpo vacío */ }

        if (!res.ok) {
            const mensaje = data && data.message
                ? data.message
                : 'Error HTTP ' + res.status;
            throw new Error(mensaje);
        }

        return data;
    }

    function toast(mensaje, ok = true) {
        const t = $('#toast');
        t.textContent = mensaje;
        t.className = ok ? 'ok' : 'error';
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 4000);
    }

    /* ============ Validación del lado cliente (6.2) ============ */
    function validarFormulario() {
        const usuarioId = $('#sel-usuario').value;
        const libroId = $('#sel-libro').value;
        const fecha = $('#fecha_estimada').value;

        if (!usuarioId) return 'Selecciona un usuario.';
        if (!libroId) return 'Selecciona un libro.';
        if (!fecha) return 'Indica la fecha estimada.';
        if (fecha < new Date().toISOString().slice(0, 10)) {
            return 'La fecha estimada no puede ser anterior a hoy.';
        }

        return null;
    }

    /* ============ Login ============ */
    async function login(email, password) {
        const res = await call('/login', { method: 'POST', body: { email, password } });
        token = res.token;
        localStorage.setItem('token', token);
        $('#login-box').classList.add('hidden');
        $('#app').classList.remove('hidden');
        $('#quien').textContent = '👤 ' + res.user.nombre;
        iniciar();
    }

    function logout() {
        localStorage.removeItem('token');
        location.reload();
    }

    /* ============ Libros con búsqueda en tiempo real ============ */
    let timerBusqueda = null;

    async function cargarLibros() {
        const titulo = $('#buscar').value.trim();
        const params = titulo ? '?titulo=' + encodeURIComponent(titulo) : '';
        const res = await call('/libros' + params);
        const cuerpo = $('#tabla-libros').querySelector('tbody'); // filas

        cuerpo.innerHTML = '';
        $('#sin-resultados').hidden = res.data.length > 0;

        res.data.forEach((libro) => {
            const fila = document.createElement('tr');
            const autores = libro.autores
                ? libro.autores.map((a) => a.nombre + ' ' + a.apellido).join(', ')
                : '';
            fila.innerHTML =
                '<td>' + libro.titulo + '</td>' +
                '<td>' + autores + '</td>' +
                '<td>' + libro.anio_publicacion + '</td>' +
                '<td>' + (libro.isbn || '-') + '</td>' +
                '<td>' + libro.stock_disponible + '</td>';

            if (libro.stock_disponible > 0) {
                const btn = document.createElement('button');
                btn.textContent = 'Prestar';
                btn.className = 'mini';
                btn.addEventListener('click', () => {
                    abrirTab('prestamos');
                    $('#sel-libro').value = libro.id;
                });
                const td = document.createElement('td');
                td.appendChild(btn);
                fila.appendChild(td);
            } else {
                fila.insertAdjacentHTML('beforeend', '<td class="muted">sin stock</td>');
            }

            cuerpo.appendChild(fila);
        });
    }

    $('#buscar').addEventListener('input', () => {
        clearTimeout(timerBusqueda);
        timerBusqueda = setTimeout(cargarLibros, 300); // búsqueda en tiempo real con debounce
    });

    /* ============ Formulario de préstamo ============ */
    async function cargarSelects() {
        const usuarios = await call('/usuarios');
        const selUsuario = $('#sel-usuario');

        usuarios.data.forEach((u) => {
            selUsuario.insertAdjacentHTML(
                'beforeend',
                '<option value="' + u.id + '">' + u.nombre + ' (' + u.email + ')</option>'
            );
        });

        const libros = await call('/libros?por_pagina=100&disponibles=1');
        const selLibro = $('#sel-libro');
        libros.data.forEach((l) => {
            selLibro.insertAdjacentHTML(
                'beforeend',
                '<option value="' + l.id + '">' + l.titulo + ' — stock ' + l.stock_disponible + '</option>'
            );
        });
    }

    $('#form-prestamo').addEventListener('submit', async (e) => {
        e.preventDefault();

        const error = validarFormulario();
        if (error) { toast(error, false); return; }

        try {
            await call('/prestamos', {
                method: 'POST',
                body: {
                    usuario_id: Number($('#sel-usuario').value),
                    libro_id: Number($('#sel-libro').value),
                    fecha_devolucion_estimada: $('#fecha_estimada').value,
                },
            });
            toast('✅ Préstamo registrado correctamente.');
            $('#form-prestamo').reset();
            refrescarSelects();
        } catch (err) {
            toast('⚠️ ' + err.message, false); // errores de negocio: sin stock, límite 3, etc.
        }
    });

    async function refrescarSelects() {
        const selUsuario = $('#sel-usuario');
        const selLibro = $('#sel-libro');
        selUsuario.innerHTML = '';
        selLibro.innerHTML = '';
        await cargarSelects();
    }

    /* ============ Dashboard con estadísticas ============ */
    async function cargarDashboard() {
        const res = await call('/estadisticas');
        const d = res.data;
        const tarjetas = [
            ['📚 Libros', d.total_libros],
            ['⛽ Sin stock', d.libros_sin_stock],
            ['👥 Usuarios', d.total_usuarios],
            ['🟢 Activos', d.prestamos_activos],
            ['🔴 Vencidos', d.prestamos_vencidos],
            ['✔ Devueltos', d.prestamos_devueltos],
        ];

        $('#stats').innerHTML = tarjetas
            .map(([n, v]) => '<div class="stat"><b>' + v + '</b><span>' + n + '</span></div>')
            .join('');
    }

    /* ============ Tabs ============ */
    function abrirTab(nombre) {
        document.querySelectorAll('.tab').forEach((t) =>
            t.classList.toggle('active', t.dataset.tab === nombre));
        document.querySelectorAll('.tab-panel').forEach((p) =>
            p.classList.add('hidden'));
        $('#tab-' + nombre).classList.remove('hidden');
    }

    // Delegación de eventos: los clicks en .tab se manejan a nivel document,
    // así los botones funcionan aunque se recargue/re-renderice el contenedor
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest ? e.target.closest('.tab') : null;
        if (trigger) abrirTab(trigger.dataset.tab);
    });

    /* ============ Arranque ============ */
    async function iniciar() {
        await cargarLibros();
        await cargarSelects();
        await cargarDashboard();
        $('#fecha_estimada').min = new Date().toISOString().slice(0, 10);
        $('#fecha_estimada').value = new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10);
    }

    $('#login-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const form = new FormData(e.target);
        login(form.get('email'), form.get('password'))
            .catch((err) => toast('⚠️ ' + err.message, false));
    });

    if (token) {
        // hay token guardado: pide los libros para saber si sigue vigente
        call('/libros').then(() => {
            $('#login-box').classList.add('hidden');
            $('#app').classList.remove('hidden');
            iniciar();
        }).catch(() => {
            localStorage.removeItem('token');
            token = '';
            $('#login-box').classList.remove('hidden');
        });
    } else {
        $('#login-box').classList.remove('hidden');
    }
})();
