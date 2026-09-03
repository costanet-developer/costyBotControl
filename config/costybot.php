<?php

return [
    'sla_casos_horas' => [
        'alta' => (int) env('COSTY_SLA_ALTA_HORAS', 2),
        'media' => (int) env('COSTY_SLA_MEDIA_HORAS', 8),
        'baja' => (int) env('COSTY_SLA_BAJA_HORAS', 24),
    ],
    'alertas' => [
        'porcentaje_aviso' => (int) env('COSTY_ALERTA_PORCENTAJE_SLA', 80),
        'escalar_despues_horas' => (int) env('COSTY_ALERTA_ESCALAR_HORAS', 24),
        'email_habilitado' => filter_var(env('COSTY_ALERTAS_EMAIL', false), FILTER_VALIDATE_BOOL),
    ],
];
