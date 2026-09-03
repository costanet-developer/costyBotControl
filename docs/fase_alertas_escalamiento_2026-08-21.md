# Fase de alertas y escalamiento automático

Fecha de activación: 21 de agosto de 2026.

## Reglas

- `nuevo_alta`: aviso inmediato para un caso nuevo de prioridad alta.
- `por_vencer`: aviso al consumir el 80 % del tiempo de SLA.
- `sla_vencido`: escalamiento al alcanzar el límite de atención.
- `escalado`: escalamiento crítico cuando pasan 24 horas adicionales.

Cada alerta utiliza una clave única formada por caso y etapa. Las ejecuciones repetidas actualizan el seguimiento sin duplicar alertas ni notificaciones.

## Destinatarios

- Caso asignado: recibe la alerta el responsable.
- Caso sin asignar: reciben la alerta los usuarios activos de Contabilidad.
- SLA vencido o escalado: también reciben el aviso Administración y Superadministración.

## Centro interno

Todos los usuarios autenticados disponen de una campana con contador y la ruta `/notificaciones`. Cada usuario solo puede consultar y marcar sus propios avisos. Al abrir un aviso se marca como leído y, si tiene permiso, se dirige al caso relacionado.

## Correo

El canal está implementado pero permanece deshabilitado mediante `COSTY_ALERTAS_EMAIL=false`. El mailer actual del backoffice es `log`; por ello no se enviaron mensajes externos en la activación. La habilitación requiere configurar SMTP y hacer una prueba controlada con destinatarios internos.

## Programación

El comando `costy:procesar-alertas-operativas` se ejecuta cada cinco minutos mediante el timer existente, después de incorporar las detecciones más recientes. Usa bloqueo para impedir ejecuciones simultáneas.

## Activación inicial

- Alertas creadas: 5.
- Tipo: SLA vencido.
- Notificaciones internas: 30, cinco para cada uno de los seis usuarios responsables.
- Correos enviados: 0.
- Errores: 0.
- Segunda ejecución: 0 alertas y 0 notificaciones nuevas.
- Respaldo: `storage/backups/costy_sesiones_20260821_140023.sql`.

## Validación

- 72 pruebas automatizadas.
- 263 verificaciones aprobadas.
- n8n y `costybot-scheduler.timer` permanecieron activos.
