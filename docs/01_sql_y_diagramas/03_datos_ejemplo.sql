-- ============================================================================
-- SISTEMA DE GESTIÓN DE BIBLIOTECA — Parte 1
-- Archivo : 03_datos_ejemplo.sql  (datos FICTICIOS de demostración)
-- Requiere: 01_esquema.sql y 02_logica_negocio.sql
-- ============================================================================

BEGIN;

-- Autores -------------------------------------------------------------------
INSERT INTO autores (id, nombre, apellido, fecha_nacimiento, nacionalidad, biografia) VALUES
(1, 'Jorge Luis',  'Borges',         DATE '1899-08-24', 'argentina',      'Escritor, poeta y ensayista argentino, figura central del siglo XX.'),
(2, 'Gabriel',     'García Márquez', DATE '1927-03-06', 'colombiana',     'Periodista y novelista, premio Nobel de Literatura 1982.'),
(3, 'George',      'Orwell',         DATE '1903-06-25', 'británica',      'Ensayista y novelista, autor de distopías fundamentales.'),
(4, 'Ursula K.',   'Le Guin',        DATE '1929-10-21', 'estadounidense', 'Autora de fantasía y ciencia ficción.'),
(5, 'Italo',       'Calvino',        DATE '1923-10-15', 'italiana',       'Escritor conocido por su inventiva formal.'),
(6, 'Adolfo',      'Bioy Casares',   DATE '1914-09-13', 'argentina',      'Autor de La invención de Morel.'),
(7, 'Silvina',     'Ocampo',         DATE '1903-08-21', 'argentina',      'Poeta y cuentista argentina.');
SELECT setval(pg_get_serial_sequence('autores', 'id'), (SELECT max(id) FROM autores));

-- Libros --------------------------------------------------------------------
INSERT INTO libros (id, titulo, isbn, anio_publicacion, numero_paginas, descripcion, stock_disponible) VALUES
(1, 'Ficciones', '9788420633107', 1944, 470, 'Recuento de cuentos fantásticos de Borges.', 3),
(2, 'Cien años de soledad', '9788497592208', 1967, 494, 'La saga de los Buendía en Macondo.', 4),
(3, '1984', '9788499890953', 1949, 326, 'Distopía sobre el totalitarismo.', 4),
(4, 'Rebelión en la granja', '9788498386356', 1945, 152, 'Sátira política en una granja.', 2),
(5, 'Los desposeídos', '9788445003679', 1974, 341, 'Utopía ambigua sobre Anarres.', 2),
(6, 'Si una noche de invierno un viajero', '9788432248153', 1979, 274, 'Novela-mosaico sobre el placer de leer.', 2),
(7, 'Antología de la literatura fantástica', '9789505114849', 1940, 532, 'Compilada por Borges, Bioy y Ocampo.', 1);
SELECT setval(pg_get_serial_sequence('libros', 'id'), (SELECT max(id) FROM libros));

-- N:M con orden de autor ------------------------------------------------------
INSERT INTO autor_libro (autor_id, libro_id, orden_autor) VALUES
(1, 1, 1),  -- Borges -> Ficciones
(2, 2, 1),  -- García Márquez -> Cien años de soledad
(3, 3, 1),  -- Orwell -> 1984
(3, 4, 1),  -- Orwell -> Rebelión en la granja
(4, 5, 1),  -- Le Guin -> Los desposeídos
(5, 6, 1),  -- Calvino -> Si una noche de invierno...
(1, 7, 1), (6, 7, 2), (7, 7, 3);  -- Antología: 3 autores con orden

-- Usuarios --------------------------------------------------------------------
INSERT INTO usuarios (id, nombre, email, telefono, estado) VALUES
(1, 'Ana Torres',      'ana.torres@example.com',      '+57 300 123 4567', 'activo'),
(2, 'Mateo Ríos',      'mateo.rios@example.com',      '+57 310 555 2211', 'activo'),
(3, 'Lucía Fernández', 'lucia.fernandez@example.com', '+57 312 884 9900', 'activo'),
(4, 'Andrés Molina',   'andres.molina@example.com',   NULL,               'inactivo');
SELECT setval(pg_get_serial_sequence('usuarios', 'id'), (SELECT max(id) FROM usuarios));

-- Préstamos (los triggers ajustan el stock automáticamente) --------------------
INSERT INTO prestamos (libro_id, usuario_id, fecha_prestamo, fecha_devolucion_estimada, fecha_devolucion_real, estado) VALUES
(3, 1, CURRENT_TIMESTAMP - INTERVAL '2 days',  CURRENT_DATE + 12, NULL, 'activo'),
(3, 3, CURRENT_TIMESTAMP - INTERVAL '5 days',  CURRENT_DATE + 9,  NULL, 'activo'),
(5, 2, CURRENT_TIMESTAMP - INTERVAL '20 days', CURRENT_DATE - 6,  NULL, 'vencido'),
(4, 3, CURRENT_TIMESTAMP - INTERVAL '30 days', CURRENT_DATE - 16, CURRENT_TIMESTAMP - INTERVAL '17 days', 'devuelto');

COMMIT;

-- Verificación rápida ----------------------------------------------------------
SELECT titulo, stock_disponible FROM libros ORDER BY id;   -- l3=2, l5=1, l4=2
SELECT * FROM vw_prestamos_activos ORDER BY dias_retraso DESC;
SELECT * FROM vw_prestamos_vencidos;
