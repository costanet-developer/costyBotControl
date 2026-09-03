--
-- PostgreSQL database dump
--

\restrict nVgQkXb63g4L7Fc8mzq78Vpc8GvK2avYitf3Fg0WwJ8cpnQpixdHCcgXSssMG4n

-- Dumped from database version 14.23 (Ubuntu 14.23-0ubuntu0.22.04.1)
-- Dumped by pg_dump version 14.23 (Ubuntu 14.23-0ubuntu0.22.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: set_updated_at(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.set_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN NEW.updated_at = NOW(); RETURN NEW; END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: alertas_operativas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.alertas_operativas (
    id bigint NOT NULL,
    clave character varying(190) NOT NULL,
    caso_operativo_id bigint NOT NULL,
    tipo character varying(40) NOT NULL,
    nivel character varying(15) NOT NULL,
    estado character varying(20) DEFAULT 'pendiente'::character varying NOT NULL,
    destinatarios json,
    notificada_en timestamp(0) without time zone,
    estado_email character varying(20) DEFAULT 'deshabilitado'::character varying NOT NULL,
    email_enviado_en timestamp(0) without time zone,
    ultimo_error text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: alertas_operativas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.alertas_operativas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: alertas_operativas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.alertas_operativas_id_seq OWNED BY public.alertas_operativas.id;


--
-- Name: auditoria_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.auditoria_logs (
    id bigint NOT NULL,
    usuario_id bigint,
    accion character varying(50) NOT NULL,
    modulo character varying(50) NOT NULL,
    entidad character varying(60),
    entidad_id bigint,
    datos_anteriores json,
    datos_nuevos json,
    direccion_ip character varying(45),
    user_agent text,
    resultado character varying(20) DEFAULT 'exitoso'::character varying NOT NULL,
    descripcion text,
    fecha_hora timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: auditoria_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.auditoria_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: auditoria_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.auditoria_logs_id_seq OWNED BY public.auditoria_logs.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: casos_operativos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.casos_operativos (
    id bigint NOT NULL,
    clave character varying(190) NOT NULL,
    tipo character varying(50) NOT NULL,
    prioridad character varying(15) DEFAULT 'media'::character varying NOT NULL,
    estado character varying(20) DEFAULT 'pendiente'::character varying NOT NULL,
    sesion_id character varying(80),
    comprobante_id bigint,
    saldo_favor_id bigint,
    validacion_identidad_id bigint,
    otp_verificacion_id bigint,
    titulo character varying(180) NOT NULL,
    detalle json,
    detectado_en timestamp(0) without time zone NOT NULL,
    ultima_deteccion_en timestamp(0) without time zone NOT NULL,
    asignado_a bigint,
    asignado_en timestamp(0) without time zone,
    resuelto_por bigint,
    resuelto_en timestamp(0) without time zone,
    resolucion text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: casos_operativos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.casos_operativos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: casos_operativos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.casos_operativos_id_seq OWNED BY public.casos_operativos.id;


--
-- Name: clientes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.clientes (
    numero_whatsapp character varying(20) NOT NULL,
    cedula character varying(15),
    nombre character varying(100),
    idcliente_costanet integer,
    primera_interaccion timestamp(0) without time zone,
    ultima_interaccion timestamp(0) without time zone,
    correo_registrado character varying(150)
);


--
-- Name: comprobantes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.comprobantes (
    id bigint NOT NULL,
    sesion_id character varying(80),
    numero_whatsapp character varying(20),
    origen character varying(20),
    fecha_hora timestamp(0) without time zone,
    nombre_archivo character varying(200),
    ruta_imagen character varying(300),
    media_id character varying(100),
    cedula character varying(15),
    banco character varying(100),
    monto numeric(12,2),
    fecha_comprobante character varying(20),
    numero_transaccion character varying(100),
    titular character varying(150),
    cuenta_destino character varying(50),
    estado character varying(30),
    banco_valido boolean,
    cuenta_valida boolean,
    titular_valido boolean,
    riesgo_visual character varying(20),
    alertas json,
    probabilidad_ia_generativa integer,
    riesgo_ia_generativa character varying(20),
    alertas_ia_generativa json,
    score_confianza integer,
    accion_recomendada character varying(30),
    estado_auditoria character varying(20) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    revisado_por bigint,
    revisado_en timestamp(0) without time zone,
    aprobado_por bigint,
    aprobado_en timestamp(0) without time zone,
    rechazado_por bigint,
    rechazado_en timestamp(0) without time zone,
    motivo_rechazo text,
    tiene_observaciones boolean DEFAULT false NOT NULL,
    deleted_at timestamp(0) without time zone,
    deleted_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    updated_by bigint,
    numero_documento character varying(100),
    titular_origen character varying(150),
    cuenta_origen character varying(80)
);


--
-- Name: comprobantes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.comprobantes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: comprobantes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.comprobantes_id_seq OWNED BY public.comprobantes.id;


--
-- Name: documentos_identidad; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.documentos_identidad (
    id bigint NOT NULL,
    sesion_id character varying(80) NOT NULL,
    numero_whatsapp character varying(20) NOT NULL,
    fecha_hora timestamp(0) without time zone,
    nombre_archivo character varying(200),
    ruta_imagen character varying(300),
    media_id character varying(100),
    cedula_ingresada character varying(15),
    cedula_ocr character varying(15),
    tipo_documento character varying(30),
    nombres character varying(150),
    apellidos character varying(150),
    fecha_nacimiento character varying(20),
    fecha_expiracion character varying(20),
    nacionalidad character varying(50),
    calidad_lectura character varying(20),
    coincide boolean,
    ocr_valido boolean,
    observaciones text,
    lado character varying(10),
    ocr_json json,
    ocr_confianza numeric(5,2),
    sexo character varying(30),
    estado_civil character varying(50),
    codigo_dactilar character varying(10),
    emisor_documento character varying(50)
);


--
-- Name: documentos_identidad_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.documentos_identidad_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: documentos_identidad_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.documentos_identidad_id_seq OWNED BY public.documentos_identidad.id;


--
-- Name: eventos_interaccion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.eventos_interaccion (
    id bigint NOT NULL,
    sesion_id character varying(80) NOT NULL,
    numero_whatsapp character varying(20) NOT NULL,
    fecha_evento timestamp(0) without time zone,
    paso character varying(60) NOT NULL,
    estado_conversacion character varying(50),
    intentos_comprobante integer,
    cedula character varying(15),
    tipo_comprobante character varying(50),
    duplicado boolean,
    opcion_ocr integer,
    monto_esperado numeric(12,2),
    deuda_total numeric(12,2),
    datos_adicionales json
);


--
-- Name: eventos_interaccion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.eventos_interaccion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: eventos_interaccion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.eventos_interaccion_id_seq OWNED BY public.eventos_interaccion.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data json NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: observaciones_interaccion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.observaciones_interaccion (
    id bigint NOT NULL,
    sesion_id character varying(80),
    comprobante_id bigint,
    usuario_id bigint NOT NULL,
    observacion text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: observaciones_interaccion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.observaciones_interaccion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: observaciones_interaccion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.observaciones_interaccion_id_seq OWNED BY public.observaciones_interaccion.id;


--
-- Name: otp_verificaciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.otp_verificaciones (
    id bigint NOT NULL,
    sesion_id character varying(80) NOT NULL,
    numero_whatsapp character varying(20) NOT NULL,
    correo character varying(150) NOT NULL,
    codigo_enviado character varying(64),
    codigo_ingresado character varying(64),
    resultado character varying(30),
    creado_en timestamp(0) without time zone,
    cedula character varying(15),
    expira_en timestamp(0) without time zone,
    intentos integer DEFAULT 0 NOT NULL,
    max_intentos integer DEFAULT 3 NOT NULL
);


--
-- Name: otp_verificaciones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.otp_verificaciones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: otp_verificaciones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.otp_verificaciones_id_seq OWNED BY public.otp_verificaciones.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: revisiones_comprobante; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.revisiones_comprobante (
    id bigint NOT NULL,
    comprobante_id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    estado_anterior character varying(20),
    estado_nuevo character varying(20) NOT NULL,
    observacion text,
    fecha_revision timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: revisiones_comprobante_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.revisiones_comprobante_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: revisiones_comprobante_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.revisiones_comprobante_id_seq OWNED BY public.revisiones_comprobante.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: saldos_a_favor; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.saldos_a_favor (
    id bigint NOT NULL,
    sesion_id character varying(100) NOT NULL,
    numero_whatsapp character varying(20) NOT NULL,
    cedula character varying(20),
    idcliente integer,
    idfactura integer,
    monto_pagado numeric(12,2),
    monto_factura numeric(12,2),
    excedente numeric(12,2),
    numero_transaccion character varying(50),
    comprobante_id bigint,
    estado character varying(20),
    origen character varying(30),
    fecha_registro timestamp(0) without time zone
);


--
-- Name: saldos_a_favor_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.saldos_a_favor_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: saldos_a_favor_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.saldos_a_favor_id_seq OWNED BY public.saldos_a_favor.id;


--
-- Name: sesiones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sesiones (
    id bigint NOT NULL,
    sesion_id character varying(80) NOT NULL,
    numero_whatsapp character varying(20) NOT NULL,
    bot character varying(30),
    intencion character varying(30),
    cedula character varying(15),
    estado_sesion character varying(20) DEFAULT 'activa'::character varying NOT NULL,
    resultado character varying(40),
    intentos_comprobante integer DEFAULT 0 NOT NULL,
    inicio timestamp(0) without time zone,
    fin timestamp(0) without time zone,
    media_id character varying(100),
    cedula_media_id character varying(100),
    mensajes_procesados json,
    es_multiples_servicios boolean DEFAULT false NOT NULL,
    servicios_disponibles json,
    codigo_servicio_elegido character varying(30),
    comprobante_id bigint,
    menu_generado_en bigint,
    ultima_actividad bigint
);


--
-- Name: sesiones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sesiones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sesiones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sesiones_id_seq OWNED BY public.sesiones.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    nombre character varying(255) NOT NULL,
    apellido character varying(255),
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    bloqueado boolean DEFAULT false NOT NULL,
    intentos_fallidos integer DEFAULT 0 NOT NULL,
    ultimo_acceso timestamp(0) without time zone,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    creado_por bigint,
    actualizado_por bigint
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: validaciones_identidad; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.validaciones_identidad (
    id bigint NOT NULL,
    sesion_id character varying(80) NOT NULL,
    numero_whatsapp character varying(20) NOT NULL,
    cedula character varying(15) NOT NULL,
    cedula_ingresada_en timestamp(0) without time zone,
    anverso_recibido_en timestamp(0) without time zone,
    reverso_recibido_en timestamp(0) without time zone,
    ocr_vs_sistema_resultado character varying(20),
    ocr_vs_sistema_detalle json,
    codigo_dactilar_validado boolean,
    correo character varying(150),
    correo_verificado boolean DEFAULT false NOT NULL,
    otp_codigo character varying(6),
    otp_expira_en timestamp(0) without time zone,
    otp_intentos integer DEFAULT 0 NOT NULL,
    estado_kyc character varying(30),
    intentos_fallidos_comparacion integer DEFAULT 0 NOT NULL,
    derivado_revision boolean DEFAULT false NOT NULL,
    actualizado_en timestamp(0) without time zone
);


--
-- Name: validaciones_identidad_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.validaciones_identidad_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: validaciones_identidad_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.validaciones_identidad_id_seq OWNED BY public.validaciones_identidad.id;


--
-- Name: alertas_operativas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alertas_operativas ALTER COLUMN id SET DEFAULT nextval('public.alertas_operativas_id_seq'::regclass);


--
-- Name: auditoria_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria_logs ALTER COLUMN id SET DEFAULT nextval('public.auditoria_logs_id_seq'::regclass);


--
-- Name: casos_operativos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.casos_operativos ALTER COLUMN id SET DEFAULT nextval('public.casos_operativos_id_seq'::regclass);


--
-- Name: comprobantes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comprobantes ALTER COLUMN id SET DEFAULT nextval('public.comprobantes_id_seq'::regclass);


--
-- Name: documentos_identidad id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documentos_identidad ALTER COLUMN id SET DEFAULT nextval('public.documentos_identidad_id_seq'::regclass);


--
-- Name: eventos_interaccion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.eventos_interaccion ALTER COLUMN id SET DEFAULT nextval('public.eventos_interaccion_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: observaciones_interaccion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.observaciones_interaccion ALTER COLUMN id SET DEFAULT nextval('public.observaciones_interaccion_id_seq'::regclass);


--
-- Name: otp_verificaciones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.otp_verificaciones ALTER COLUMN id SET DEFAULT nextval('public.otp_verificaciones_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: revisiones_comprobante id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revisiones_comprobante ALTER COLUMN id SET DEFAULT nextval('public.revisiones_comprobante_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: saldos_a_favor id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.saldos_a_favor ALTER COLUMN id SET DEFAULT nextval('public.saldos_a_favor_id_seq'::regclass);


--
-- Name: sesiones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sesiones ALTER COLUMN id SET DEFAULT nextval('public.sesiones_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: validaciones_identidad id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.validaciones_identidad ALTER COLUMN id SET DEFAULT nextval('public.validaciones_identidad_id_seq'::regclass);


--
-- Data for Name: alertas_operativas; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.alertas_operativas (id, clave, caso_operativo_id, tipo, nivel, estado, destinatarios, notificada_en, estado_email, email_enviado_en, ultimo_error, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: auditoria_logs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.auditoria_logs (id, usuario_id, accion, modulo, entidad, entidad_id, datos_anteriores, datos_nuevos, direccion_ip, user_agent, resultado, descripcion, fecha_hora) FROM stdin;
67	\N	pagar_factura	costy_reactivacion	factura_costanet	\N	\N	"{\\"sesion_id\\":\\"593986980424_1787342569510\\",\\"respuesta\\":{\\"estado\\":\\"exito\\",\\"salida\\":\\"Pago registrado correctamente.\\",\\"id\\":226609}}"	\N	\N	exitoso	Sesion 593986980424_1787342569510	2026-08-21 20:03:28
68	\N	activar_servicio	costy_reactivacion	cliente_costanet	7361	\N	"{\\"sesion_id\\":\\"593986980424_1787342569510\\",\\"respuesta\\":{\\"estado\\":\\"error\\",\\"mensaje\\":\\"El cliente se encuentra con estado ACTIVO\\"}}"	\N	\N	exitoso	Sesion 593986980424_1787342569510	2026-08-21 20:03:28
69	\N	pagar_factura	costy_reactivacion	factura_costanet	\N	\N	"{\\"sesion_id\\":\\"593967477270_1787343298447\\",\\"respuesta\\":{\\"estado\\":\\"exito\\",\\"salida\\":\\"Pago registrado correctamente.\\",\\"id\\":226612}}"	\N	\N	exitoso	Sesion 593967477270_1787343298447	2026-08-21 20:16:00
70	\N	activar_servicio	costy_reactivacion	cliente_costanet	10160	\N	"{\\"sesion_id\\":\\"593967477270_1787343298447\\",\\"respuesta\\":{\\"estado\\":\\"error\\",\\"mensaje\\":\\"El cliente se encuentra con estado ACTIVO\\"}}"	\N	\N	exitoso	Sesion 593967477270_1787343298447	2026-08-21 20:16:00
71	\N	pagar_factura	costy_reactivacion	factura_costanet	\N	\N	"{\\"sesion_id\\":\\"593969904000_1787344138703\\",\\"respuesta\\":{\\"estado\\":\\"exito\\",\\"salida\\":\\"Pago registrado correctamente.\\",\\"id\\":226613}}"	\N	\N	exitoso	Sesion 593969904000_1787344138703	2026-08-21 20:29:22
72	\N	activar_servicio	costy_reactivacion	cliente_costanet	7650	\N	"{\\"sesion_id\\":\\"593969904000_1787344138703\\",\\"respuesta\\":{\\"estado\\":\\"error\\",\\"mensaje\\":\\"El cliente se encuentra con estado ACTIVO\\"}}"	\N	\N	exitoso	Sesion 593969904000_1787344138703	2026-08-21 20:29:22
73	\N	pagar_factura	costy_reactivacion	factura_costanet	\N	\N	"{\\"sesion_id\\":\\"593969904000_1787344194194\\",\\"respuesta\\":{\\"estado\\":\\"exito\\",\\"salida\\":\\"Pago registrado correctamente.\\",\\"id\\":226614}}"	\N	\N	exitoso	Sesion 593969904000_1787344194194	2026-08-21 20:30:16
74	\N	activar_servicio	costy_reactivacion	cliente_costanet	10685	\N	"{\\"sesion_id\\":\\"593969904000_1787344194194\\",\\"respuesta\\":{\\"estado\\":\\"error\\",\\"mensaje\\":\\"El cliente se encuentra con estado ACTIVO\\"}}"	\N	\N	exitoso	Sesion 593969904000_1787344194194	2026-08-21 20:30:17
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache (key, value, expiration) FROM stdin;
costybo-cache-desarrollador@costanetplus.net|10.100.70.1:timer	i:1787345201;	1787345201
costybo-cache-desarrollador@costanetplus.net|10.100.70.1	i:1;	1787345201
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: casos_operativos; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.casos_operativos (id, clave, tipo, prioridad, estado, sesion_id, comprobante_id, saldo_favor_id, validacion_identidad_id, otp_verificacion_id, titulo, detalle, detectado_en, ultima_deteccion_en, asignado_a, asignado_en, resuelto_por, resuelto_en, resolucion, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: clientes; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.clientes (numero_whatsapp, cedula, nombre, idcliente_costanet, primera_interaccion, ultima_interaccion, correo_registrado) FROM stdin;
593986980424	0914653084	🌍	\N	\N	2026-08-21 15:03:09	\N
593993317017	\N	eswincorreac	\N	\N	2026-08-21 15:10:17	\N
593967477270	0929465524	Cuadrilla Isla Puna	\N	\N	2026-08-21 15:15:44	\N
593969904000	\N	Costanet	\N	\N	2026-08-21 15:29:55	\N
\.


--
-- Data for Name: comprobantes; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.comprobantes (id, sesion_id, numero_whatsapp, origen, fecha_hora, nombre_archivo, ruta_imagen, media_id, cedula, banco, monto, fecha_comprobante, numero_transaccion, titular, cuenta_destino, estado, banco_valido, cuenta_valida, titular_valido, riesgo_visual, alertas, probabilidad_ia_generativa, riesgo_ia_generativa, alertas_ia_generativa, score_confianza, accion_recomendada, estado_auditoria, revisado_por, revisado_en, aprobado_por, aprobado_en, rechazado_por, rechazado_en, motivo_rechazo, tiene_observaciones, deleted_at, deleted_by, created_at, updated_at, updated_by, numero_documento, titular_origen, cuenta_origen) FROM stdin;
13	593969904000_1787342126221	593969904000	ocr_automatico	\N	sin_cedula_2026-08-21_14-55-37_593969904000.jpg	/home/Tlsg_n8n/whatsapp_imagenes/sin_cedula_2026-08-21_14-55-37_593969904000.jpg	1692572425156987	\N	BANCO GUAYAQUIL	20.00	21/08/2026	19122020608211441280	TELECOMNET S A S	48****64	pendiente_cedula	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	\N	\N	\N	Jose Romero	\N
14	593986980424_1787342569510	593986980424	ocr_automatico	\N	\N	\N	\N	0914653084	\N	20.00	\N	4136 3810	\N	\N	reactivacion_exitosa	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	\N	\N	\N	Cedeno Rivas Elvis Javier	221121 5728
15	593993317017_1787343012457	593993317017	ocr_automatico	\N	sin_cedula_2026-08-21_15-10-26_593993317017.jpg	/home/Tlsg_n8n/whatsapp_imagenes/sin_cedula_2026-08-21_15-10-26_593993317017.jpg	2331293921024748	\N	BANCO GUAYAQUIL	15.00	21/08/2026	37975	TELECOM NET SAS	48••64	pendiente_cedula	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	\N	\N	\N	Eswin Correa	62••92
16	593967477270_1787343298447	593967477270	ocr_automatico	\N	\N	\N	\N	0929465524	\N	30.00	\N	9135555	\N	\N	reactivacion_exitosa	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	\N	\N	136041	LUCERITO	\N
17	593969904000_1787344138703	593969904000	ocr_automatico	\N	\N	\N	\N	0942333865	\N	20.00	\N	0009251565	\N	\N	reactivacion_exitosa	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	\N	\N	\N	Jaime Pluas Danny Samuel	001XXX0243
18	593969904000_1787344194194	593969904000	ocr_automatico	\N	\N	\N	\N	2400281735	\N	20.00	\N	0020092785	\N	\N	reactivacion_exitosa	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	PENDIENTE	\N	\N	\N	\N	\N	\N	\N	f	\N	\N	\N	\N	\N	\N	Telecom	210XXX9012
\.


--
-- Data for Name: documentos_identidad; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.documentos_identidad (id, sesion_id, numero_whatsapp, fecha_hora, nombre_archivo, ruta_imagen, media_id, cedula_ingresada, cedula_ocr, tipo_documento, nombres, apellidos, fecha_nacimiento, fecha_expiracion, nacionalidad, calidad_lectura, coincide, ocr_valido, observaciones, lado, ocr_json, ocr_confianza, sexo, estado_civil, codigo_dactilar, emisor_documento) FROM stdin;
\.


--
-- Data for Name: eventos_interaccion; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.eventos_interaccion (id, sesion_id, numero_whatsapp, fecha_evento, paso, estado_conversacion, intentos_comprobante, cedula, tipo_comprobante, duplicado, opcion_ocr, monto_esperado, deuda_total, datos_adicionales) FROM stdin;
2	593969904000_1787342126221	593969904000	2026-08-21 19:55:26	mensaje_recibido	inicio	0	0917934382	\N	\N	\N	\N	\N	{"media_id": null, "button_id": null, "tipo_mensaje": "text", "wa_message_id": "wamid.HBgMNTkzOTY5OTA0MDAwFQIAEhgWM0VCMDg1Rjk3Q0MxNzc4Qzc0NUFBNgA="}
3	593969904000_1787342126221	593969904000	2026-08-21 19:55:26	mensaje_recibido	inicio	0	\N	\N	\N	\N	\N	\N	{"media_id": "1692572425156987", "button_id": null, "tipo_mensaje": "image", "wa_message_id": "wamid.HBgMNTkzOTY5OTA0MDAwFQIAEhgWM0VCMENGM0UwMUUzN0YxQzMzMzE2NQA="}
4	593969904000_1787342126221	593969904000	2026-08-21 19:55:41	interaccion_finalizada	cuenta_al_dia	0	0917934382	\N	\N	\N	\N	\N	{"wa_message_id": "wamid.HBgMNTkzOTY5OTA0MDAwFQIAEhgWM0VCMDg1Rjk3Q0MxNzc4Qzc0NUFBNgA="}
5	593986980424_1787342569510	593986980424	2026-08-21 20:02:50	mensaje_recibido	inicio	0	\N	\N	\N	\N	\N	\N	{"media_id": "1539110630754930", "button_id": null, "tipo_mensaje": "image", "wa_message_id": "wamid.HBgMNTkzOTg2OTgwNDI0FQIAEhggQUM5NzMxQzk0NTZENjg1ODA5M0FEMzExNDMyMkMxRDQA"}
6	593986980424_1787342569510	593986980424	2026-08-21 20:03:09	mensaje_recibido	esperando_cedula	0	0914653084	\N	\N	\N	\N	\N	{"media_id": null, "button_id": null, "tipo_mensaje": "text", "wa_message_id": "wamid.HBgMNTkzOTg2OTgwNDI0FQIAEhggQUMwQ0UwQURCNzBGQzQ3MTczMDg4MDZERTczQUY2MEIA"}
7	593993317017_1787342942135	593993317017	2026-08-21 20:09:02	mensaje_recibido	inicio	0	\N	\N	\N	\N	\N	\N	{"media_id": null, "button_id": null, "tipo_mensaje": "text", "wa_message_id": "wamid.HBgMNTkzOTkzMzE3MDE3FQIAEhggQUM2NzYwRDMyRDQ5MkE4OEU5MjRDQUJGMzZFQUMyODkA"}
8	593993317017_1787343012457	593993317017	2026-08-21 20:10:12	mensaje_recibido	inicio	0	1101952750	\N	\N	\N	\N	\N	{"media_id": null, "button_id": null, "tipo_mensaje": "text", "wa_message_id": "wamid.HBgMNTkzOTkzMzE3MDE3FQIAEhggQUNEODg0MDJGNkVCQTA3Nzc5Rjg0RTBDMjgwRUE1N0YA"}
9	593993317017_1787343012457	593993317017	2026-08-21 20:10:17	mensaje_recibido	inicio	0	\N	\N	\N	\N	\N	\N	{"media_id": "2331293921024748", "button_id": null, "tipo_mensaje": "image", "wa_message_id": "wamid.HBgMNTkzOTkzMzE3MDE3FQIAEhggQUM3NzA1MDlBRTM0QkZBQjFGQzE2QzgzRjEwNDQ5Q0UA"}
10	593993317017_1787343012457	593993317017	2026-08-21 20:10:44	interaccion_finalizada	cuenta_al_dia	0	1101952750	\N	\N	\N	\N	\N	{"wa_message_id": "wamid.HBgMNTkzOTkzMzE3MDE3FQIAEhggQUNEODg0MDJGNkVCQTA3Nzc5Rjg0RTBDMjgwRUE1N0YA"}
11	593967477270_1787343298447	593967477270	2026-08-21 20:14:58	mensaje_recibido	inicio	0	\N	\N	\N	\N	\N	\N	{"media_id": null, "button_id": null, "tipo_mensaje": "text", "wa_message_id": "wamid.HBgMNTkzOTY3NDc3MjcwFQIAEhggQUM1MEIwNjY3M0I4RDBFRUNDNEQ4OUU4MUUxQ0U4Q0YA"}
12	593967477270_1787343298447	593967477270	2026-08-21 20:15:08	mensaje_recibido	inicio	0	\N	\N	\N	\N	\N	\N	{"media_id": "1716738759626251", "button_id": null, "tipo_mensaje": "image", "wa_message_id": "wamid.HBgMNTkzOTY3NDc3MjcwFQIAEhggQUMyRTU3QzcxMjA3MEY4MUVFMzI0RDQ5RTU5OTZGNjcA"}
13	593967477270_1787343298447	593967477270	2026-08-21 15:15:11	menu_principal_mostrado	inicio	\N	\N	\N	f	\N	\N	\N	{"orden_ms":1787343311916,"execution_id":"85797","wa_message_id":"wamid.HBgMNTkzOTY3NDc3MjcwFQIAEhggQUM1MEIwNjY3M0I4RDBFRUNDNEQ4OUU4MUUxQ0U4Q0YA","timestamp_whatsapp":"1787343296","mensaje":"Buenas tardes","tipo_mensaje":"text","boton_id":null,"boton_titulo":null,"nombre_contacto":"Cuadrilla Isla Puna"}
14	593967477270_1787343298447	593967477270	2026-08-21 20:15:18	mensaje_recibido	inicio	0	\N	menu_reactivar	\N	\N	\N	\N	{"media_id": null, "button_id": "menu_reactivar", "tipo_mensaje": "interactive", "wa_message_id": "wamid.HBgMNTkzOTY3NDc3MjcwFQIAEhggQUM3MzIzOTA3MTY3NUEyOEZDNUQwMTE1ODRBRUE2NjYA"}
15	593967477270_1787343298447	593967477270	2026-08-21 20:15:22	mensaje_recibido	esperando_imagen_y_cedula	0	\N	\N	\N	\N	\N	\N	{"media_id": "1084991520547483", "button_id": null, "tipo_mensaje": "image", "wa_message_id": "wamid.HBgMNTkzOTY3NDc3MjcwFQIAEhggQUNCNERGQTIzNzlBMTI4REExM0U5RTQ5NUI5QjI2Q0UA"}
16	593967477270_1787343298447	593967477270	2026-08-21 20:15:44	mensaje_recibido	esperando_cedula	0	0929465524	\N	\N	\N	\N	\N	{"media_id": null, "button_id": null, "tipo_mensaje": "text", "wa_message_id": "wamid.HBgMNTkzOTY3NDc3MjcwFQIAEhggQUNFOTVEQUZDODEyRDE5MTYwMjI2NEU0NUQzNkNEM0YA"}
17	593969904000_1787344100206	593969904000	2026-08-21 20:28:20	mensaje_recibido	inicio	0	0942333865	\N	\N	\N	\N	\N	{"media_id": null, "button_id": null, "tipo_mensaje": "text", "wa_message_id": "wamid.HBgMNTkzOTY5OTA0MDAwFQIAEhgWM0VCMDVDMjI2NTIyM0NCMkRCMDRDRAA="}
18	593969904000_1787344100206	593969904000	2026-08-21 20:28:21	mensaje_recibido	inicio	0	\N	\N	\N	\N	\N	\N	{"media_id": "4570276496572994", "button_id": null, "tipo_mensaje": "image", "wa_message_id": "wamid.HBgMNTkzOTY5OTA0MDAwFQIAEhgWM0VCMDI2MTcwNkI5ODhENjcyODJBQwA="}
19	593969904000_1787344138703	593969904000	2026-08-21 20:28:59	mensaje_recibido	inicio	0	0942333865	\N	\N	\N	\N	\N	{"media_id": null, "button_id": null, "tipo_mensaje": "text", "wa_message_id": "wamid.HBgMNTkzOTY5OTA0MDAwFQIAEhgWM0VCMDBDNEY2OTNFQTA4NjBEMkEyNAA="}
20	593969904000_1787344138703	593969904000	2026-08-21 20:28:59	mensaje_recibido	inicio	0	\N	\N	\N	\N	\N	\N	{"media_id": "1537445777619663", "button_id": null, "tipo_mensaje": "image", "wa_message_id": "wamid.HBgMNTkzOTY5OTA0MDAwFQIAEhgWM0VCMDRBMzAyNEI1Rjc1MDg5MzIyMwA="}
21	593969904000_1787344194194	593969904000	2026-08-21 20:29:54	mensaje_recibido	inicio	0	2400281735	\N	\N	\N	\N	\N	{"media_id": null, "button_id": null, "tipo_mensaje": "text", "wa_message_id": "wamid.HBgMNTkzOTY5OTA0MDAwFQIAEhgWM0VCMERCQjk4NDU0RjE4N0ZDMTNENwA="}
22	593969904000_1787344194194	593969904000	2026-08-21 20:29:55	mensaje_recibido	inicio	0	\N	\N	\N	\N	\N	\N	{"media_id": "1613375830495912", "button_id": null, "tipo_mensaje": "image", "wa_message_id": "wamid.HBgMNTkzOTY5OTA0MDAwFQIAEhgWM0VCMDFFMTVFNTEyMTgzNzY2NzNCRAA="}
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_07_29_000000_create_bot_core_tables_for_clean_installations	1
5	2026_07_30_113553_create_auditoria_logs_table	1
6	2026_07_30_142957_add_auditoria_columns_to_users_table	1
7	2026_07_30_161812_create_permission_tables	1
8	2026_07_30_162813_add_auditoria_columns_to_comprobantes_table	1
9	2026_07_31_021338_create_observaciones_interaccion_table	1
10	2026_07_31_021339_create_revisiones_comprobante_table	1
11	2026_08_21_000001_add_payment_origin_fields_to_comprobantes_table	1
12	2026_08_21_000002_add_identity_document_permissions	1
13	2026_08_21_000003_create_casos_operativos_table	1
14	2026_08_21_000004_add_case_management_permission	1
15	2026_08_21_000005_add_audit_center_support	1
16	2026_08_21_000006_create_operational_alerts_and_notifications	1
\.


--
-- Data for Name: model_has_permissions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.model_has_permissions (permission_id, model_type, model_id) FROM stdin;
\.


--
-- Data for Name: model_has_roles; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.model_has_roles (role_id, model_type, model_id) FROM stdin;
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.notifications (id, type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: observaciones_interaccion; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.observaciones_interaccion (id, sesion_id, comprobante_id, usuario_id, observacion, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: otp_verificaciones; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.otp_verificaciones (id, sesion_id, numero_whatsapp, correo, codigo_enviado, codigo_ingresado, resultado, creado_en, cedula, expira_en, intentos, max_intentos) FROM stdin;
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.permissions (id, name, guard_name, created_at, updated_at) FROM stdin;
1	documentos_identidad.ver	web	2026-08-21 14:53:01	2026-08-21 14:53:01
2	documentos_identidad.descargar	web	2026-08-21 14:53:01	2026-08-21 14:53:01
3	casos_operativos.gestionar	web	2026-08-21 14:53:01	2026-08-21 14:53:01
4	auditoria.exportar	web	2026-08-21 14:53:01	2026-08-21 14:53:01
\.


--
-- Data for Name: revisiones_comprobante; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.revisiones_comprobante (id, comprobante_id, usuario_id, estado_anterior, estado_nuevo, observacion, fecha_revision) FROM stdin;
\.


--
-- Data for Name: role_has_permissions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.role_has_permissions (permission_id, role_id) FROM stdin;
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.roles (id, name, guard_name, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: saldos_a_favor; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.saldos_a_favor (id, sesion_id, numero_whatsapp, cedula, idcliente, idfactura, monto_pagado, monto_factura, excedente, numero_transaccion, comprobante_id, estado, origen, fecha_registro) FROM stdin;
\.


--
-- Data for Name: sesiones; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sesiones (id, sesion_id, numero_whatsapp, bot, intencion, cedula, estado_sesion, resultado, intentos_comprobante, inicio, fin, media_id, cedula_media_id, mensajes_procesados, es_multiples_servicios, servicios_disponibles, codigo_servicio_elegido, comprobante_id, menu_generado_en, ultima_actividad) FROM stdin;
31	593967477270_1787343298447	593967477270	reactivacion	\N	0929465524	cerrada	reactivado	0	\N	2026-08-21 15:16:03	\N	\N	\N	f	\N	\N	16	\N	1787343363108
24	593969904000_1787342126221	593969904000	reactivacion	\N	0917934382	cerrada	cuenta_al_dia	0	\N	2026-08-21 19:55:41	\N	\N	\N	f	\N	\N	13	\N	1787342138145
36	593969904000_1787344100206	593969904000	reactivacion	\N	0942333865	cerrada	no_reconocido	0	\N	2026-08-21 15:28:39	\N	\N	\N	f	\N	\N	17	\N	1787344108248
26	593986980424_1787342569510	593986980424	reactivacion	\N	0914653084	cerrada	reactivado	0	\N	2026-08-21 15:03:31	\N	\N	\N	f	\N	\N	14	\N	1787342611024
28	593993317017_1787342942135	593993317017	reactivacion	\N	\N	cerrada	no_reconocido	0	\N	2026-08-21 15:09:21	\N	\N	\N	f	\N	\N	\N	\N	\N
29	593993317017_1787343012457	593993317017	reactivacion	\N	1101952750	cerrada	cuenta_al_dia	0	\N	2026-08-21 20:10:44	\N	\N	\N	f	\N	\N	15	\N	1787343026734
38	593969904000_1787344138703	593969904000	reactivacion	\N	0942333865	cerrada	reactivado	0	\N	2026-08-21 15:29:26	\N	\N	\N	f	\N	\N	17	\N	1787344165437
40	593969904000_1787344194194	593969904000	reactivacion	\N	2400281735	cerrada	reactivado	0	\N	2026-08-21 15:30:21	\N	\N	\N	f	\N	\N	18	\N	1787344220389
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
te0u5khKtdLv4W56xOu9ITSje8skW547cilXyOdP	\N	10.100.70.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0	eyJfdG9rZW4iOiI5S0dwYXVWQjQxdEp4aDFlOURqRk5kVHA0dHV4cjZESFFSM2s3dlJiIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE5Mi4xNjguMjMwLjUxOjE3MTJcL2xvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19	1787345141
EbkST8oaPzi7ZyfKYNSEUfiSKgn1txAUzepk299T	\N	127.0.0.1	Symfony	eyJfdG9rZW4iOiJOTnZvVGREeUs4c3o3S21vM1oxUEFlbFF4eXdJb1UxT05XUk5JSnVKIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=	1787341984
uQ39RDJCU4P03sLv6XcSmrWIG9n1WBhXGi7oRzUn	\N	10.100.70.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	eyJfdG9rZW4iOiIwY3ZBQTJad2RacnVIWHJpQmEwUHZlTldzd0Q0WjRpOUN0Nkp0SXg5IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTkyLjE2OC4yMzAuNTE6MTcxMlwvcGVuZGllbnRlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTkyLjE2OC4yMzAuNTE6MTcxMlwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=	1787342402
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.users (id, nombre, apellido, email, email_verified_at, password, activo, bloqueado, intentos_fallidos, ultimo_acceso, remember_token, created_at, updated_at, deleted_at, creado_por, actualizado_por) FROM stdin;
\.


--
-- Data for Name: validaciones_identidad; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.validaciones_identidad (id, sesion_id, numero_whatsapp, cedula, cedula_ingresada_en, anverso_recibido_en, reverso_recibido_en, ocr_vs_sistema_resultado, ocr_vs_sistema_detalle, codigo_dactilar_validado, correo, correo_verificado, otp_codigo, otp_expira_en, otp_intentos, estado_kyc, intentos_fallidos_comparacion, derivado_revision, actualizado_en) FROM stdin;
\.


--
-- Name: alertas_operativas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.alertas_operativas_id_seq', 6, true);


--
-- Name: auditoria_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.auditoria_logs_id_seq', 74, true);


--
-- Name: casos_operativos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.casos_operativos_id_seq', 19, true);


--
-- Name: comprobantes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.comprobantes_id_seq', 18, true);


--
-- Name: documentos_identidad_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.documentos_identidad_id_seq', 2, true);


--
-- Name: eventos_interaccion_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.eventos_interaccion_id_seq', 22, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 16, true);


--
-- Name: observaciones_interaccion_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.observaciones_interaccion_id_seq', 1, false);


--
-- Name: otp_verificaciones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.otp_verificaciones_id_seq', 5, true);


--
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.permissions_id_seq', 76, true);


--
-- Name: revisiones_comprobante_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.revisiones_comprobante_id_seq', 1, false);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.roles_id_seq', 27, true);


--
-- Name: saldos_a_favor_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.saldos_a_favor_id_seq', 6, true);


--
-- Name: sesiones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.sesiones_id_seq', 41, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 55, true);


--
-- Name: validaciones_identidad_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.validaciones_identidad_id_seq', 4, true);


--
-- Name: alertas_operativas alertas_operativas_clave_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alertas_operativas
    ADD CONSTRAINT alertas_operativas_clave_unique UNIQUE (clave);


--
-- Name: alertas_operativas alertas_operativas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alertas_operativas
    ADD CONSTRAINT alertas_operativas_pkey PRIMARY KEY (id);


--
-- Name: auditoria_logs auditoria_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria_logs
    ADD CONSTRAINT auditoria_logs_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: casos_operativos casos_operativos_clave_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.casos_operativos
    ADD CONSTRAINT casos_operativos_clave_unique UNIQUE (clave);


--
-- Name: casos_operativos casos_operativos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.casos_operativos
    ADD CONSTRAINT casos_operativos_pkey PRIMARY KEY (id);


--
-- Name: clientes clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_pkey PRIMARY KEY (numero_whatsapp);


--
-- Name: comprobantes comprobantes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comprobantes
    ADD CONSTRAINT comprobantes_pkey PRIMARY KEY (id);


--
-- Name: documentos_identidad documentos_identidad_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documentos_identidad
    ADD CONSTRAINT documentos_identidad_pkey PRIMARY KEY (id);


--
-- Name: eventos_interaccion eventos_interaccion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.eventos_interaccion
    ADD CONSTRAINT eventos_interaccion_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: observaciones_interaccion observaciones_interaccion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.observaciones_interaccion
    ADD CONSTRAINT observaciones_interaccion_pkey PRIMARY KEY (id);


--
-- Name: otp_verificaciones otp_verificaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.otp_verificaciones
    ADD CONSTRAINT otp_verificaciones_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: revisiones_comprobante revisiones_comprobante_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revisiones_comprobante
    ADD CONSTRAINT revisiones_comprobante_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: saldos_a_favor saldos_a_favor_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.saldos_a_favor
    ADD CONSTRAINT saldos_a_favor_pkey PRIMARY KEY (id);


--
-- Name: sesiones sesiones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sesiones
    ADD CONSTRAINT sesiones_pkey PRIMARY KEY (id);


--
-- Name: sesiones sesiones_sesion_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sesiones
    ADD CONSTRAINT sesiones_sesion_id_unique UNIQUE (sesion_id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: validaciones_identidad validaciones_identidad_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.validaciones_identidad
    ADD CONSTRAINT validaciones_identidad_pkey PRIMARY KEY (id);


--
-- Name: alertas_operativas_caso_operativo_id_tipo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX alertas_operativas_caso_operativo_id_tipo_index ON public.alertas_operativas USING btree (caso_operativo_id, tipo);


--
-- Name: alertas_operativas_estado_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX alertas_operativas_estado_index ON public.alertas_operativas USING btree (estado);


--
-- Name: alertas_operativas_nivel_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX alertas_operativas_nivel_index ON public.alertas_operativas USING btree (nivel);


--
-- Name: alertas_operativas_tipo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX alertas_operativas_tipo_index ON public.alertas_operativas USING btree (tipo);


--
-- Name: auditoria_logs_entidad_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX auditoria_logs_entidad_idx ON public.auditoria_logs USING btree (entidad, entidad_id);


--
-- Name: auditoria_logs_fecha_hora_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX auditoria_logs_fecha_hora_idx ON public.auditoria_logs USING btree (fecha_hora);


--
-- Name: auditoria_logs_modulo_accion_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX auditoria_logs_modulo_accion_idx ON public.auditoria_logs USING btree (modulo, accion);


--
-- Name: auditoria_logs_resultado_fecha_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX auditoria_logs_resultado_fecha_idx ON public.auditoria_logs USING btree (resultado, fecha_hora);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: casos_operativos_comprobante_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX casos_operativos_comprobante_id_index ON public.casos_operativos USING btree (comprobante_id);


--
-- Name: casos_operativos_estado_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX casos_operativos_estado_index ON public.casos_operativos USING btree (estado);


--
-- Name: casos_operativos_estado_prioridad_detectado_en_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX casos_operativos_estado_prioridad_detectado_en_index ON public.casos_operativos USING btree (estado, prioridad, detectado_en);


--
-- Name: casos_operativos_otp_verificacion_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX casos_operativos_otp_verificacion_id_index ON public.casos_operativos USING btree (otp_verificacion_id);


--
-- Name: casos_operativos_prioridad_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX casos_operativos_prioridad_index ON public.casos_operativos USING btree (prioridad);


--
-- Name: casos_operativos_saldo_favor_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX casos_operativos_saldo_favor_id_index ON public.casos_operativos USING btree (saldo_favor_id);


--
-- Name: casos_operativos_sesion_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX casos_operativos_sesion_id_index ON public.casos_operativos USING btree (sesion_id);


--
-- Name: casos_operativos_tipo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX casos_operativos_tipo_index ON public.casos_operativos USING btree (tipo);


--
-- Name: casos_operativos_validacion_identidad_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX casos_operativos_validacion_identidad_id_index ON public.casos_operativos USING btree (validacion_identidad_id);


--
-- Name: comprobantes_estado_auditoria_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX comprobantes_estado_auditoria_index ON public.comprobantes USING btree (estado_auditoria);


--
-- Name: comprobantes_sesion_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX comprobantes_sesion_id_index ON public.comprobantes USING btree (sesion_id);


--
-- Name: documentos_identidad_sesion_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX documentos_identidad_sesion_id_index ON public.documentos_identidad USING btree (sesion_id);


--
-- Name: eventos_interaccion_sesion_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX eventos_interaccion_sesion_id_index ON public.eventos_interaccion USING btree (sesion_id);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: observaciones_interaccion_comprobante_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX observaciones_interaccion_comprobante_id_index ON public.observaciones_interaccion USING btree (comprobante_id);


--
-- Name: observaciones_interaccion_sesion_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX observaciones_interaccion_sesion_id_index ON public.observaciones_interaccion USING btree (sesion_id);


--
-- Name: otp_verificaciones_sesion_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX otp_verificaciones_sesion_id_index ON public.otp_verificaciones USING btree (sesion_id);


--
-- Name: revisiones_comprobante_comprobante_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX revisiones_comprobante_comprobante_id_index ON public.revisiones_comprobante USING btree (comprobante_id);


--
-- Name: saldos_a_favor_sesion_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX saldos_a_favor_sesion_id_index ON public.saldos_a_favor USING btree (sesion_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: validaciones_identidad_sesion_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX validaciones_identidad_sesion_id_index ON public.validaciones_identidad USING btree (sesion_id);


--
-- Name: alertas_operativas alertas_operativas_caso_operativo_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alertas_operativas
    ADD CONSTRAINT alertas_operativas_caso_operativo_id_foreign FOREIGN KEY (caso_operativo_id) REFERENCES public.casos_operativos(id) ON DELETE CASCADE;


--
-- Name: auditoria_logs auditoria_logs_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria_logs
    ADD CONSTRAINT auditoria_logs_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.users(id);


--
-- Name: casos_operativos casos_operativos_asignado_a_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.casos_operativos
    ADD CONSTRAINT casos_operativos_asignado_a_foreign FOREIGN KEY (asignado_a) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: casos_operativos casos_operativos_resuelto_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.casos_operativos
    ADD CONSTRAINT casos_operativos_resuelto_por_foreign FOREIGN KEY (resuelto_por) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: comprobantes comprobantes_aprobado_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comprobantes
    ADD CONSTRAINT comprobantes_aprobado_por_foreign FOREIGN KEY (aprobado_por) REFERENCES public.users(id);


--
-- Name: comprobantes comprobantes_deleted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comprobantes
    ADD CONSTRAINT comprobantes_deleted_by_foreign FOREIGN KEY (deleted_by) REFERENCES public.users(id);


--
-- Name: comprobantes comprobantes_rechazado_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comprobantes
    ADD CONSTRAINT comprobantes_rechazado_por_foreign FOREIGN KEY (rechazado_por) REFERENCES public.users(id);


--
-- Name: comprobantes comprobantes_revisado_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comprobantes
    ADD CONSTRAINT comprobantes_revisado_por_foreign FOREIGN KEY (revisado_por) REFERENCES public.users(id);


--
-- Name: comprobantes comprobantes_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comprobantes
    ADD CONSTRAINT comprobantes_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id);


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: observaciones_interaccion observaciones_interaccion_comprobante_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.observaciones_interaccion
    ADD CONSTRAINT observaciones_interaccion_comprobante_id_foreign FOREIGN KEY (comprobante_id) REFERENCES public.comprobantes(id);


--
-- Name: observaciones_interaccion observaciones_interaccion_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.observaciones_interaccion
    ADD CONSTRAINT observaciones_interaccion_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.users(id);


--
-- Name: revisiones_comprobante revisiones_comprobante_comprobante_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revisiones_comprobante
    ADD CONSTRAINT revisiones_comprobante_comprobante_id_foreign FOREIGN KEY (comprobante_id) REFERENCES public.comprobantes(id);


--
-- Name: revisiones_comprobante revisiones_comprobante_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revisiones_comprobante
    ADD CONSTRAINT revisiones_comprobante_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.users(id);


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: users users_actualizado_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_actualizado_por_foreign FOREIGN KEY (actualizado_por) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: users users_creado_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_creado_por_foreign FOREIGN KEY (creado_por) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict nVgQkXb63g4L7Fc8mzq78Vpc8GvK2avYitf3Fg0WwJ8cpnQpixdHCcgXSssMG4n

