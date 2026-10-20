-- ============================================================================
-- SISTEMA DE GESTIÓN DE BIBLIOTECA — Parte 1
-- Archivo : 02_logica_negocio.sql  (triggers, funciones y vistas)
-- Requiere: 01_esquema.sql ejecutado previamente
--
-- Reglas (alineadas con la Parte 5 de la prueba):
--   R1  Sólo usuarios con estado 'activo' pueden tomar préstamos.
--   R2  Máximo 3 préstamos activos por usuario.
--   R3  No se presta un libro sin stock; el stock se descuenta al
--       registrar el préstamo y se repone al devolverlo.
--   R4  updated_at se mantiene automático en todas las tablas.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- updated_at automático
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION f_touch_updated_at()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    NEW.updated_at := CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_autores_updated_at     BEFORE UPDATE ON autores     FOR EACH ROW EXECUTE FUNCTION f_touch_updated_at();
CREATE TRIGGER trg_libros_updated_at      BEFORE UPDATE ON libros      FOR EACH ROW EXECUTE FUNCTION f_touch_updated_at();
CREATE TRIGGER trg_autor_libro_updated_at BEFORE UPDATE ON autor_libro FOR EACH ROW EXECUTE FUNCTION f_touch_updated_at();
CREATE TRIGGER trg_usuarios_updated_at    BEFORE UPDATE ON usuarios    FOR EACH ROW EXECUTE FUNCTION f_touch_updated_at();
CREATE TRIGGER trg_prestamos_updated_at   BEFORE UPDATE ON prestamos   FOR EACH ROW EXECUTE FUNCTION f_touch_updated_at();

-- ---------------------------------------------------------------------------
-- R1 + R2 + R3: validación al REGISTRAR un préstamo
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION f_prestamo_before_insert()
RETURNS trigger
LANGUAGE plpgsql AS $$
DECLARE
    v_activos INTEGER;
BEGIN
    -- R1: usuario debe existir y estar activo
    IF NOT EXISTS (
        SELECT 1 FROM usuarios u
        WHERE u.id = NEW.usuario_id AND u.estado = 'activo'
    ) THEN
        RAISE EXCEPTION 'El usuario % no existe o no está activo', NEW.usuario_id;
    END IF;

    -- R2: máximo 3 préstamos activos por usuario
    SELECT count(*) INTO v_activos
    FROM prestamos p
    WHERE p.usuario_id = NEW.usuario_id
      AND p.estado <> 'devuelto';

    IF v_activos >= 3 THEN
        RAISE EXCEPTION 'El usuario % ya alcanzó el límite de 3 préstamos activos',
            NEW.usuario_id;
    END IF;

    -- R3: descontar stock si el ejemplar sale de la biblioteca
    -- (insertar directamente 'devuelto' no toca el stock)
    IF NEW.estado <> 'devuelto' THEN
        UPDATE libros
        SET stock_disponible = stock_disponible - 1
        WHERE id = NEW.libro_id
          AND stock_disponible > 0;

        IF NOT FOUND THEN
            RAISE EXCEPTION 'No hay stock disponible para el libro %', NEW.libro_id;
        END IF;
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_prestamos_before_insert
    BEFORE INSERT ON prestamos
    FOR EACH ROW EXECUTE FUNCTION f_prestamo_before_insert();

-- ---------------------------------------------------------------------------
-- R3: reposición/descuento simétrico del stock al CAMBIAR de estado
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION f_prestamo_after_update()
RETURNS trigger
LANGUAGE plpgsql AS $$
DECLARE
    v_viejo_espera BOOLEAN;   -- ejemplar fuera de la biblioteca
    v_nuevo_espera BOOLEAN;
BEGIN
    v_viejo_espera := (OLD.estado <> 'devuelto');
    v_nuevo_espera := (NEW.estado <> 'devuelto');

    IF v_viejo_espera = v_nuevo_espera THEN
        RETURN NEW;                        -- sin cambio relevante para el stock
    END IF;

    IF v_nuevo_espera THEN
        -- Re-apertura de un préstamo devuelto: volver a descontar
        UPDATE libros
        SET stock_disponible = stock_disponible - 1
        WHERE id = NEW.libro_id AND stock_disponible > 0;

        IF NOT FOUND THEN
            RAISE EXCEPTION 'Stock insuficiente para reactivar el préstamo %', NEW.id;
        END IF;
    ELSE
        -- Devolución: reponer un ejemplar
        UPDATE libros SET stock_disponible = stock_disponible + 1
        WHERE id = NEW.libro_id;
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_prestamos_after_update
    AFTER UPDATE ON prestamos
    FOR EACH ROW EXECUTE FUNCTION f_prestamo_after_update();

-- ---------------------------------------------------------------------------
-- R3: si se elimina un préstamo vigente, reponer el stock
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION f_prestamo_before_delete()
RETURNS trigger
LANGUAGE plpgsql AS $$
BEGIN
    IF OLD.estado <> 'devuelto' THEN
        UPDATE libros SET stock_disponible = stock_disponible + 1
        WHERE id = OLD.libro_id;
    END IF;
    RETURN OLD;
END;
$$;

CREATE TRIGGER trg_prestamos_before_delete
    BEFORE DELETE ON prestamos
    FOR EACH ROW EXECUTE FUNCTION f_prestamo_before_delete();

-- ---------------------------------------------------------------------------
-- Vistas de apoyo (consulta)
-- ---------------------------------------------------------------------------
CREATE OR REPLACE VIEW vw_prestamos_activos AS
SELECT p.id,
       p.usuario_id,
       u.nombre   AS usuario,
       u.email    AS email_usuario,
       p.libro_id,
       l.titulo   AS libro,
       p.fecha_prestamo,
       p.fecha_devolucion_estimada,
       GREATEST(CURRENT_DATE - p.fecha_devolucion_estimada, 0) AS dias_retraso
FROM prestamos p
JOIN usuarios u ON u.id = p.usuario_id
JOIN libros   l ON l.id = p.libro_id
WHERE p.estado <> 'devuelto'
  AND p.fecha_devolucion_real IS NULL;

-- Préstamos vencidos (más de 15 días de retraso — Parte 5.1)
CREATE OR REPLACE VIEW vw_prestamos_vencidos AS
SELECT p.id,
       p.usuario_id,
       u.nombre AS usuario,
       p.libro_id,
       l.titulo AS libro,
       p.fecha_devolucion_estimada,
       CURRENT_DATE - p.fecha_devolucion_estimada AS dias_retraso
FROM prestamos p
JOIN usuarios u ON u.id = p.usuario_id
JOIN libros   l ON l.id = p.libro_id
WHERE p.fecha_devolucion_estimada < CURRENT_DATE - INTERVAL '15 days'
  AND p.estado <> 'devuelto'
  AND p.fecha_devolucion_real IS NULL;
