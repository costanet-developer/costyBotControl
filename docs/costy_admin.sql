-- ================================================================
-- EXTENSIÓN DE costy_sesiones PARA SISTEMA ADMINISTRATIVO (Laravel)
-- No modifica el comportamiento de n8n. Solo agrega columnas/tablas
-- nuevas que n8n nunca escribe.
-- Ejecutar conectado a la BD costy_sesiones, usuario con permisos DDL.
-- ================================================================

BEGIN;

-- ================================================================
-- 1. COLUMNAS DE AUDITORÍA SOBRE comprobantes (n8n no las toca)
-- ================================================================
ALTER TABLE comprobantes
    ADD COLUMN IF NOT EXISTS estado_auditoria   VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    ADD COLUMN IF NOT EXISTS revisado_por       BIGINT,          -- FK a users(id) de Laravel
    ADD COLUMN IF NOT EXISTS revisado_en        TIMESTAMP,
    ADD COLUMN IF NOT EXISTS aprobado_por       BIGINT,
    ADD COLUMN IF NOT EXISTS aprobado_en        TIMESTAMP,
    ADD COLUMN IF NOT EXISTS rechazado_por      BIGINT,
    ADD COLUMN IF NOT EXISTS rechazado_en       TIMESTAMP,
    ADD COLUMN IF NOT EXISTS motivo_rechazo     TEXT,
    ADD COLUMN IF NOT EXISTS tiene_observaciones BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS deleted_at         TIMESTAMP,       -- SoftDeletes (Laravel)
    ADD COLUMN IF NOT EXISTS deleted_by         BIGINT,
    ADD COLUMN IF NOT EXISTS created_at         TIMESTAMP NOT NULL DEFAULT NOW(),
    ADD COLUMN IF NOT EXISTS updated_at         TIMESTAMP NOT NULL DEFAULT NOW(),
    ADD COLUMN IF NOT EXISTS updated_by         BIGINT;

COMMENT ON COLUMN comprobantes.estado_auditoria IS
    'PENDIENTE | EN_REVISION | APROBADO | RECHAZADO | DUPLICADO | CON_NOVEDAD | ESCALADO | ANULADO';

CREATE INDEX IF NOT EXISTS idx_comp_estado_auditoria ON comprobantes(estado_auditoria);

-- Regla: si numero_transaccion ya existía antes de este comprobante -> marcar DUPLICADO
-- (esto se resuelve mejor a nivel de aplicación en Laravel, pero se deja el índice único
-- ya presente en numero_transaccion como respaldo de integridad)

-- ================================================================
-- 2. USUARIOS ADMINISTRATIVOS (tabla propia de Laravel: `users`)
--    Se usa el nombre estándar de Laravel (`users`) en vez de `usuarios`
--    para que Auth, Breeze/Fortify y Spatie Permission funcionen sin
--    configuración adicional. Si prefieres `usuarios` en español, se
--    puede sobreescribir $table en el modelo, pero se recomienda
--    mantener el estándar para evitar fricción con paquetes.
-- ================================================================
CREATE TABLE IF NOT EXISTS users (
    id                  BIGSERIAL PRIMARY KEY,
    nombre              VARCHAR(100) NOT NULL,
    apellido             VARCHAR(100),
    email               VARCHAR(150) UNIQUE NOT NULL,
    email_verified_at   TIMESTAMP,
    password            VARCHAR(255) NOT NULL,
    activo              BOOLEAN NOT NULL DEFAULT TRUE,
    bloqueado           BOOLEAN NOT NULL DEFAULT FALSE,
    intentos_fallidos   INT NOT NULL DEFAULT 0,
    ultimo_acceso       TIMESTAMP,
    remember_token      VARCHAR(100),
    created_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    creado_por          BIGINT REFERENCES users(id),
    actualizado_por     BIGINT REFERENCES users(id),
    deleted_at          TIMESTAMP  -- SoftDeletes
);
CREATE INDEX IF NOT EXISTS idx_users_email  ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_activo ON users(activo);

-- Ahora sí se pueden agregar las FKs pendientes de comprobantes:
ALTER TABLE comprobantes
    ADD CONSTRAINT fk_comp_revisado_por  FOREIGN KEY (revisado_por)  REFERENCES users(id),
    ADD CONSTRAINT fk_comp_aprobado_por  FOREIGN KEY (aprobado_por)  REFERENCES users(id),
    ADD CONSTRAINT fk_comp_rechazado_por FOREIGN KEY (rechazado_por) REFERENCES users(id),
    ADD CONSTRAINT fk_comp_deleted_by    FOREIGN KEY (deleted_by)    REFERENCES users(id),
    ADD CONSTRAINT fk_comp_updated_by    FOREIGN KEY (updated_by)    REFERENCES users(id);

-- Tabla nativa de sesiones de Laravel (driver 'database'), reemplaza sesiones_usuario
CREATE TABLE IF NOT EXISTS sessions (
    id            VARCHAR(255) PRIMARY KEY,
    user_id       BIGINT REFERENCES users(id),
    ip_address    VARCHAR(45),
    user_agent    TEXT,
    payload       TEXT NOT NULL,
    last_activity INT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions(last_activity);

-- Password reset tokens (estándar Laravel)
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email       VARCHAR(150) PRIMARY KEY,
    token       VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP
);

-- ================================================================
-- 3. ROLES Y PERMISOS
--    NOTA: en Laravel real, estas tablas las genera el paquete
--    spatie/laravel-permission (`php artisan vendor:publish`). Se
--    incluyen aquí como referencia/documentación por si necesitas
--    crearlas manualmente o entender la estructura que va a aparecer.
--    Si usas el paquete, NO ejecutes este bloque: usa sus migraciones.
-- ================================================================
CREATE TABLE IF NOT EXISTS roles (
    id            BIGSERIAL PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    guard_name    VARCHAR(50) NOT NULL DEFAULT 'web',
    created_at    TIMESTAMP DEFAULT NOW(),
    updated_at    TIMESTAMP DEFAULT NOW(),
    UNIQUE(name, guard_name)
);

CREATE TABLE IF NOT EXISTS permissions (
    id            BIGSERIAL PRIMARY KEY,
    name          VARCHAR(150) NOT NULL,   -- ej. 'comprobantes.aprobar'
    guard_name    VARCHAR(50) NOT NULL DEFAULT 'web',
    modulo        VARCHAR(50),
    created_at    TIMESTAMP DEFAULT NOW(),
    updated_at    TIMESTAMP DEFAULT NOW(),
    UNIQUE(name, guard_name)
);

CREATE TABLE IF NOT EXISTS model_has_roles (
    role_id       BIGINT REFERENCES roles(id) ON DELETE CASCADE,
    model_type    VARCHAR(150) NOT NULL,
    model_id      BIGINT NOT NULL,
    PRIMARY KEY (role_id, model_id, model_type)
);

CREATE TABLE IF NOT EXISTS model_has_permissions (
    permission_id BIGINT REFERENCES permissions(id) ON DELETE CASCADE,
    model_type    VARCHAR(150) NOT NULL,
    model_id      BIGINT NOT NULL,
    PRIMARY KEY (permission_id, model_id, model_type)
);

CREATE TABLE IF NOT EXISTS role_has_permissions (
    permission_id BIGINT REFERENCES permissions(id) ON DELETE CASCADE,
    role_id       BIGINT REFERENCES roles(id) ON DELETE CASCADE,
    PRIMARY KEY (permission_id, role_id)
);

-- Datos iniciales de roles
INSERT INTO roles (name) VALUES
    ('superadministrador'), ('administrador'), ('contabilidad')
ON CONFLICT DO NOTHING;

-- Datos iniciales de permisos
INSERT INTO permissions (name, modulo) VALUES
    ('usuarios.ver','usuarios'), ('usuarios.crear','usuarios'), ('usuarios.editar','usuarios'), ('usuarios.eliminar','usuarios'),
    ('roles.ver','roles'), ('roles.administrar','roles'),
    ('interacciones.ver','interacciones'), ('interacciones.editar','interacciones'), ('interacciones.eliminar','interacciones'),
    ('comprobantes.ver','comprobantes'), ('comprobantes.descargar','comprobantes'),
    ('comprobantes.revisar','comprobantes'), ('comprobantes.aprobar','comprobantes'), ('comprobantes.rechazar','comprobantes'),
    ('observaciones.crear','observaciones'),
    ('reportes.ver','reportes'), ('reportes.exportar','reportes'),
    ('auditoria.ver','auditoria'),
    ('configuracion.ver','configuracion'), ('configuracion.editar','configuracion')
ON CONFLICT DO NOTHING;

-- Asignación inicial: superadministrador = todos los permisos
INSERT INTO role_has_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'superadministrador'
ON CONFLICT DO NOTHING;

-- administrador: todo menos usuarios.eliminar, roles.administrar, configuracion.editar
INSERT INTO role_has_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'administrador'
  AND p.name NOT IN ('usuarios.eliminar','roles.administrar','configuracion.editar')
ON CONFLICT DO NOTHING;

-- contabilidad: solo lo operativo de revisión
INSERT INTO role_has_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'contabilidad'
  AND p.name IN ('interacciones.ver','comprobantes.ver','comprobantes.revisar',
                 'comprobantes.aprobar','comprobantes.rechazar','observaciones.crear')
ON CONFLICT DO NOTHING;

-- ================================================================
-- 4. AUDITORÍA — tabla append-only
-- ================================================================
CREATE TABLE IF NOT EXISTS auditoria_logs (
    id                 BIGSERIAL PRIMARY KEY,
    usuario_id         BIGINT REFERENCES users(id),
    accion             VARCHAR(50) NOT NULL,   -- login, logout, update, delete, aprobar, rechazar, exportar...
    modulo             VARCHAR(50) NOT NULL,
    entidad            VARCHAR(60),
    entidad_id         BIGINT,
    datos_anteriores   JSONB,
    datos_nuevos       JSONB,
    direccion_ip       VARCHAR(45),
    user_agent         TEXT,
    resultado          VARCHAR(20) NOT NULL DEFAULT 'exitoso',
    descripcion        TEXT,
    fecha_hora         TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_audit_usuario ON auditoria_logs(usuario_id);
CREATE INDEX IF NOT EXISTS idx_audit_entidad ON auditoria_logs(entidad, entidad_id);
CREATE INDEX IF NOT EXISTS idx_audit_fecha   ON auditoria_logs(fecha_hora);

-- Sin trigger de bloqueo aquí a propósito: la protección "nadie puede
-- editar/borrar logs" se implementa a nivel de Policy en Laravel
-- (ningún controller expone update/destroy sobre AuditoriaLog).
-- Si quieres blindaje también a nivel de BD, descomenta:
--
-- CREATE OR REPLACE FUNCTION bloquear_edicion_auditoria() RETURNS TRIGGER AS $$
-- BEGIN
--     RAISE EXCEPTION 'Los registros de auditoria_logs no pueden modificarse ni eliminarse';
-- END;
-- $$ LANGUAGE plpgsql;
--
-- CREATE TRIGGER trg_bloquear_auditoria
-- BEFORE UPDATE OR DELETE ON auditoria_logs
-- FOR EACH ROW EXECUTE FUNCTION bloquear_edicion_auditoria();

-- ================================================================
-- 5. OBSERVACIONES sobre una interacción/comprobante
-- ================================================================
CREATE TABLE IF NOT EXISTS observaciones_interaccion (
    id              BIGSERIAL PRIMARY KEY,
    sesion_id       VARCHAR(80) REFERENCES sesiones(sesion_id),
    comprobante_id  BIGINT REFERENCES comprobantes(id),
    usuario_id      BIGINT NOT NULL REFERENCES users(id),
    observacion     TEXT NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_obs_sesion ON observaciones_interaccion(sesion_id);
CREATE INDEX IF NOT EXISTS idx_obs_comprobante ON observaciones_interaccion(comprobante_id);

-- ================================================================
-- 6. HISTORIAL DE REVISIONES (una fila por cada cambio de estado)
-- ================================================================
CREATE TABLE IF NOT EXISTS revisiones_comprobante (
    id               BIGSERIAL PRIMARY KEY,
    comprobante_id   BIGINT NOT NULL REFERENCES comprobantes(id),
    usuario_id       BIGINT NOT NULL REFERENCES users(id),
    estado_anterior  VARCHAR(20),
    estado_nuevo     VARCHAR(20) NOT NULL,
    observacion      TEXT,
    fecha_revision   TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_revision_comprobante ON revisiones_comprobante(comprobante_id);

-- ================================================================
-- 7. TRIGGER genérico updated_at (comprobantes, users, observaciones)
-- ================================================================
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_comprobantes_updated_at ON comprobantes;
CREATE TRIGGER trg_comprobantes_updated_at
BEFORE UPDATE ON comprobantes
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS trg_users_updated_at ON users;
CREATE TRIGGER trg_users_updated_at
BEFORE UPDATE ON users
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS trg_obs_updated_at ON observaciones_interaccion;
CREATE TRIGGER trg_obs_updated_at
BEFORE UPDATE ON observaciones_interaccion
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ================================================================
-- 8. CREACIÓN SEGURA DEL PRIMER SUPERADMINISTRADOR
--    NO se inserta una contraseña fija en este script. Usa un
--    comando artisan (seeder interactivo o `php artisan tinker`)
--    que pida el password por input y lo hashee con Hash::make():
--
--    php artisan make:command CrearSuperadmin
--    -> pide email + password por consola (Laravel Prompts),
--       crea el registro en users, y le asigna el rol
--       'superadministrador' vía Spatie ($user->assignRole(...)).
--
--    Alternativa rápida sin comando custom:
--    php artisan tinker
--    >>> $u = User::create(['nombre'=>'Luis','email'=>'tu@correo.com','password'=>Hash::make('escribe-aqui-un-password-fuerte')]);
--    >>> $u->assignRole('superadministrador');
-- ================================================================

COMMIT;
