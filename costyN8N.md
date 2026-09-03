# costyN8N — Documentación del flujo "CostyBot Whatsapp - Reactivación"

> Documento de trabajo para revisar y arreglar el código entre **n8n**, **PostgreSQL** y el **panel de control** (Laravel).
> Última revisión del workflow: 2026-08-04 (workflow de PRODUCCIÓN, 159 nodos, versión publicada `97fc223c-b43a-4c01-a039-1bef6911bd11`).

---

## 0. CHANGELOG DE ARREGLOS (2026-08-04)

### 0.4 BUG CRÍTICO — `textbody=undefined` en `IA_ClasificarIntencion` — CORREGIDO Y ACTIVO (versión `fa35e0c1-...` → `97fc223c-...`)
- **Síntoma**: la IA respondía "Mensaje no reconocido" y cerraba la sesión sin reaccionar a comprobantes ni cédulas (ejecución 62160: el JSON que recibió `IA_ClasificarIntencion` era solo `{"sesion_id":"...","sesion_es_nueva":false}` → `{{ $json.textbody }}` resolvía `undefined`).
- **Causa raíz (introducida por la sección 0.1)**: al insertar `DB_Leer_SesionActiva` entre `LeerEstadoConversacion` y `Sesion_GestionarID`, el nodo Postgres reemplaza el payload con `{sesion_id}`. El `Sesion_GestionarID` original hacía `...$json` (solo `{sesion_id, sesion_es_nueva}`) → se perdían `from`, `textbody`, `media_id`, `cedula`, `tipo_comprobante`, etc. en TODA la cadena aguas abajo.
- **Fix**: `Sesion_GestionarID` ahora re-mergea todo el contexto: `{...contexto, ...$json, from, sesion_id, sesion_es_nueva}` con `contexto = $('LeerEstadoConversacion').item.json`. Publicado como `fa35e0c1-...` (primera corrección), luego consolidado en `97fc223c-...`.
- **Verificado con mensaje real**: ejecución 62233 (593969904000, imagen con cédula 0941388142) → OCR completo (Banco Guayaquil, $20.00, trans 0009043097), comprobante 1183 insertado, sesión 2182 con `comprobante_id=1183`, `resultado=reactivado`, factura 238357 marcada pagada.

### 0.4b FIXES QA ADICIONALES — ACTIVOS (versión `97fc223c-...`)
- `IF_DeudaCero` usaba `Number($json.deuda_total)` pero `GetInvoices` NUNCA devuelve `deuda_total` (respuestas reales: `{estado, facturas:[...]}`) → `NaN ≠ 0` → la rama "cuenta al día" era código muerto y todo cliente con factura pagada recibía "monto no coincide". Ahora la deuda se calcula desde `$json.facturas` (suma de `total` de facturas `no pagado`/`vencido`).
- `DB_CSV_RegistrarTransaccion4` desconectado de la salida "mensaje no reconocido" del `Switch_IntencionIA` (fallaba con FK `comprobantes_numero_whatsapp_fkey` por `numero_whatsapp=''`; solo queda `CSV_RegistrarTransaccion4` en esa rama).

### 0.1 Sesiones duplicadas — PUBLICADO Y ACTIVO (versión `dde027cc-56e3-4b6b-b82c-99d2f94b1dbe`)
- **IMPORTANTE (mecanismo descubierto)**: n8n ejecuta la versión **PUBLICADA** (`workflow_history.activeVersionId`), NO el draft de `workflow_entity`. Un primer intento de fix (solo en el draft, `e06f8645-...`) **nunca se activó**; los bugs siguieron vivos hasta publicar la versión correcta.
- Nuevo nodo **`DB_Leer_SesionActiva`** (Postgres, `alwaysOutputData`, `onError=continueRegularOutput`):
  `SELECT s.sesion_id FROM sesiones s WHERE s.numero_whatsapp = $1 AND s.estado_sesion='activa' ORDER BY s.sesion_id DESC LIMIT 1`
- Inserción en el flujo: `LeerEstadoConversacion → DB_Leer_SesionActiva → Sesion_GestionarID`
- `Sesion_GestionarID` ahora reusa: `conv.sesion_id || $json.sesion_id` (staticData → BD activa → nuevo `${from}_${Date.now()}`)
- Backups: `backups/n8n/CostyBot_Whatsapp_Reactivacion_PROD_20260804.json` (export), `backups/n8n/database.sqlite.pre-fix` y `backups/n8n/database.sqlite.pre-publish-fix` (DB completa, `.backup` con WAL)
- Nota: queda una ventana residual mínima (~ms) si dos ejecuciones concurrentes leen la BD antes de que la primera haga upsert; Meta reintenta con segundos de retraso, por lo que en la práctica queda cubierto.

### 0.2 Zona horaria — PUBLICADO Y ACTIVO (versión `dde027cc-...`)
- Los 17 nodos `DB_Log_*` (fecha_evento), `DB_Upsert_Cliente` (ultima_interaccion) y los 4 `DB_Cerrar_Sesion_*` (fin) dejaron de usar `setZone('America/Guayaquil')`; ahora escriben `$now.toFormat('yyyy-MM-dd HH:mm:ss')` (UTC).
- Los `Log_*` (JSONL), `CSV_*` y `GenerarNombreArchivoImagen` conservan hora local a propósito (solo uso manual/archivos, no van a la BD del panel).

### 0.2b BUG CRÍTICO CORREGIDO — `DB_Guardar_ComprobanteEnSesion` fallaba (comprobante nunca enlazado)
- El 2026-08-04 el usuario reeditó el nodo en la UI y n8n agregó columnas basura: `"id": 0`, `"intentos_comprobante": 0`, `"menu_generado_en": 0` (además de los cambios intencionales: guía real `GuiaComprobante_Preparar` leyendo `/home/Tlsg_n8n/imagenes/guia_reactivacion.png` y sticker nuevo).
- El UPDATE generado incluía `SET id=0` → chocaba con una sesión histórica que quedó con `id=0` (primera víctima del bug, `593969904000_..._19923`, del 02/08) → error `duplicate key value violates unique constraint "sesiones_pkey" Key (id)=(0)` → **`comprobante_id` nunca se escribía** en rutas de cliente único.
- Corrección aplicada y publicada: `columns.value` quedó solo con `sesion_id`, `comprobante_id`, `ultima_actividad` (igual a la config que funcionaba el 08-03, cuando 72/72 sesiones quedaron enlazadas).
- La sesión con `id=0` fue reenumerada (`id=2182`) y cerrada (`estado_sesion='cerrada'`); sus 4 FKs apuntan a `sesion_id`, no a `id` (verificado).
- Evidencia: ejecución 62079 (593969449270, comp 1171, reactivado SIN comprobante_id), ejecución 61933 (593991994533, comp 1166 sin enlazar).

### 0.5 INCIDENTE 2026-08-06 — PRODUCCIÓN DEJÓ DE RESPONDER (webhook des-registrado) — RESUELTO
- **Síntoma**: al ejecutar el workflow de Desarrollo desde la UI, producción (verde/publicado) dejó de responder a los usuarios.
- **Causa raíz**: Meta permite **UNA sola suscripción de webhook por App de WhatsApp**. El workflow "CostyBot (Desarrollo)" (`BfwEauoRPJYzYI1P`) usaba la MISMA credencial (App ID 1508323143457783) que producción. Al ejecutar/activar el trigger de Desarrollo, n8n intentó registrar una segunda suscripción → Meta la rechazó (`The WhatsApp App ID ... already has a webhook subscription`) y el webhook de producción (`25df2562-.../webhook`) quedó sin registrarse en memoria → todas las peticiones de Meta daban `404 not registered` (verificado en journal: 10:51-10:54).
- **Fix aplicado**: backup `backups/n8n/database.sqlite.pre-webhook-fix` → n8n DETENIDO → `DELETE FROM webhook_entity` (se limpiaron también los webhooks residuales de OLLAMA-PRUEBAS `2c7963a2`) → arrancar n8n → al activar el workflow, n8n re-registró el webhook de producción desde cero (mismo path `25df2562-.../webhook`, GET y POST) y re-suscribió en Meta.
- **Verificación**: `GET https://n8n.telearseg.net/webhook/25df2562-.../webhook` → HTTP 200 (antes 404); activación limpia sin errores de suscripción; solo los webhooks de producción quedan en `webhook_entity`.
- **REGLAS PARA EL USUARIO (evitar que se repita)**:
  1. NO ejecutar/activar el trigger de WhatsApp de OTRO workflow que use la misma credencial mientras producción esté activa (Meta: 1 webhook por App).
  2. El workflow de Desarrollo no debe tener trigger de WhatsApp con esa credencial; debe ser solo un flujo secundario llamado por `Ejecutar_Dev` (`executeWorkflow`, sin suscripción en Meta) — como quedó diseñado en el draft `c8bbea1c` (`Trigger → IF_EsUsuarioDev → Ejecutar_Dev/ExtraerDatosMensaje`).
  3. Si se activa manualmente otro workflow de WhatsApp con la misma App, desactivarlo ANTES de tocar producción.


- Eliminadas **14 sesiones duplicadas** (8 fantasma `reactivado` sin comprobante + hermanas `activa`/`no_reconocido`/`cerrada_sin_comprobante` del mismo instante).
- Movidos **7 comprobantes reales** (964, 1079, 682, 1090, 469, 403, 389) a la sesión `reactivado` correcta; `comprobantes.sesion_id` reasignado (FK `comprobantes_sesion_id_fkey`).
- Cerrada `593986912798_..._772777` (reactivado que quedó activa) con fin = fecha del comprobante.
- `fin` corregido (+5h local→UTC) en las 8 sesiones conservadas. 4 eventos huérfanos eliminados.
- Resultado: **0** sesiones `reactivado` sin comprobante; **93** reactivaciones con comprobante (eran 86 + 7 recuperadas).
- El resto del histórico (fins/eventos viejos en hora local) se dejó SIN corregir por decisión del usuario.

---

## 1. Visión general del sistema

```
Cliente (WhatsApp)
   │ mensaje / imagen / botón
   ▼
[n8n] CostyBot Whatsapp - Reactivación   ← único workflow de producción
   │
   ├──► API Costanet/Mikrowisp ($env.API_COSTANET)  → GetInvoices, PaidInvoice, ActiveService, GetClientsDetails
   ├──► Meta Graph API (WhatsApp)  → subir media, enviar mensajes/stickers/guía
   ├──► OCR por IA (OpenAI, nodo langchain)
   ├──► Archivos CSV (/home/Tlsg_n8n/*.csv)  → registro manual de transacciones
   ├──► Imágenes (/home/Tlsg_n8n/whatsapp_imagenes*)
   └──► [PostgreSQL] costy_sesiones  → tablas del panel (fuente de verdad para el aplicativo)
                │
                ▼
        [Panel Laravel] costyBotControl  → Interacciones, Reportes, Auditoría (solo LECTURA)
```

- El **n8n** escribe en PostgreSQL y el **panel** solo lee (nunca escribe).
- El panel usa `resultado = 'reactivado'` de la tabla `sesiones` para el reporte de Pagos y Reactivaciones.
- Todo el estado de conversación vive en `staticData.conversaciones[from]` (memoria del workflow n8n) + respaldo en PostgreSQL.

---

## 2. Tablas de PostgreSQL y quién las escribe

| Tabla | Nodos que escriben | Uso en el panel |
|---|---|---|
| `sesiones` | `DB_Upsert_Sesion` (crea/actualiza), `DB_Guardar_ComprobanteEnSesion` (comprobante_id), `DB_Marcar_MultiplesServicios`, `DB_Marcar_ServicioSeleccionado`, `DB_Cerrar_Sesion_*` (4 nodos de cierre) | Listado de interacciones, reporte de reactivaciones (resultado, comprobante_id) |
| `comprobantes` | `DB_CSV_RegistrarTransaccion1/3/4` (insert con origen `ocr_automatico` / manual) | Detalle de interacción, reporte (monto, banco, número) |
| `eventos_interaccion` | `DB_Log_*` (14 nodos: menu_mostrado, cedula_valida/invalida, comprobante_recibido, unico/duplicado, ocr_legible/ilegible, monto_coincide/no_coincide, reactivacion_exitosa, etc.) | Línea de tiempo de la interacción |
| `clientes` | `DB_Upsert_Cliente` (upsert por numero_whatsapp) | Nombre del cliente en el listado |
| `auditoria_logs` | `DB_Log_Auditoria_PagoFactura`, `DB_Log_Auditoria_ActivarServicio` | (histórico de acciones sobre la API Costanet) |
| `saldos_a_favor` | `DB_Insertar_SaldoAFavor` (sobrepago → saldo a favor) | (no usado aún por el panel) |
| `documentos_identidad` | — (sin escritor detectado) | (el panel lo carga en el detalle) |

---

## 3. Flujo paso a paso (rama principal)

### 3.1 Entrada — `Trigger_WhatsApp`
- Webhook de Meta (WhatsApp Cloud API) para el número de negocio del bot.
- Cada evento (mensaje de texto, imagen, botón, lista, status) dispara **una ejecución**.

### 3.2 `ExtraerDatosMensaje` (Code)
Extrae del payload de Meta:
- `wa_message_id` (clave de deduplicación), `media_id`, `type`, `from`, `name`
- `cedula`: de la **caption de la imagen** o de un **texto de 10/13 dígitos**
- `tipo_comprobante` / `button_id` / `button_title`: de mensajes `interactive` (button_reply / list_reply)
- `context_from`, `context_msg_id`, `timestamp_whatsapp`
- Si el evento es de `statuses` (entregado/leído), genera un objeto de status.

### 3.3 `Filtrar_MensajesDuplicados` (Code) — DEDUPLICACIÓN
Protege contra:
- **Reintentos de Meta** (reenvía el webhook si no hubo 200 OK): TTL 10 min por `wa_message_id`
- **Doble-tap** de botón (llega con wa_message_id DISTINTO): TTL 8 s por `from::button_id`

> ⚠️ **ADVERTENCIA**: esta deduplicación vive en `staticData` (RAM del n8n, sin lock).
> Dos ejecuciones CONCURRENTES pueden leer el estado "no procesado" a la vez y pasar ambas
> → es la misma condición de carrera que el propio código documenta para los CSV
> (ver comentario en `Validar Duplicados`). Ver sección 7 (bugs).

### 3.4 Rutas de entrada
- `IF_NoEsDuplicado` (FALSE → se descarta)
- `IF_EsSeleccionServicio` → TRUE si el mensaje es el clic de la lista de servicios (`button_id` empieza con `serv_`) → rama multi-servicio (3.8)
- `IF_EsMensajeReal` → separa mensajes de `statuses` (se ignoran en `NoOp_IgnorarStatus`)
- `IF_TieneImagen` → TRUE si `type = image`

### 3.5 Sesión — `LeerEstadoConversacion` → `Sesion_GestionarID` → `DB_Upsert_*`
- `LeerEstadoConversacion`: limpia conversaciones inactivas (>24 h), expone `estado_previo`, `cedula_guardada`, `intencion_guardada`, `nombre_guardado`.
- **`Sesion_GestionarID`** (Code): asigna el ID de sesión:
  ```js
  sesionId = conv.sesion_id || `${from}_${Date.now()}`;
  ```
  Si `staticData.conversaciones[from].sesion_id` ya existe, lo REUSA.
  ⚠️ Si dos ejecuciones concurrentes no tienen `sesion_id` guardado, cada una genera uno distinto → **sesiones duplicadas**.
- `DB_Upsert_Cliente`: upsert `clientes` por `numero_whatsapp` (actualiza `ultima_interaccion`).
- `DB_Upsert_Sesion`:
  ```sql
  INSERT INTO sesiones (sesion_id, numero_whatsapp, bot, cedula, estado_sesion)
  VALUES (...) ON CONFLICT (sesion_id) DO UPDATE ...
  ```
  ⚠️ **No** usa `numero_whatsapp` como conflicto: crea una fila nueva si el `sesion_id` es distinto.
- `Restaurar_Contexto`: pasa el `sesion_id` al resto del flujo.

### 3.6 Manejo de cédula / imagen
- `IF_ImagenSinCedula`: imagen sin caption ni cédula guardada → pedir cédula (`Send_PedirCedulaTrasComprobante` si ya hay comprobante, o `Send_SolicitarCedula`).
- `IF_EsperandoCedula`: si el estado previo era `esperando_cedula`/`esperando_comprobante`/`esperando_imagen_y_cedula` y llega imagen → `Resolver_CedulaActiva` (une cédula del mensaje + guardada).
- `Guardar_CedulaTexto_YValidar` / `Resolver_CedulaActiva` → `Reactivacion_ValidarCedula` (HTTP `GetClientsDetails`) → `IF_validarCedula` (`estado == 'exito'`).
- `Log_CedulaValida` → `Marcar_EsperandoComprobante` (estado `esperando_comprobante`) → `IF_validarServicioNoRetirado` → `FiltrarClienteRetirado` (solo clientes ACTIVO/SUSPENDIDO).

### 3.7 Procesamiento del comprobante (imagen)
1. `Send_ConfirmacionRecepcionImagen` (WhatsApp) + log `comprobante_recibido`
2. `Meta_ObtenerURLMedia` → `Meta_DescargarImagen` (binario)
3. `OCR_LeerComprobante` (OpenAI, analiza la imagen) → `OCR_PrepararDatosParaSwitch` (parsea el JSON del modelo) → `Switch_Intencion`:
   - `opcion = 0` → **no es comprobante** → `Set_MensajeRechazo` → `Msg_Rechazo` (+ posible transferencia a pagos)
   - `opcion = 1` (foto) / `opcion = 2` (captura) → `Set_RutaLegible`:
     - Define `carpeta_destino`: `whatsapp_imagenes` (legible) / `whatsapp_imagenes_no_detectadas_ocr`
     - Define `archivo_csv`: `registro_datos.csv` / `registro_datos_no_detectado_ocr.csv`
     - `es_legible` = banco + monto>0 + (transacción|fecha|titular)
4. `IF_TipoMensajeOCR` (¿es legible?) → `Log_OCRLegible` / `Log_OCRIlegible`
5. `GenerarNombreArchivoImagen` → nombre `${cedula}_${fechaLocal}_${from}.${ext}`; **usa hora LOCAL de Guayaquil solo para el nombre del archivo**
6. `GuardarImagenEnDisco` (escribe el binario en el disco)
7. `OCR_ParsearJSON` (parsea/valida el JSON del OCR; opcion 0/1/2)
8. `CSV_PrepararLinea` → `Validar Duplicados`:
   - **Reconstruye el índice SIEMPRE** leyendo los 2 CSV de disco (evita condición de carrera del caché)
   - `duplicado` = el número de transacción ya existe en algún CSV (normalizado: sin espacios/puntos/guiones)
9. `If` → si `duplicado` → `Log_ComprobanteDuplicado` y se detiene; si no → `CSV_RegistrarTransaccion1` (append al CSV `registro_datos.csv`) **y** `DB_CSV_RegistrarTransaccion1` (insert en `comprobantes`, `estado='recibida'`, `origen='ocr_automatico'`, con `sesion_id`)
10. `Detectar_ClienteResuelto` → `IF_ClienteResuelto` → `IF_TipoMensajeOCR` (sigue el flujo legible) o `Send_PedirCedulaTrasComprobante`
11. `Guardar_ComprobanteEnSesion` (Code): guarda el comprobante en `staticData` (para multi-servicio)
12. `DB_Guardar_ComprobanteEnSesion`: **UPDATE `sesiones` SET `comprobante_id` = id del comprobante insertado** (matching por `sesion_id` de ESTA ejecución)
13. `IF_MultiplesServicios` (usando `FiltrarClienteRetirado.total`):
    - **1 servicio** → `ClienteUnico` → `Resolver_ClienteContexto` (flujo de activación, 3.9)
    - **varios** → `ConstruirMenuServicios` (filas `serv_<codigo>`), `Guardar_ContextoMultiServicio` + `DB_Marcar_MultiplesServicios` (guarda `servicios_disponibles` y `menu_generado_en` en `sesiones`), `Send_MenuServicios` (lista interactiva)

### 3.8 Rama multi-servicio (clic en la lista)
- Nueva ejecución por el clic → `IF_EsSeleccionServicio` (TRUE) → `DB_Leer_SesionParaSeleccion`:
  ```sql
  SELECT ... FROM sesiones s LEFT JOIN comprobantes c ON c.id = s.comprobante_id
  WHERE s.numero_whatsapp = $1 AND s.estado_sesion = 'activa'
  ORDER BY s.sesion_id DESC LIMIT 1
  ```
- `GuardarSeleccionServicio`:
  - Valida expiración del menú (15 min)
  - Toma `codigoSeleccionado` de `button_id` (`serv_<codigo>`)
  - Une el cliente elegido con el **comprobante de la sesión activa** (JOIN a `comprobantes`)
  - Si no hay comprobante → error "envíalo nuevamente"
- `IF_SeleccionValida` → `Resolver_ClienteContexto` → flujo de activación (3.9)

### 3.9 Activación y cierre — `Resolver_ClienteContexto` → ... → `DB_Cerrar_Sesion_Reactivado`
`Resolver_ClienteContexto` es el **punto de convergencia** (cliente único y multi-servicio): resuelve `idcliente`, `codigo_servicio`, `numero_transaccion`, `monto`, `cedula`, `sesion_id`, `duplicado`, `opcion_ocr`, `comprobante_id`.

1. `OCR_GetFacturas` (HTTP `GetInvoices`) → `OCR_GetFacturas1_MergeContexto` (reintegra el contexto) → `OCR_CalcularDeuda` (suma facturas "No pagado"/"vencido") → `OCR_UnificarMonto`
2. `IF_MontoCoincide_OCR` (monto esperado >= deuda) → si NO → `GetFacturas_VerificarEstado` → `IF_FacturaAnulada` / `IF_DeudaCero` (deuda 0 → `Send_CuentaAlDia`; suspendida/anulada → `Send_LineaSuspendida_BotonPagos` → `Set_EstadoConversacion_TransferidoPagos` → `Notificar_AreaPagos` → **`DB_Cerrar_Sesion_TransferidoPagos`**)
3. Si el monto coincide → `OCR_CalcularExcedente` (sobrepago) → `OCR_PrepararActivacion` → `OCR_PagarFactura` (HTTP `PaidInvoice`) → `DB_Log_Auditoria_PagoFactura` → `OCR_ActivarCliente` (HTTP `ActiveService`) → `DB_Log_Auditoria_ActivarServicio`
4. `IF_HayExcedente` → `DB_Insertar_SaldoAFavor` (sobrepago → saldo a favor)
5. `OCR_GetCliente_ConfirmacionFinal` (HTTP `GetClientsDetails`) → `Sticker_PrepararPagoVerificado` → `Sticker_SubirPagoVerificado` → `Sticker_EnviarPagoVerificado` → `clienteFinal` (elige el servicio reactivado: match por código, luego ACTIVO, luego más reciente)
6. `Send_ComprobanteReactivacion` (WhatsApp: "pago verificado, reactivación en proceso")
7. `Log_ReactivacionExitosa` + `DB_Log_ReactivacionExitosa` (evento `reactivacion_exitosa` en `eventos_interaccion`)
8. **`DB_Cerrar_Sesion_Reactivado`**: UPDATE `sesiones` SET `estado_sesion='cerrada'`, `resultado='reactivado'`, `fin=...` — **matching por `Resolver_ClienteContexto.sesion_id`**

### 3.10 Otros cierres
- **`DB_Cerrar_Sesion_SinComprobante`**: después de 3 intentos sin comprobante (`IF_IntentosMenor` < 3) → `Send_CierreInteraccion`, `DT_Reset_Estado` → `resultado='cerrado_sin_comprobante'`
- **`DB_Cerrar_Sesion_NoReconocido`**: mensaje no reconocido (rama de IA `CSV_RegistrarTransaccion4`) → `resultado='no_reconocido'`
- **`DB_Cerrar_Sesion_TransferidoPagos`**: `resultado='transferido_pagos'` (ver 3.9)

### 3.11 Intención (IA)
- `IA_ClasificarIntencion` (OpenAI) → `Switch_IntencionIA`:
  - sticker de bienvenida + `Send_PoliticaPrivacidad` + `Send_MenuPrincipal` (log `menu_principal_mostrado`)
  - botón Reactivar → `MarcarEstado_Reactivar` (intención `reactivar`, estado `esperando_imagen_y_cedula`)
  - botón Consultar saldo → `MarcarEstado_ConsultarSaldo` (intención `consultar`, estado `esperando_cedula`)
  - texto con cédula → `Guardar_CedulaTexto_YValidar` → validación
  - no reconocido → `CSV_RegistrarTransaccion4` + `Send_MensajeNoReconocido`

---

## 4. Estado de conversación (`staticData.conversaciones[from]`)

| Campo | Se escribe en | Para qué |
|---|---|---|
| `sesion_id` | `Sesion_GestionarID` | ID estable de la conversación (reuso) |
| `estado` | `MarcarEstado_*`, `Guardar_CedulaTexto_YValidar`, `Marcar_EsperandoComprobante`, etc. | Máquina de estados: `inicio`, `esperando_cedula`, `esperando_comprobante`, `esperando_imagen_y_cedula` |
| `intencion` | `MarcarEstado_*` | `reactivar` / `consultar` |
| `cedula` | `Guardar_CedulaTexto_YValidar`, `Marcar_EsperandoComprobante` | Cédula ya validada |
| `comprobante` | `Guardar_ComprobanteEnSesion` | Comprobante guardado (respaldo multi-servicio) |
| `servicios_disponibles` | `Guardar_ContextoMultiServicio` | Lista de clientes para el menú |
| `menu_generado_en` | `Guardar_ContextoMultiServicio` | Expiración del menú (15 min) |
| `intentos_comprobante` | `DT_Update_Intentos` | Límite de 3 intentos |
| `ultima_actividad` | varios | Limpieza de conversaciones > 24 h |
| `mensajesProcesados` / `botonesProcesados` (mapas globales) | `Filtrar_MensajesDuplicados` | Deduplicación de webhooks/doble-tap |

⚠️ `staticData` se **pierde si se reinicia/desactiva el workflow** (o se vacía manualmente): las conversaciones en curso y los `sesion_id` guardados se pierden → la siguiente ejecución crea una sesión nueva. La fuente de verdad real es PostgreSQL.

---

## 5. Variables de entorno ($env) y rutas de archivos

| Variable / ruta | Uso |
|---|---|
| `API_COSTANET` | Base de la API Costanet/Mikrowisp (`GetInvoices`, `PaidInvoice`, `ActiveService`, `GetClientsDetails`) |
| `API_TOKEN_ADMIN` / `API_DEMO` | Tokens de la API (definidos en el servicio n8n) |
| `/home/Tlsg_n8n/registro_datos.csv` | Registro de transacciones legibles (para validación de duplicados) |
| `/home/Tlsg_n8n/registro_datos_no_detectado_ocr.csv` | Transacciones ilegibles |
| `/home/Tlsg_n8n/registro_datos_ingresados_cliente.csv` | Transacciones ingresadas manualmente |
| `/home/Tlsg_n8n/whatsapp_imagenes/` | Imágenes legibles (las usa el panel para mostrar comprobantes) |
| `/home/Tlsg_n8n/whatsapp_imagenes_no_detectadas_ocr/` | Imágenes ilegibles |
| `/home/Tlsg_n8n/logs/interacciones.jsonl` | Log de eventos (los `Log_*` escriben aquí y también en `eventos_interaccion`) |
| `/home/Tlsg_n8n/imagenes/guia_reactivacion.png` | Guía enviada al cliente |

---

## 6. Convención de fechas — PUNTO CRÍTICO (bug de zona horaria)

El bot escribe las fechas de **dos maneras distintas**, y el panel las interpreta todas como UTC:

| Campo | Cómo lo escribe el n8n | Ejemplo real (crudo en BD) |
|---|---|---|
| `sesiones.inicio` | `now()` de PostgreSQL (UTC) | `2026-08-04 02:26:51.965` |
| `sesiones.fin` | `$now.setZone('America/Guayaquil').toFormat('yyyy-MM-dd HH:mm:ss')` — **LOCAL** | `2026-08-03 21:27:43` (¡parece anterior al inicio!) |
| `eventos_interaccion.fecha_evento` | `setZone('America/Guayaquil')` — **LOCAL** | `2026-08-03 21:27:11` |
| `comprobantes.fecha_hora` | `now()` (UTC, con microsegundos) | `2026-08-04 16:20:14.705` |
| `clientes.ultima_interaccion` | `setZone('America/Guayaquil')` — **LOCAL** | `2026-08-03 21:02:08` |
| `sesiones.ultima_actividad` | `Date.now()` (epoch ms) | `1785810409000` |
| `menu_generado_en` | `Date.now()` (epoch ms) | — |

**El panel (Laravel) asume que TODO está en UTC** (cast `BotDatetime` convierte UTC → Guayaquil). Consecuencias:
- `inicio` y `fecha_hora` se muestran correctos (−5 h).
- `fin`, `fecha_evento`, `ultima_interaccion` se muestran con **5 horas de menos** (se escribieron en local y el cast les resta otras 5).
- Esto también explica que en algunas sesiones el `fin` parezca ANTERIOR al `inicio`.

**Arreglo propuesto (a confirmar con el equipo)**: unificar TODO a UTC (o todo a local) en el n8n. La opción más simple y segura: que TODOS los campos usen `$now.toFormat('yyyy-MM-dd HH:mm:ss')` (UTC, formato sin zona) o `$now.toISO()`, y el panel mantiene su conversión.

---

## 7. Hallazgos / bugs detectados (para arreglar entre n8n + BD + panel)

### 7.1 ✅ CORREGIDO Y PUBLICADO (2026-08-04) — Sesiones duplicadas por ejecuciones concurrentes
- **Síntoma en BD**: 8 sesiones con `resultado='reactivado'` sin comprobante (las 8 filas con $0 del reporte). Ejemplo real: el número 593988882789 generó 3 sesiones en el mismo segundo (`..._1781` activa sin comprobante, `..._1936` cerrada/reactivado sin comprobante, `..._8718` activa **con** el comprobante $20).
- **Causa**: 
  1. `Sesion_GestionarID` genera `${from}_${Date.now()}` y lo guarda en `staticData` (RAM) — si Meta reenvía el webhook o llegan ejecuciones concurrentes (o se perdió el staticData tras un reinicio), cada ejecución genera un `sesion_id` distinto.
  2. `Filtrar_MensajesDuplicados` también usa `staticData` sin lock → dos ejecuciones concurrentes pueden pasar la deduplicación a la vez.
  3. El cierre (`DB_Cerrar_Sesion_Reactivado`) usa el `sesion_id` de la ejecución actual → puede cerrar como "reactivado" una sesión sin comprobante, mientras el comprobante quedó en otra sesión que sigue `activa`.
- **Arreglo aplicado y PUBLICADO** (versión activa `dde027cc-...`): nodo `DB_Leer_SesionActiva` antes de `Sesion_GestionarID` (reuso de la sesión ACTIVA en BD por `numero_whatsapp`) + limpieza de los datos duplicados (sección 0).
- **Causa raíz del primer intento fallido**: n8n ejecuta la versión PUBLICADA de `workflow_history` (campo `workflow_entity.activeVersionId`), no el draft. La publicación se replicó manualmente: INSERT en `workflow_history` (nueva `versionId`, `autosaved=0`) + UPDATE `workflow_entity` (`nodes`, `connections`, `versionId`, `activeVersionId`) + INSERT `workflow_publish_history` (`activated`).

### 7.2 ✅ CORREGIDO Y PUBLICADO (2026-08-04) — Zona horaria mezclada (ver sección 6)
- Unificados a UTC los 22 nodos DB que escribían con `setZone('America/Guayaquil')` (ver 0.2). El panel mantiene su conversión UTC→Guayaquil.
- El histórico previo a 2026-08-04 12:30 UTC conserva la hora local almacenada (decisión del usuario: no tocar).

### 7.3 ✅ CORREGIDO Y PUBLICADO (2026-08-04) — `DB_Guardar_ComprobanteEnSesion` fallaba con `duplicate key (id)=(0)`
- **NO era un problema de 0 filas**: el UPDATE fallaba con `duplicate key value violates unique constraint "sesiones_pkey" Key (id)=(0) already exists`.
- **Causa**: la reedición del nodo en la UI (2026-08-04) dejó en `columns.value` las columnas `"id": 0`, `"intentos_comprobante": 0`, `"menu_generado_en": 0`. El SQL generado incluía `SET id=0` y existía una sesión histórica con `id=0` (primera víctima del bug, 02/08) → violación de PK en TODOS los intentos posteriores → `comprobante_id` nunca se persistía (sesiones `reactivado` sin comprobante o `activa` con comprobante huérfano: comps 1171, 1166).
- **Fix publicado**: `columns.value` = solo `sesion_id`, `comprobante_id`, `ultima_actividad` (la config que funcionaba el 08-03: 72/72 enlazadas). Sesión `id=0` reenumerada (`id=2182`) y cerrada. Verificado que ningún FK referencia `sesiones.id`.
- **Aprendizaje**: al editar nodos Postgres en la UI, n8n puede re-inyectar columnas con valores por defecto (`0`); revisar `columns.value` antes de publicar.

### 7.4 Panel — cédula del reporte
- Ya resuelto en el panel: la cédula se busca en sesión → cualquier evento → comprobante (antes solo miraba el evento `reactivacion_exitosa`).

### 7.5 Panel — reporte de reactivaciones
- Ya usa `resultado='reactivado'` + exige comprobante (excluye las 8 fantasma) + paginación.

---

## 8. Preguntas abiertas / pendientes para revisar juntos

1. **Sesión duplicada**: ✅ RESUELTO y PUBLICADO (opción A: reuso de sesión ACTIVA en BD). Se descartaron B y C.
2. **Zona horaria**: ✅ RESUELTO y PUBLICADO — todo UTC en los nodos DB; histórico sin corregir por decisión del usuario.
3. **`comprobante_id`**: ✅ RESUELTO y PUBLICADO — columna `id:0` eliminada de `DB_Guardar_ComprobanteEnSesion` (7.3); pendiente solo la verificación con ejecuciones reales.
4. **Dónde aplicar**: ✅ Aplicado en producción con backup (export JSON + copia de la DB SQLite del n8n con `.backup`).
5. **Datos históricos**: ✅ Limpieza aplicada con resumen previo (sección 0.3).
6. **Nuevo pendiente**: revisar si quedan sesiones `activa` abandonadas (205 activas; muchas son abandonadas legítimas — decidir si se añade un cierre por inactividad en el n8n).

---

## 9. Notas

- Workflow de producción: `wEyRieNktfYASmur` ("CostyBot Whatsapp - Reactivación", 159 nodos, activo, versión publicada `97fc223c-b43a-4c01-a039-1bef6911bd11`).
- Workflow de desarrollo: `BfwEauoRPJYzYI1P` (341 nodos, contiene nodos duplicados con sufijo `1`/`2` — probablemente versiones antiguas sin limpiar).
- El n8n corre como usuario `n8n`, HOME=`/var/lib/n8n`, SQLite del n8n en `/var/lib/n8n/.n8n/database.sqlite` (solo lectura para este análisis).
- Los nodos HTTP hacia Meta usan el número de negocio `1219363517917163` (Graph API v21.0) y v25.0 para media de WhatsApp.
- El análisis se hizo leyendo el workflow en SOLO LECTURA; los arreglos se aplicaron y publicaron según se documenta en la sección 0 (con backups en `backups/n8n/`).
