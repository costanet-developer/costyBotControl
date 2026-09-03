# Fase de auditoría central y seguimiento SLA

Fecha de activación: 21 de agosto de 2026.

## Funcionalidad

La ruta `/auditoria` concentra el historial administrativo y el seguimiento de casos operativos.

- Métricas de casos abiertos, sin asignar, vencidos y tiempos promedio.
- SLA configurable en `config/costybot.php`: alta 2 horas, media 8 horas y baja 24 horas.
- Lista de casos críticos ordenada por vencimiento, con acceso directo a la bandeja.
- Indicadores de atención por responsable.
- Historial filtrable por fecha, usuario, módulo, acción, resultado y búsqueda textual.
- Acceso directo desde eventos relacionados hacia casos, interacciones o usuarios.
- Exportación a Excel de hasta 50.000 eventos respetando los filtros.

## Seguridad

Los permisos `auditoria.ver` y `auditoria.exportar` están reservados a `superadministrador` y `administrador`. Contabilidad conserva la gestión de casos, pero no accede al historial administrativo global.

La pantalla y el Excel ocultan contraseñas, tokens, secretos, códigos OTP, base64 y contenido binario. Los registros no disponen de rutas de edición o eliminación y el modelo bloquea ambas operaciones.

Cada exportación genera un nuevo evento `exportar_auditoria` con usuario, filtros, cantidad exportada, IP y navegador.

## Datos al activar

- Eventos de auditoría: 3.176.
- Casos abiertos: 11.
- Casos sin asignar: 11.
- Casos fuera de SLA: 5.
- Respaldo previo: `storage/backups/costy_sesiones_20260821_132559.sql`.

## Validación

- 66 pruebas automatizadas.
- 238 verificaciones aprobadas.
- Migración `2026_08_21_000005_add_audit_center_support` aplicada.
- n8n y `costybot-scheduler.timer` permanecieron activos.
