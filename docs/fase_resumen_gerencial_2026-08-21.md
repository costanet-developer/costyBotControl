# Fase: resumen gerencial

Fecha de cierre: 21 de agosto de 2026.

## Alcance implementado

- Nueva vista `/resumen-gerencial`, disponible para usuarios con `auditoria.ver`.
- Periodos: hoy, ayer, últimos 7 días, últimos 30 días y personalizado (máximo 366 días).
- Comparación automática contra el periodo anterior de igual duración.
- Indicadores de interacciones, clientes únicos, pagos, valor recibido, créditos, pagos sin evidencia, KYC, correos verificados, casos y acciones administrativas.
- Evolución diaria, valores por banco, casos por tipo, SLA vigente y desempeño por responsable.
- Exportación Excel protegida por `auditoria.exportar` y registrada en auditoría.
- Selección única del comprobante asociado a cada pago y deduplicación de comprobantes compartidos.
- Agrupamiento por fecha operativa original para evitar desplazamientos por zona horaria.

## Automatización

- Resumen diario: 07:30, correspondiente al día anterior.
- Resumen semanal: lunes 07:45, correspondiente a los siete días anteriores.
- Zona horaria: `America/Guayaquil`.
- El envío externo permanece deshabilitado mientras `COSTY_ALERTAS_EMAIL=false`. En ese estado el comando termina correctamente, no crea auditoría de envío y no entrega notificaciones.

## Validación

- Suite completa: 78 pruebas, 294 verificaciones.
- Pruebas específicas: 6 pruebas, 31 verificaciones.
- Conciliación real de siete días al cierre:
  - 331 pagos en total y 331 en el desglose diario.
  - $7.258,57 tanto en el total como en la suma diaria.
  - 5 pagos procesados sin evidencia enlazada.
- Respaldo previo: `storage/backups/costy_sesiones_20260821_144504.sql` (2,69 MB).

## Seguridad y límites

- No se modificó el workflow de n8n ni sus datos.
- No se habilitó SMTP ni se enviaron correos externos.
- La exportación deja trazabilidad central.
- Los periodos personalizados mayores a 366 días se rechazan para proteger rendimiento.
