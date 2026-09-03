<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE encuestas_ces DROP CONSTRAINT IF EXISTS encuestas_ces_sesion_id_key');
        DB::statement('ALTER TABLE encuestas_ces ADD CONSTRAINT encuestas_ces_sesion_tipo_key UNIQUE (sesion_id, tipo_gestion)');

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION public.sp_programar_encuesta_ces(
    p_sesion_id character varying,
    p_numero_whatsapp character varying,
    p_tipo_gestion character varying,
    p_cerrar_sesion boolean DEFAULT false
)
RETURNS TABLE(registrada boolean, sesion_id character varying)
LANGUAGE plpgsql
AS $function$
#variable_conflict use_column
DECLARE
    v_insertada VARCHAR(80);
    v_numero VARCHAR(20);
BEGIN
    IF p_tipo_gestion NOT IN ('reactivacion', 'consulta_valores') THEN
        RAISE EXCEPTION 'Tipo de gestión CES no válido: %', p_tipo_gestion;
    END IF;

    v_numero := regexp_replace(COALESCE(p_numero_whatsapp, ''), '[^0-9]', '', 'g');
    IF length(v_numero) < 10 OR length(v_numero) > 15 THEN
        RAISE EXCEPTION 'Número de WhatsApp CES no válido';
    END IF;

    IF p_cerrar_sesion THEN
        UPDATE sesiones s
           SET estado_sesion = 'cerrada',
               intencion = CASE WHEN p_tipo_gestion = 'consulta_valores' THEN 'consultar' ELSE s.intencion END,
               resultado = CASE WHEN p_tipo_gestion = 'consulta_valores' THEN 'consulta_exitosa' ELSE s.resultado END,
               fin = COALESCE(s.fin, NOW())
         WHERE s.sesion_id = p_sesion_id;

        IF NOT FOUND THEN
            RAISE EXCEPTION 'No existe la sesión %', p_sesion_id;
        END IF;
    END IF;

    INSERT INTO encuestas_ces (
        sesion_id, numero_whatsapp, tipo_gestion, estado, programada_para
    ) VALUES (
        p_sesion_id, v_numero, p_tipo_gestion, 'pendiente', NOW()
    )
    ON CONFLICT (sesion_id, tipo_gestion) DO NOTHING
    RETURNING encuestas_ces.sesion_id INTO v_insertada;

    IF v_insertada IS NOT NULL THEN
        INSERT INTO eventos_interaccion (
            sesion_id, numero_whatsapp, paso, fecha_evento, datos_adicionales
        ) VALUES (
            p_sesion_id, v_numero, 'ces_programada', NOW(),
            jsonb_build_object('tipo_gestion', p_tipo_gestion, 'demora_minutos', 0)
        );
    END IF;

    RETURN QUERY SELECT v_insertada IS NOT NULL, p_sesion_id;
END;
$function$;
SQL);

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION public.sp_marcar_encuesta_ces_enviada(
    p_id bigint,
    p_whatsapp_message_id character varying
)
RETURNS TABLE(sesion_id character varying)
LANGUAGE plpgsql
AS $function$
#variable_conflict use_column
BEGIN
    RETURN QUERY
    WITH actualizada AS (
        UPDATE encuestas_ces e
           SET estado = 'enviada',
               enviada_en = NOW(),
               actualizado_en = NOW(),
               whatsapp_message_id = p_whatsapp_message_id,
               error_envio = NULL
         WHERE e.id = p_id AND e.estado = 'enviando'
        RETURNING e.sesion_id, e.numero_whatsapp, e.tipo_gestion
    ), evento AS (
        INSERT INTO eventos_interaccion (
            sesion_id, numero_whatsapp, paso, fecha_evento, datos_adicionales
        )
        SELECT a.sesion_id, a.numero_whatsapp, 'ces_enviada', NOW(),
               jsonb_build_object(
                   'whatsapp_message_id', p_whatsapp_message_id,
                   'tipo_gestion', a.tipo_gestion
               )
          FROM actualizada a
    )
    SELECT a.sesion_id FROM actualizada a;
END;
$function$;
SQL);

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION public.sp_registrar_respuesta_ces(
    p_numero_whatsapp character varying,
    p_puntuacion smallint,
    p_wa_respuesta_id character varying
)
RETURNS TABLE(registrada boolean, puntuacion smallint)
LANGUAGE plpgsql
AS $function$
#variable_conflict use_column
BEGIN
    IF p_puntuacion NOT BETWEEN 1 AND 7 THEN
        RAISE EXCEPTION 'Puntuación CES fuera de rango: %', p_puntuacion;
    END IF;

    RETURN QUERY
    WITH candidata AS (
        SELECT e.id
          FROM encuestas_ces e
         WHERE e.numero_whatsapp = regexp_replace(COALESCE(p_numero_whatsapp, ''), '[^0-9]', '', 'g')
           AND e.estado = 'enviada'
           AND e.enviada_en >= NOW() - INTERVAL '48 hours'
         ORDER BY e.enviada_en DESC
         LIMIT 1
         FOR UPDATE SKIP LOCKED
    ), actualizada AS (
        UPDATE encuestas_ces e
           SET estado = 'respondida',
               puntuacion = p_puntuacion,
               respondida_en = NOW(),
               wa_respuesta_id = p_wa_respuesta_id,
               actualizado_en = NOW()
          FROM candidata c
         WHERE e.id = c.id
        RETURNING e.sesion_id, e.numero_whatsapp, e.puntuacion, e.tipo_gestion
    ), evento AS (
        INSERT INTO eventos_interaccion (
            sesion_id, numero_whatsapp, paso, fecha_evento, datos_adicionales
        )
        SELECT a.sesion_id, a.numero_whatsapp, 'ces_respondida', NOW(),
               jsonb_build_object(
                   'puntuacion', a.puntuacion,
                   'wa_respuesta_id', p_wa_respuesta_id,
                   'tipo_gestion', a.tipo_gestion,
                   'opcion', CASE
                       WHEN a.tipo_gestion = 'consulta_valores' THEN 'saldo_pagar'
                       ELSE 'reactivacion'
                   END
               )
          FROM actualizada a
    )
    SELECT EXISTS (SELECT 1 FROM actualizada),
           COALESCE((SELECT a.puntuacion FROM actualizada a LIMIT 1), p_puntuacion);
END;
$function$;
SQL);
    }

    public function down(): void
    {
        // No se revierte automáticamente: estas funciones son compartidas con n8n.
    }
};
