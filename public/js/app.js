/* Parte 6: frontend vanilla JS que consume la API con fetch + Sanctum (6.2) */
(function () {
    'use strict';

    // API en la MISMA origen que la página (nginx, artisan serve, o cualquier puerto)
    const API = window.location.origin + '/api';
    let token = localStorage.getItem('token') || '';
    let cacheLibros = [];   // para el modal de detalle (3)
    let usuarioId = null;   // usuario autenticado (para "mis préstamos")

    const $ = (sel) => document.querySelector(sel);

    /* ============ Tema claro/oscuro (4) ============ */
    function aplicarTema(tema) {
        document.documentElement.dataset.theme = tema;
        localStorage.setItem('tema', tema);
        $('#btn-tema').textContent = tema === 'dark' ? '☀️' : '🌙';
    }
    $('#btn-tema').addEventListener('click', () => {
        aplicarTema(document.documentElement.dataset.theme === 'dark' ? 'claro' : 'dark');
    });
    aplicarTema(document.documentElement.dataset.theme || 'claro');

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
        const usuarioIdSel = $('#sel-usuario').value;
        const libroId = $('#sel-libro').value;
        const fecha = $('#fecha_estimada').value;

        if (!usuarioIdSel) return 'Selecciona un usuario.';
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
        usuarioId = res.user && res.user.id;
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
    $('#btn-salir').addEventListener('click', logout);

    // El «Salir» y el asistente solo tienen sentido con sesión activa:
    // los ocultamos si la pestaña principal (#app) está oculta (sin login).
    function refrescaSalir() {
        const sinSesion = $('#app').classList.contains('hidden');
        $('#btn-salir').classList.toggle('hidden', sinSesion);
        $('#btn-chat').classList.toggle('hidden', sinSesion);
        if (sinSesion) $('#chat').classList.add('hidden');
    }
    refrescaSalir();

    /* ============ Libros: búsqueda + filtros combinables (5) ============ */
    let timerBusqueda = null;

    function filaSkeleton(tbody, columnas) {
        tbody.innerHTML = '<tr class="skeleton"><td colspan="' + columnas + '">⏳ Cargando…</td></tr>';
    }

    async function cargarLibros() {
        const p = new URLSearchParams();
        if ($('#buscar').value.trim()) p.set('titulo', $('#buscar').value.trim());
        if ($('#f-autor').value) p.set('autor_id', $('#f-autor').value);
        if ($('#f-anio').value) p.set('anio', $('#f-anio').value);
        if ($('#f-dispo').checked) p.set('disponibles', '1');
        p.set('orden', $('#f-orden').value || 'titulo_asc');

        const cuerpo = $('#tabla-libros').querySelector('tbody');
        filaSkeleton(cuerpo, 6);

        const res = await call('/libros?' + p.toString());
        cacheLibros = res.data;

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
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    abrirTab('prestamos');
                    $('#sel-libro').value = libro.id;
                });
                const td = document.createElement('td');
                td.appendChild(btn);
                fila.appendChild(td);
            } else {
                fila.insertAdjacentHTML('beforeend', '<td class="muted">sin stock</td>');
            }

            fila.addEventListener('click', () => mostrarDetalle(libro));
            cuerpo.appendChild(fila);
        });

        // años disponibles para el filtro (solo la primera carga llena el select)
        const selAnio = $('#f-anio');
        if (selAnio.options.length <= 1 && res.meta && res.meta.anios) {
            res.meta.anios.forEach((a) => {
                selAnio.insertAdjacentHTML('beforeend', '<option value="' + a + '">' + a + '</option>');
            });
        }
    }

    ['#buscar', '#f-autor', '#f-anio', '#f-dispo', '#f-orden'].forEach((sel) => {
        $(sel).addEventListener($('#buscar') === $(sel) ? 'input' : 'change', () => {
            clearTimeout(timerBusqueda);
            timerBusqueda = setTimeout(cargarLibros, $('#buscar') === $(sel) ? 300 : 0);
        });
    });

    /* ============ Modal detalle de libro (3) ============ */
    async function mostrarDetalle(libro) {
        try {
            const res = await call('/libros/' + libro.id);
            const l = res.data;
            const autores = (l.autores || [])
                .map((a, i) => '<div class="detalle-fila"><b>Autor ' + (i + 1) + '</b>' + a.nombre + ' ' + a.apellido + '</div>')
                .join('');

            $('#modal-titulo').textContent = '📗 ' + l.titulo;
            $('#modal-libro').innerHTML =
                '<div class="detalle-fila"><b>Autores (en orden de firma)</b>' + (autores || '—') + '</div>' +
                '<div class="detalle-fila"><b>ISBN</b>' + (l.isbn || '—') + '</div>' +
                '<div class="detalle-fila"><b>Año de publicación</b>' + l.anio_publicacion + '</div>' +
                '<div class="detalle-fila"><b>Stock disponible</b>' + l.stock_disponible + ' copia(s)</div>' +
                '<div class="detalle-fila"><b>Estado</b>' + (l.stock_disponible > 0 ? '✅ Disponible para préstamo' : '⛔ Sin stock') + '</div>';
            $('#modal').classList.remove('hidden');
        } catch (err) {
            toast('⚠️ ' + err.message, false);
        }
    }

    $('#cerrar-modal').addEventListener('click', () => $('#modal').classList.add('hidden'));
    $('#modal').addEventListener('click', (e) => {
        if (e.target === e.currentTarget) $('#modal').classList.add('hidden');
    });

    /* ============ Mis préstamos (2) ============ */
    const ETIQUETAS = { activo: '🟢 activo', vencido: '🔴 vencido', devuelto: '✔ devuelto' };

    async function cargarMisPrestamos() {
        const estado = $('#f-estado-mis').value;
        const p = new URLSearchParams();
        if (estado) p.set('estado', estado);

        const cuerpo = $('#tabla-mis').querySelector('tbody');
        filaSkeleton(cuerpo, 5);

        const res = await call('/mis-prestamos?' + p.toString());
        cuerpo.innerHTML = '';
        $('#sin-mis').hidden = res.data.length > 0;

        res.data.forEach((prestamo) => {
            const fila = document.createElement('tr');
            fila.innerHTML =
                '<td>' + (prestamo.libro ? prestamo.libro.titulo : '(libro eliminado)') + '</td>' +
                '<td><span class="estado ' + prestamo.estado + '">' + (ETIQUETAS[prestamo.estado] || prestamo.estado) + '</span></td>' +
                '<td>' + prestamo.fecha_prestamo + '</td>' +
                '<td>' + prestamo.fecha_devolucion_estimada + '</td>';

            if (prestamo.estado === 'activo' || prestamo.estado === 'vencido') {
                const btn = document.createElement('button');
                btn.textContent = 'Devolver';
                btn.className = 'mini';
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const titulo = prestamo.libro ? prestamo.libro.titulo : 'este libro';
                    if (!confirm('¿Marcar «' + titulo + '» como devuelto?')) return;

                    btn.disabled = true;
                    try {
                        await call('/prestamos/' + prestamo.id + '/devolver', { method: 'PUT' });
                        toast('✅ Devolución registrada; stock repuesto.');
                        cargarMisPrestamos();
                        cargarDashboard();
                        refrescarSelects();
                    } catch (err) {
                        toast('⚠️ ' + err.message, false);
                        btn.disabled = false;
                    }
                });
                const td = document.createElement('td');
                td.appendChild(btn);
                fila.appendChild(td);
            } else {
                fila.insertAdjacentHTML('beforeend', '<td class="muted">—</td>');
            }

            cuerpo.appendChild(fila);
        });
    }

    $('#f-estado-mis').addEventListener('change', cargarMisPrestamos);

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

    /* ============ Dashboard con estadísticas + gráficos ============ */
    let chartMeses = null;
    let chartTop = null;

    function dibujarGraficos(g) {
        if (!window.Chart) {
            $('#sin-graficos').hidden = false;
            return;
        }
        $('#sin-graficos').hidden = true;

        const estilos = getComputedStyle(document.documentElement);
        const verde = estilos.getPropertyValue('--accent').trim() || '#2fa37c';
        const azul = '#7c8ea8';

        if (chartMeses) chartMeses.destroy();
        chartMeses = new Chart($('#g-meses'), {
            type: 'bar',
            data: {
                labels: g.prestamos_por_mes.labels,
                datasets: [{ label: 'Préstamos', data: g.prestamos_por_mes.datos, backgroundColor: verde }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { ticks: { precision: 0 } } }
            }
        });

        if (chartTop) chartTop.destroy();
        chartTop = new Chart($('#g-top'), {
            type: 'bar',
            data: {
                labels: g.top_libros.labels,
                datasets: [{ label: 'Veces prestado', data: g.top_libros.datos, backgroundColor: azul }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { y: { ticks: { precision: 0 } } }
            }
        });
    }

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

        if (d.graficos) dibujarGraficos(d.graficos);
    }

    /* ============ Exportar CSV (mejoras) ============ */
    async function descargarCsv() {
        try {
            const r = await fetch(API + '/mis-prestamos/exportar', {
                headers: { Accept: 'text/csv', Authorization: 'Bearer ' + token }
            });
            if (!r.ok) throw new Error('descarga');
            const blob = await r.blob();
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'prestamos_' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(a.href);
            toast('✅ CSV descargado');
        } catch (e) {
            toast('❌ No se pudo descargar el CSV');
        }
    }
    $('#btn-csv').addEventListener('click', descargarCsv);

    /* ============ Asistente / chatbot local ============ */
    function burbuja(texto, quien) {
        const div = document.createElement('div');
        div.className = 'burbuja ' + quien;
        div.textContent = texto;
        $('#chat-log').appendChild(div);
        $('#chat-log').scrollTop = $('#chat-log').scrollHeight;
    }

    $('#btn-chat').addEventListener('click', () => {
        $('#chat').classList.remove('hidden');
        $('#chat-input').focus();
    });
    $('#cerrar-chat').addEventListener('click', () => $('#chat').classList.add('hidden'));

    $('#chat-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const mensaje = $('#chat-input').value.trim();
        if (!mensaje) return;

        burbuja(mensaje, 'user');
        $('#chat-input').value = '';
        burbuja('…', 'bot');

        try {
            const res = await call('/asistente', { method: 'POST', body: { mensaje } });
            // sustituye el "…" por la respuesta real
            const divs = $('#chat-log').querySelectorAll('.burbuja.bot');
            divs[divs.length - 1].textContent = res.data.respuesta;
            $('#chat-log').scrollTop = $('#chat-log').scrollHeight;
        } catch (err) {
            const divs = $('#chat-log').querySelectorAll('.burbuja.bot');
            divs[divs.length - 1].textContent = '⚠️ ' + err.message;
        }
    });

    /* ============ Tabs ============ */
    function abrirTab(nombre) {
        document.querySelectorAll('.tab').forEach((t) =>
            t.classList.toggle('active', t.dataset.tab === nombre));
        document.querySelectorAll('.tab-panel').forEach((p) =>
            p.classList.add('hidden'));
        $('#tab-' + nombre).classList.remove('hidden');
    }

    // Delegación de eventos: los clicks en .tab se manejan a nivel document,
    // así los botones funcionan aunque el contenedor se recargue/re-renderice
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest ? e.target.closest('.tab') : null;
        if (trigger) {
            abrirTab(trigger.dataset.tab);
            if (trigger.dataset.tab === 'mis') cargarMisPrestamos();
            if (trigger.dataset.tab === 'dashboard') cargarDashboard();
        }
    });

    /* ============ Arranque ============ */
    async function iniciar() {
        refrescaSalir();
        filaSkeleton($('#tabla-libros').querySelector('tbody'), 6);
        await cargarLibros();
        await cargarSelects();
        cargarFiltroAutores();
        await cargarDashboard();
        $('#fecha_estimada').min = new Date().toISOString().slice(0, 10);
        $('#fecha_estimada').value = new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10);

        // saludo inicial del asistente la primera vez
        if (!$('#chat-log').children.length) {
            burbuja('¡Hola! Soy el asistente de la biblioteca 🤖\nPregúntame por ejemplo: «¿cuántas copias quedan de El río oscuro?», «mis préstamos» o «catálogo».', 'bot');
        }
    }

    async function cargarFiltroAutores() {
        try {
            const res = await call('/autores');
            const sel = $('#f-autor');
            res.data.forEach((a) => {
                sel.insertAdjacentHTML(
                    'beforeend',
                    '<option value="' + a.id + '">' + a.nombre + ' ' + a.apellido + '</option>'
                );
            });
        } catch (e) { /* si falla, el filtro de autor simplemente queda vacío */ }
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
            refrescaSalir();
        });
    } else {
        $('#login-box').classList.remove('hidden');
    }
})();
