# Fase de conciliación y alertas operativas

Fecha de activación: 21 de agosto de 2026.

## Alcance

CostyBot Control detecta y conserva casos operativos sin modificar las tablas administradas por n8n. La detección se ejecuta cada cinco minutos y utiliza una clave única por anomalía para evitar duplicar casos.

Reglas implementadas:

- Pago procesado sin comprobante relacionado (prioridad alta).
- Número de transacción repetido, normalizado sin espacios, puntos, guiones ni ceros iniciales (alta).
- Crédito cuyo excedente no coincide con pago menos factura (alta).
- Monto no conciliado que no terminó en una reactivación exitosa (media).
- Validación KYC derivada a revisión manual (alta).
- OTP que agotó sus intentos sin validarse (media).
- Sesión activa reciente, sin resultado y sin actividad final durante más de 30 minutos (baja). Solo se consideran sesiones iniciadas en las últimas 24 horas para no contaminar la bandeja con historia antigua.

Los detalles de OTP nunca almacenan el código enviado ni el ingresado.

## Gestión

La pestaña `Pendientes > Casos automáticos` permite filtrar abiertos, pendientes, en revisión, resueltos, descartados o todos. Los usuarios autorizados pueden tomar, resolver, descartar y reabrir un caso. Resolver o descartar exige una justificación.

Cada transición registra usuario, fecha, IP, agente del navegador, estado anterior y estado nuevo en `auditoria_logs`. Los estados resueltos o descartados no se reabren automáticamente si la misma anomalía vuelve a detectarse; solo se actualiza su última detección.

Roles con gestión: `superadministrador`, `administrador` y `contabilidad`.

## Operación técnica

- Comando: `php artisan costy:detectar-casos-operativos`
- Frecuencia Laravel: cada cinco minutos, sin solapamiento.
- Ejecutor: `costybot-scheduler.timer`, activo cada minuto para evaluar la programación de Laravel.
- Tabla propia: `casos_operativos`.
- Respaldo previo: `storage/backups/costy_sesiones_20260821_083528.sql`.

Primera detección: 11 casos; 5 pagos sin evidencia y 6 sesiones recientes estancadas.
