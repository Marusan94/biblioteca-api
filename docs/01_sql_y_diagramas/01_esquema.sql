-- ============================================================================
-- SISTEMA DE GESTIÓN DE BIBLIOTECA — Parte 1: Esquema de base de datos
-- Archivo : 01_esquema.sql
-- Ejecutar: psql -d biblioteca -f 01_esquema.sql   (idempotente; recrea tablas)
--
-- Notas sobre el modelo de referencia:
--   * Columna unificada como `anio_publicacion` (el referencia decía
--     `año_publicacion` en la columna pero `anio_publicacion` en el CHECK,
--     lo cual falla; además evita problemas con Eloquent/Laravel).
--   * El CHECK usa límite constante: PostgreSQL no admite CURRENT_DATE
--     (ni EXTRACT sobre CURRENT_DATE) dentro de un CHECK por no ser inmutable.
-- ============================================================================

DROP TABLE IF EXISTS prestamos, autor_libro, libros, usuarios, autores CASCADE;
DROP VIEW IF EXISTS vw_prestamos_activos, vw_prestamos_vencidos CASCADE;
DROP TYPE IF EXISTS estado_usuario;
DROP TYPE IF EXISTS estado_prestamo;
DROP FUNCTION IF EXISTS f_touch_updated_at(), f_prestamo_before_insert(),
    f_prestamo_after_update(), f_prestamo_before_delete() CASCADE;

-- Tipos ENUM (según referencia)
CREATE TYPE estado_usuario  AS ENUM ('activo', 'inactivo');
CREATE TYPE estado_prestamo AS ENUM ('activo', 'devuelto', 'vencido');

-- ---------------------------------------------------------------------------
-- Tabla: Autores
-- ---------------------------------------------------------------------------
CREATE TABLE autores (
    id               BIGSERIAL PRIMARY KEY,
    nombre           VARCHAR(100) NOT NULL,
    apellido         VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE         NOT NULL,
    nacionalidad     VARCHAR(100) NOT NULL,
    biografia        TEXT,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------------
-- Tabla: Libros (con deleted_at para soft delete — Parte 4)
-- ---------------------------------------------------------------------------
CREATE TABLE libros (
    id               BIGSERIAL PRIMARY KEY,
    titulo           VARCHAR(255) NOT NULL,
    isbn             VARCHAR(13)  UNIQUE NOT NULL,
    anio_publicacion INT          NOT NULL CHECK (anio_publicacion BETWEEN 1450 AND 2100),
    numero_paginas   INT          NOT NULL CHECK (numero_paginas > 0),
    descripcion      TEXT,
    stock_disponible INT          NOT NULL DEFAULT 0 CHECK (stock_disponible >= 0),
    deleted_at       TIMESTAMPTZ, -- soft deletes (Parte 4)
    created_at       TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------------
-- Tabla pivote: autor_libro (N:M con orden_autor — Parte 3.3)
-- ---------------------------------------------------------------------------
CREATE TABLE autor_libro (
    id          BIGSERIAL PRIMARY KEY,
    autor_id    BIGINT NOT NULL,
    libro_id    BIGINT NOT NULL,
    orden_autor INT    NOT NULL DEFAULT 1 CHECK (orden_autor > 0),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_autor_libro_autor FOREIGN KEY (autor_id) REFERENCES autores(id) ON DELETE RESTRICT,
    CONSTRAINT fk_autor_libro_libro FOREIGN KEY (libro_id) REFERENCES libros(id)  ON DELETE CASCADE,
    CONSTRAINT uq_autor_libro UNIQUE (autor_id, libro_id)
);

-- ---------------------------------------------------------------------------
-- Tabla: Usuarios
-- ---------------------------------------------------------------------------
CREATE TABLE usuarios (
    id             BIGSERIAL PRIMARY KEY,
    nombre         VARCHAR(150) NOT NULL,
    email          VARCHAR(255) UNIQUE NOT NULL,
    telefono       VARCHAR(20),
    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado         estado_usuario NOT NULL DEFAULT 'activo',
    created_at     TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------------
-- Tabla: Prestamos
-- ---------------------------------------------------------------------------
CREATE TABLE prestamos (
    id                        BIGSERIAL PRIMARY KEY,
    libro_id                  BIGINT NOT NULL,
    usuario_id                BIGINT NOT NULL,
    fecha_prestamo            TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion_estimada DATE        NOT NULL,
    fecha_devolucion_real     TIMESTAMPTZ,
    estado                    estado_prestamo NOT NULL DEFAULT 'activo',
    created_at                TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_prestamos_libro   FOREIGN KEY (libro_id)   REFERENCES libros(id)   ON DELETE RESTRICT,
    CONSTRAINT fk_prestamos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    -- NOTA: el cast ::DATE de timestamptz no es inmutable; la comparación
    -- directa date vs timestamptz sí es válida dentro del CHECK.
    CONSTRAINT chk_fechas_prestamo CHECK (fecha_devolucion_estimada >= fecha_prestamo)
);

-- ---------------------------------------------------------------------------
-- Índices para optimización de consultas
-- ---------------------------------------------------------------------------
CREATE INDEX idx_libros_titulo      ON libros(titulo);
CREATE INDEX idx_libros_anio        ON libros(anio_publicacion);
CREATE INDEX idx_libros_stock       ON libros(stock_disponible);
CREATE INDEX idx_prestamos_libro    ON prestamos(libro_id);
CREATE INDEX idx_prestamos_usuario  ON prestamos(usuario_id);
CREATE INDEX idx_prestamos_estado   ON prestamos(estado);
CREATE INDEX idx_autor_libro_libro  ON autor_libro(libro_id);
CREATE INDEX idx_usuarios_estado    ON usuarios(estado);
