# Cierre operativo de costyBot-control

Fecha de referencia: 21 de agosto de 2026.

## Objetivo

Establecer quién puede consultar y operar la información generada por CostyBot, cómo deben revisarse las excepciones y qué debe comprobar cada área antes de aceptar el sistema.

## Matriz de acceso

| Capacidad | Contabilidad | Administrador | Superadministrador |
|---|---:|---:|---:|
| Ver interacciones y recorrido | Sí | Sí | Sí |
| Ver comprobantes | Sí | Sí | Sí |
| Descargar comprobantes | No | Sí | Sí |
| Revisar, aprobar o rechazar comprobantes | Sí | Sí | Sí |
| Ver documentos de identidad | Sí | Sí | Sí |
| Descargar documentos de identidad | No | Sí | Sí |
| Ver y exportar reportes | Sí | Sí | Sí |
| Administrar usuarios | No | Sí, con restricciones | Sí |
| Administrar roles y configuración | No | No | Sí |

Toda visualización o descarga de documentos de identidad y comprobantes debe quedar registrada en `auditoria_logs`.

## Bandeja operativa

La ruta `/pendientes` concentra estas categorías:

1. Pagos sin evidencia: la sesión registra una reactivación exitosa, pero no existe un comprobante enlazado.
2. Auditoría pendiente: comprobantes que todavía no han sido revisados por una persona.
3. En revisión: comprobantes tomados por un auditor y aún no resueltos.
4. Créditos pendientes: excedentes registrados después de cubrir el valor de la factura.
5. KYC derivado: validaciones de identidad que requieren revisión manual.

Los cinco casos históricos sin evidencia no deben corregirse automáticamente. El responsable debe contrastarlos con la fuente bancaria o el sistema comercial antes de registrar cualquier decisión.

## Procedimiento de revisión

### Comprobante

1. Abrir la interacción desde la bandeja.
2. Confirmar imagen, banco, valor, fecha, Control/transacción y Documento.
3. Comparar titular/cuenta de origen y cuenta beneficiaria cuando sean visibles.
4. Revisar deuda, factura, servicio reactivado y crédito generado.
5. Tomar el comprobante en revisión.
6. Aprobar o rechazar. Todo rechazo debe incluir una observación concreta.

### Crédito

1. Confirmar monto pagado y monto de factura.
2. Verificar que `excedente = monto pagado - monto factura`.
3. Comparar la transacción con el comprobante principal.
4. Gestionar el crédito en el sistema comercial conforme al procedimiento contable vigente.

### Identidad y OTP

1. Confirmar anverso y reverso, cédula detectada y coincidencia con el titular.
2. Revisar nombres, apellidos, sexo, estado civil, código dactilar y emisor del documento.
3. Confirmar únicamente el resultado del OTP. Nunca solicitar, copiar o registrar el código enviado.
4. Las imágenes no deben descargarse fuera de los roles autorizados.

## Pruebas de aceptación

### Gerencia general

- [ ] El tablero muestra interacciones, pagos, créditos y excepciones.
- [ ] Los totales del reporte coinciden con los filtros aplicados.
- [ ] Los casos sin evidencia son visibles y diferenciados.

### Gerencia de operaciones y jefaturas

- [ ] Se puede localizar una sesión por cédula, WhatsApp o transacción.
- [ ] El recorrido conserva el orden cronológico.
- [ ] Los servicios y el servicio seleccionado son legibles.
- [ ] La bandeja permite abrir cada caso pendiente.

### Contabilidad

- [ ] Puede visualizar comprobantes y revisar su información completa.
- [ ] Puede tomar, aprobar o rechazar un comprobante según las transiciones permitidas.
- [ ] Puede consultar y exportar reportes filtrados.
- [ ] El Excel incluye Control, Documento, origen, destino, crédito, KYC y resultado OTP.
- [ ] No puede descargar comprobantes ni documentos de identidad.
- [ ] Ningún código OTP aparece en pantalla o exportación.

### Sistemas

- [ ] n8n permanece activo y conserva sus webhooks.
- [ ] Las imágenes sólo se sirven desde las carpetas autorizadas.
- [ ] Los accesos a evidencias quedan registrados en auditoría.
- [ ] La suite automatizada y la compilación visual finalizan correctamente.
- [ ] Existe respaldo reciente de aplicación, PostgreSQL y workflow n8n.

## Criterio de cierre

La fase se considera aceptada cuando cada área completa su sección, los cinco casos históricos tienen un responsable asignado y no existen fallos de permisos, exposición de OTP o pérdida de trazabilidad.
