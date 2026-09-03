CREATE USER costy_bot WITH PASSWORD 'Constanet2026';
CREATE DATABASE costy_sesiones OWNER costy_bot;
GRANT ALL PRIVILEGES ON DATABASE costy_sesiones TO costy_bot;
\q

-- ================================================================
-- 1. CLIENTES — identidad maestra, reutilizable por futuros bots
-- ================================================================
CREATE TABLE clientes (
    numero_whatsapp     VARCHAR(20) PRIMARY KEY,
    cedula               VARCHAR(15),
    nombre               VARCHAR(100),
    idcliente_costanet   INT,
    primera_interaccion  TIMESTAMP DEFAULT NOW(),
    ultima_interaccion   TIMESTAMP DEFAULT NOW()
);
CREATE INDEX idx_clientes_cedula ON clientes(cedula);

-- ================================================================
-- 2. SESIONES — una fila por intento de conversación
--    Reemplaza: staticData.conversaciones + historialConversaciones
-- ================================================================
CREATE TABLE sesiones (
    id                   SERIAL PRIMARY KEY,
    sesion_id            VARCHAR(80) UNIQUE NOT NULL,
    numero_whatsapp      VARCHAR(20) NOT NULL REFERENCES clientes(numero_whatsapp),
    bot                  VARCHAR(30) DEFAULT 'reactivacion',  -- 'reactivacion' | 'soporte_tecnico' | 'ventas'
    intencion            VARCHAR(30),                          -- 'reactivar' | 'consultar'
    cedula               VARCHAR(15),
    estado_sesion        VARCHAR(20) DEFAULT 'activa',         -- 'activa' | 'cerrada'
    resultado            VARCHAR(40),                          -- ver catálogo abajo
    intentos_comprobante INT DEFAULT 0,
    inicio               TIMESTAMP DEFAULT NOW(),
    fin                  TIMESTAMP,
    media_id             VARCHAR(100),
    cedula_media_id      VARCHAR(100),
    mensajes_procesados  JSONB DEFAULT '[]'
);
CREATE INDEX idx_sesiones_whatsapp ON sesiones(numero_whatsapp);
CREATE INDEX idx_sesiones_estado   ON sesiones(estado_sesion);
CREATE INDEX idx_sesiones_bot      ON sesiones(bot);

-- Catálogo de "resultado" (sin CHECK, para no romper al agregar bots nuevos):
--   reactivado | consulta_exitosa | sin_deuda | sin_servicio
--   cliente_no_encontrado | cedula_no_encontrada | abandonado_sin_comprobante
--   expirado | monto_no_coincide_final

-- ================================================================
-- 3. EVENTOS_INTERACCION — el embudo completo, un evento por paso
--    Reemplaza: TODOS los nodos Log_* → logs/interacciones.jsonl
--    Esta es la tabla clave para FCR y CES
-- ================================================================
CREATE TABLE eventos_interaccion (
    id                    SERIAL PRIMARY KEY,
    sesion_id             VARCHAR(80) REFERENCES sesiones(sesion_id),
    numero_whatsapp       VARCHAR(20) NOT NULL,
    fecha_evento          TIMESTAMP DEFAULT NOW(),
    paso                  VARCHAR(60) NOT NULL,
    estado_conversacion   VARCHAR(50),
    intentos_comprobante  INT,
    cedula                VARCHAR(15),
    tipo_comprobante      VARCHAR(50),
    duplicado             BOOLEAN,
    opcion_ocr            INT,
    monto_esperado        NUMERIC(10,2),
    deuda_total           NUMERIC(10,2),
    datos_adicionales     JSONB DEFAULT '{}'
);
CREATE INDEX idx_eventos_sesion ON eventos_interaccion(sesion_id);
CREATE INDEX idx_eventos_wa     ON eventos_interaccion(numero_whatsapp);
CREATE INDEX idx_eventos_paso   ON eventos_interaccion(paso);
CREATE INDEX idx_eventos_fecha  ON eventos_interaccion(fecha_evento);

-- Catálogo actual de "paso" (los 15 que ya generan tus nodos Log_*):
--   menu_principal_mostrado, cedula_valida, cedula_invalida,
--   menu_consultar_seleccionado, menu_reactivar_seleccionado,
--   cierre_sin_resolucion, reintento_comprobante, comprobante_recibido,
--   comprobante_no_duplicado, comprobante_duplicado, ocr_legible,
--   ocr_ilegible, monto_ok, monto_no_coincide, reactivacion_exitosa

-- ================================================================
-- 4. COMPROBANTES — pago vía OCR automático o texto manual, unificados
--    Reemplaza: registro_datos.csv + registro_datos_ingresados_cliente.csv
-- ================================================================
CREATE TABLE comprobantes (
    id                    SERIAL PRIMARY KEY,
    sesion_id             VARCHAR(80) REFERENCES sesiones(sesion_id),
    numero_whatsapp       VARCHAR(20) NOT NULL REFERENCES clientes(numero_whatsapp),
    origen                VARCHAR(20) NOT NULL DEFAULT 'ocr_automatico', -- 'ocr_automatico' | 'manual'
    fecha_hora            TIMESTAMP DEFAULT NOW(),
    nombre_archivo        VARCHAR(200),
    ruta_imagen           VARCHAR(300),
    media_id              VARCHAR(100),
    cedula                VARCHAR(15),
    banco                 VARCHAR(100),
    monto                 NUMERIC(10,2),
    fecha_comprobante     VARCHAR(20),
    numero_transaccion    VARCHAR(100) UNIQUE,   -- reemplaza indice_transacciones (anti-duplicado)
    titular                VARCHAR(150),
    cuenta_destino         VARCHAR(50),
    estado                 VARCHAR(30) DEFAULT 'recibida',
    -- Solo se llenan cuando origen = 'ocr_automatico':
    banco_valido           BOOLEAN,
    cuenta_valida          BOOLEAN,
    titular_valido         BOOLEAN,
    riesgo_visual          VARCHAR(20),
    alertas                JSONB DEFAULT '[]',
    probabilidad_ia_generativa INT,
    riesgo_ia_generativa   VARCHAR(20),
    alertas_ia_generativa  JSONB DEFAULT '[]',
    score_confianza        INT,
    accion_recomendada     VARCHAR(30)
);
CREATE INDEX idx_comp_wa      ON comprobantes(numero_whatsapp);
CREATE INDEX idx_comp_cedula  ON comprobantes(cedula);
CREATE INDEX idx_comp_sesion  ON comprobantes(sesion_id);

-- ================================================================
-- 5. DOCUMENTOS_IDENTIDAD — cédulas analizadas por OCR (flujo consulta)
--    Reemplaza: registro_cedulas.csv
-- ================================================================
CREATE TABLE documentos_identidad (
    id                  SERIAL PRIMARY KEY,
    sesion_id           VARCHAR(80) REFERENCES sesiones(sesion_id),
    numero_whatsapp     VARCHAR(20) NOT NULL REFERENCES clientes(numero_whatsapp),
    fecha_hora          TIMESTAMP DEFAULT NOW(),
    nombre_archivo      VARCHAR(200),
    ruta_imagen         VARCHAR(300),
    media_id            VARCHAR(100),
    cedula_ingresada    VARCHAR(15),
    cedula_ocr          VARCHAR(15),
    tipo_documento      VARCHAR(30),
    nombres             VARCHAR(150),
    apellidos           VARCHAR(150),
    fecha_nacimiento    VARCHAR(20),
    fecha_expiracion    VARCHAR(20),
    nacionalidad        VARCHAR(50),
    calidad_lectura     VARCHAR(20),
    coincide            BOOLEAN,
    ocr_valido          BOOLEAN,
    observaciones       TEXT
);
CREATE INDEX idx_docid_wa      ON documentos_identidad(numero_whatsapp);
CREATE INDEX idx_docid_cedula  ON documentos_identidad(cedula_ingresada);
CREATE INDEX idx_docid_sesion  ON documentos_identidad(sesion_id);

-- ================================================================
-- VISTA: interacción 360° por sesión — "vista total del cliente"
-- ================================================================
CREATE VIEW vista_interaccion_completa AS
SELECT
    s.sesion_id, s.numero_whatsapp, c.nombre, s.bot, s.intencion,
    s.estado_sesion, s.resultado, s.inicio, s.fin,
    EXTRACT(EPOCH FROM (s.fin - s.inicio)) AS duracion_segundos,
    s.intentos_comprobante,
    (SELECT COUNT(*) FROM eventos_interaccion e WHERE e.sesion_id = s.sesion_id) AS total_eventos,
    (SELECT COUNT(*) FROM eventos_interaccion e WHERE e.sesion_id = s.sesion_id
        AND e.paso IN ('cedula_invalida','comprobante_duplicado','ocr_ilegible','monto_no_coincide')) AS eventos_friccion,
    cp.numero_transaccion, cp.score_confianza, di.cedula_ocr
FROM sesiones s
JOIN clientes c ON c.numero_whatsapp = s.numero_whatsapp
LEFT JOIN comprobantes cp ON cp.sesion_id = s.sesion_id
LEFT JOIN documentos_identidad di ON di.sesion_id = s.sesion_id;
