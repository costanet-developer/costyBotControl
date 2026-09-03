# Manual operativo del área de Auditoría — CostyBO

**Sistema:** CostyBO, Panel de Auditoría  
**Área responsable:** Auditoría y Control Operativo  
**Versión del manual:** 1.0  
**Fecha de actualización:** 22 de agosto de 2026

## 1. Objetivo

Este manual describe el procedimiento para revisar pagos, comprobantes, interacciones, excepciones y trazabilidad administrativa dentro de CostyBO.

Los objetivos del área de Auditoría son:

- Confirmar que cada pago procesado tenga una evidencia válida y accesible.
- Verificar que los datos del comprobante coincidan con la operación registrada.
- Emitir un dictamen humano independiente del resultado automático del bot.
- Gestionar novedades, duplicados, casos operativos y alertas dentro de los tiempos establecidos.
- Mantener una trazabilidad clara, completa y verificable de cada acción.

## 2. Acceso y seguridad

Para ingresar se necesita una cuenta activa, verificada y con los permisos asignados al cargo.

1. Ingresar a CostyBO con el correo corporativo.
2. Escribir la contraseña personal.
3. Confirmar que el nombre del usuario aparezca en la parte superior del panel.
4. Al terminar la jornada, cerrar la sesión desde el menú del perfil.

Normas obligatorias:

- No compartir usuarios ni contraseñas.
- No dejar una sesión abierta en un equipo desatendido.
- No enviar comprobantes, cédulas o datos bancarios por canales no autorizados.
- Descargar evidencias únicamente cuando sea necesario y se tenga el permiso correspondiente.
- No modificar directamente archivos, CSV o bases de datos.
- Reportar inmediatamente cualquier acceso no reconocido o información expuesta.

CostyBO registra los accesos a imágenes, descargas, cambios de estado y acciones administrativas.

## 3. Módulos principales

### 3.1 Panel principal

El panel presenta los indicadores operativos y accesos rápidos:

- **FCR por opción:** porcentaje de sesiones resueltas automáticamente en el mismo contacto para Reactivación y Saldo a pagar. Las transferencias a atención manual no cuentan como resolución.
- **CES por opción:** promedio de facilidad reportado por el cliente en escala de 1 (muy difícil) a 7 (muy fácil), junto con el porcentaje favorable de respuestas entre 5 y 7. Si no existen encuestas, se muestra “Sin respuestas”.
- Los KPI pueden consultarse para los últimos 7, 30 o 90 días.

Para alimentar CES, el bot debe registrar un evento `encuesta_ces_respondida` en `eventos_interaccion`, con `datos_adicionales` en el formato `{"opcion":"reactivacion|saldo_pagar","puntuacion":1..7}`. El backoffice también reconoce los pasos específicos `ces_reactivacion_respondida` y `ces_saldo_pagar_respondida`.

- Interacciones del día.
- Pagos del día y pagos procesados.
- Comprobantes pendientes de auditoría.
- Pagos sin evidencia.
- Últimos pagos procesados.
- Casos que requieren seguimiento.

Los indicadores sirven para priorizar el trabajo, pero el dictamen debe realizarse desde el detalle de la interacción.

### 3.2 Interacciones

Muestra las conversaciones y sesiones gestionadas por el bot. Permite filtrar por:

- Bot.
- Resultado.
- Intención.
- Estado del pago.
- Estado de auditoría.
- Rango de fechas.
- Cédula, sesión, WhatsApp o cliente.
- Cantidad de filas por página.

Al abrir una interacción se visualizan, según la información disponible:

- Resumen de la sesión.
- Datos del cliente.
- Línea de tiempo de eventos.
- Comprobantes y pagos.
- Validaciones de identidad o KYC.
- OTP y correo enmascarado.
- Créditos o saldos a favor.
- Observaciones e historial de revisión.

### 3.3 Bandeja operativa

Agrupa las excepciones que requieren atención:

- **Casos automáticos:** alertas generadas por reglas de conciliación.
- **Pagos sin evidencia:** reactivaciones exitosas sin comprobante enlazado.
- **Auditoría pendiente:** comprobantes aún no revisados.
- **En revisión:** comprobantes tomados por un auditor.
- **Créditos pendientes:** excedentes registrados para gestión.
- **KYC derivado:** validaciones de identidad enviadas a revisión manual.

### 3.4 Reportes

El módulo de Control operativo permite consultar:

- Pagos procesados.
- Procesados sin evidencia.
- Comprobantes recibidos sin procesar.
- Interacciones sin comprobante.
- Todas las interacciones.

Los filtros disponibles incluyen fechas, bot, banco, estado de auditoría y búsqueda por cédula, WhatsApp, cliente, transacción o documento.

La exportación a Excel depende de los permisos del usuario y respeta los filtros aplicados. Los archivos exportados no deben contener códigos OTP, contraseñas ni tokens.

### 3.5 Resumen gerencial

Presenta indicadores consolidados del periodo seleccionado:

- Interacciones y clientes únicos.
- Pagos procesados y valor recibido.
- Créditos generados.
- Pagos sin evidencia.
- Casos detectados y resueltos.
- Comparación con el periodo anterior.
- Evolución diaria y distribución por banco.

### 3.6 Auditoría

Contiene la trazabilidad administrativa y el seguimiento de los tiempos de atención.

Permite revisar:

- Casos abiertos, próximos a vencer o fuera del SLA.
- Usuario o sistema que realizó una acción.
- Fecha, módulo, acción, entidad y resultado.
- Datos anteriores y posteriores al cambio.
- Dirección IP u otra referencia disponible.

Los límites de SLA para prioridades alta, media y baja se muestran en la propia pantalla y deben revisarse al inicio de cada jornada.

### 3.7 Notificaciones

Muestra alertas relacionadas con casos y eventos operativos. Cada notificación debe abrirse, revisarse y atenderse desde el módulo relacionado. La opción **Marcar todas como leídas** no reemplaza la gestión de los casos pendientes.

## 4. Estados del pago

El estado del pago lo determina el resultado técnico del bot y no equivale al dictamen humano de Auditoría.

| Estado visible | Significado | Acción esperada |
|---|---|---|
| Pago procesado | La operación terminó exitosamente y existe un comprobante relacionado. | Revisar evidencia y datos antes de aprobar. |
| Procesado sin evidencia | La operación terminó exitosamente, pero no existe un comprobante enlazado. | No aprobar; gestionar como incidencia prioritaria. |
| Comprobante recibido | Existe evidencia, pero el pago no consta como procesado. | Revisar eventos, resultado y posibles novedades. |
| Sin comprobante | No existe pago procesado ni evidencia relacionada. | Revisar únicamente si forma parte de una alerta o investigación. |

## 5. Estados de auditoría

El estado de auditoría representa la decisión humana sobre el comprobante.

| Estado | Uso operativo |
|---|---|
| PENDIENTE | El comprobante todavía no ha sido revisado. |
| EN_REVISION | Un auditor tomó el comprobante y está realizando la validación. |
| APROBADO | La evidencia y los datos revisados son consistentes. |
| RECHAZADO | La evidencia presenta una inconsistencia que impide aprobarla. |
| CON_NOVEDAD | Existe una situación que necesita información o análisis adicional. |
| ESCALADO | El caso fue enviado a un nivel superior de revisión. |
| DUPLICADO | El número de transacción o la evidencia ya se encuentra registrada. |
| ANULADO | El registro fue invalidado por un usuario con privilegios especiales. |

El flujo habitual del auditor es:

`PENDIENTE → EN_REVISION → APROBADO o RECHAZADO`

Los estados especiales pueden depender del perfil y de instrucciones del supervisor. No se debe forzar una transición ni usar otro estado para evitar documentar una novedad.

## 6. Procedimiento de revisión de un pago

### Paso 1: tomar el registro

1. Ingresar a **Bandeja operativa**.
2. Abrir **Auditoría pendiente**.
3. Seleccionar el comprobante.
4. Abrir el detalle de la interacción.
5. Presionar **Tomar en revisión**.

Tomar el registro evita que el comprobante permanezca como pendiente mientras ya está siendo analizado.

### Paso 2: confirmar la evidencia

En la sección **Comprobantes y pagos** se debe validar:

- Que la imagen se muestre completa.
- Que el documento sea legible y no esté recortado.
- Que no existan señales evidentes de manipulación.
- Que el banco sea identificable.
- Que el monto sea visible.
- Que la fecha corresponda a la operación.
- Que exista número de transacción, control o documento cuando el banco lo emita.
- Que la cuenta o titular beneficiario corresponda a Costanet.
- Que los datos de origen sean coherentes cuando aparezcan en la evidencia.

La imagen puede ampliarse seleccionándola. La descarga solo debe utilizarse cuando el análisis lo requiera y el usuario tenga autorización.

### Paso 3: comparar los datos registrados

Comparar la imagen con los campos mostrados por CostyBO:

- Banco.
- Valor recibido.
- Fecha del pago.
- Número de transacción o control.
- Número de documento.
- Titular y cuenta de origen.
- Titular y cuenta beneficiaria.
- Indicadores de coincidencia de banco, cuenta y titular.
- Alertas técnicas del OCR o del análisis visual.

Una coincidencia automática ayuda a la revisión, pero no sustituye el criterio del auditor.

### Paso 4: revisar el contexto de la operación

Antes de emitir el dictamen, revisar:

- Estado del pago y resultado de la sesión.
- Eventos de reactivación exitosa.
- Monto esperado o deuda registrada.
- Crédito generado por sobrepago, si existe.
- Historial de la interacción.
- Posibles números de transacción duplicados.
- Observaciones anteriores.

### Paso 5: emitir el dictamen

**Aprobar** cuando:

- La imagen es válida y legible.
- Los datos principales coinciden.
- No existen indicios de duplicidad o alteración.
- El pago y su aplicación son coherentes con la sesión.

**Rechazar** cuando:

- La evidencia no corresponde a un comprobante válido.
- El monto, banco, cuenta o titular presentan una inconsistencia material.
- La imagen está alterada, incompleta o no permite confirmar la operación.
- El número de transacción ya fue utilizado y no existe una explicación válida.
- La operación no puede vincularse de manera confiable con el cliente o la sesión.

Todo rechazo debe incluir una observación concreta. Evitar textos como “incorrecto” o “revisar”. Ejemplo recomendado:

> Se rechaza porque el valor visible en el comprobante es de USD 15,00 y la operación registrada indica USD 20,00. Se requiere validación del área de Pagos.

## 7. Manejo de pagos sin evidencia

Cuando una operación aparezca como **Procesado sin evidencia**:

1. Abrir **Bandeja operativa → Pagos sin evidencia**.
2. Ingresar al detalle de la interacción.
3. Confirmar que no exista ningún comprobante en la sección de pagos.
4. Revisar la línea de tiempo para verificar la reactivación exitosa.
5. Registrar o comunicar la incidencia según el canal interno establecido.
6. Incluir sesión, fecha, número de transacción si existe y descripción del problema.
7. No marcar el comprobante como aprobado ni inventar una evidencia.

Si existe un registro de comprobante pero la imagen muestra un error o no carga, reportar como **evidencia no disponible** e incluir el ID del comprobante.

## 8. Gestión de casos operativos

Los casos automáticos se ordenan por prioridad y fecha de detección.

Procedimiento:

1. Abrir **Bandeja operativa → Casos automáticos**.
2. Priorizar casos de nivel alto y los próximos a vencer.
3. Presionar **Tomar caso**.
4. Abrir la interacción relacionada y realizar la investigación.
5. Escribir una resolución o justificación clara.
6. Seleccionar **Resolver** cuando se aplicó una solución verificable.
7. Seleccionar **Descartar** solamente cuando la alerta no corresponde a una incidencia real, explicando el motivo.
8. Usar **Reabrir caso** si aparece nueva información o si la solución no fue efectiva.

La resolución debe tener entre 5 y 1000 caracteres y debe permitir que otro auditor entienda qué se revisó y cuál fue el resultado.

## 9. Búsqueda y conciliación

Para investigar una operación:

1. Buscar primero por número de transacción o documento.
2. Si no existe resultado, buscar por cédula.
3. Complementar con WhatsApp, nombre del cliente o sesión.
4. Aplicar el rango de fechas del pago.
5. Comparar banco, monto y fecha antes de concluir que dos registros corresponden a la misma operación.

No declarar un duplicado únicamente porque los montos sean iguales. La validación debe considerar como mínimo transacción, fecha, banco, cliente y evidencia.

## 10. Reportes y cierre diario

Al finalizar la jornada:

1. Revisar **Pagos sin evidencia**.
2. Revisar **Auditoría pendiente**.
3. Confirmar que los comprobantes tomados estén resueltos o correctamente documentados en **En revisión**.
4. Revisar casos de prioridad alta y casos fuera de SLA.
5. Consultar el reporte de pagos procesados del día.
6. Verificar el total y valor recibido contra el control interno correspondiente.
7. Exportar el reporte solo si es necesario y está autorizado.
8. Guardar el archivo exportado en la ubicación corporativa aprobada.
9. Eliminar copias locales cuando ya no sean necesarias, conforme a la política de retención.

Lista mínima de cierre:

- [ ] No quedan pagos sin evidencia sin reportar.
- [ ] No quedan comprobantes tomados sin seguimiento.
- [ ] Los rechazos tienen una justificación clara.
- [ ] Los casos de prioridad alta fueron atendidos o escalados.
- [ ] Los reportes coinciden con los filtros y fechas requeridos.
- [ ] La sesión de CostyBO quedó cerrada.

## 11. Buenas prácticas de observación

Una observación debe indicar:

- Qué se encontró.
- Qué dato o evidencia se comparó.
- Qué diferencia existe.
- Qué acción se realizó o se requiere.

Ejemplos:

- “Comprobante legible. Banco, monto de USD 20,00 y transacción 123456 coinciden con el registro. Se aprueba.”
- “La cuenta beneficiaria visible no corresponde a la cuenta autorizada. Se rechaza y se deriva a Pagos.”
- “La imagen no permite leer el número de control. Se mantiene con novedad y se solicita evidencia legible.”
- “Transacción registrada previamente en otro comprobante. Caso escalado para conciliación.”

No incluir contraseñas, tokens, códigos OTP ni información que no sea necesaria para el análisis.

## 12. Escalamiento de incidencias

Escalar al supervisor o al equipo responsable cuando:

- Un pago exitoso no aparece en CostyBO.
- La evidencia no carga o devuelve un error.
- El pago aparece procesado sin comprobante.
- Existen varios clientes asociados a la misma transacción.
- Los totales del reporte no coinciden con la revisión operativa.
- No es posible cambiar el estado por un problema de permisos.
- Se detecta una posible manipulación o exposición de datos.
- El sistema presenta errores repetidos o indisponibilidad.

Información mínima para reportar:

- Fecha y hora.
- ID de sesión.
- ID del comprobante, si existe.
- Número de transacción o documento.
- Módulo donde ocurrió el problema.
- Mensaje de error.
- Descripción de lo que se esperaba y lo que ocurrió.

No enviar la contraseña del usuario ni códigos OTP en el reporte.

## 13. Preguntas frecuentes

### ¿“Pago procesado” significa que Auditoría ya lo aprobó?

No. Significa que el bot registró una operación exitosa. El estado de auditoría se administra por separado.

### ¿Qué hago si el pago está procesado, pero no aparece la foto?

Revisar **Bandeja operativa → Pagos sin evidencia**, confirmar el detalle y reportar la incidencia con la sesión y el comprobante, si existe. No aprobar sin evidencia.

### ¿Puedo aprobar directamente un comprobante pendiente?

El procedimiento correcto es tomarlo primero como **En revisión** y después aprobarlo o rechazarlo.

### ¿Qué hago si dos registros tienen el mismo número de transacción?

Comparar ambas evidencias, fechas, clientes y sesiones. No eliminar registros. Documentar y escalar la posible duplicidad.

### ¿Por qué no veo una opción del menú o un botón?

Las opciones dependen de los permisos asignados. Solicitar revisión del perfil al administrador o supervisor; no utilizar la cuenta de otra persona.

### ¿La descarga de una imagen queda registrada?

Sí. La visualización y descarga controlada de comprobantes forman parte de la trazabilidad del sistema.

## 14. Responsabilidades

**Auditor:** revisar evidencia, documentar hallazgos y emitir el dictamen con objetividad.

**Supervisor de Auditoría:** distribuir carga, controlar SLA, revisar escalados y validar cierres.

**Área de Pagos o Conciliación:** resolver diferencias financieras, duplicidades y operaciones no identificadas.

**Administrador de CostyBO:** gestionar usuarios, permisos y configuración autorizada.

**Soporte técnico:** atender fallos de registro, imágenes, integraciones o disponibilidad del sistema.

---

Este manual debe actualizarse cuando cambien los estados, permisos, pantallas o procedimientos del Panel de Auditoría.
