# costyBotControl

Panel de control y monitorización para **CostyBot**, un bot de WhatsApp que gestiona reactivaciones de servicio y consultas de saldo para clientes de ISP/telecomunicaciones.

---

## 🎯 ¿Qué es este sistema?

**costyBotControl** es una aplicación Laravel que actúa como **panel de solo lectura** para supervisar la operación de un bot de WhatsApp (desarrollado en **n8n**) que:

- Recibe comprobantes de pago por WhatsApp (imagen/foto/captura)
- Usa **OCR (OpenAI)** para extraer datos del comprobante (banco, monto, transacción, fecha, titular)
- Valida contra la API **Costanet/Mikrowisp** (facturas, pagos, activación de servicio)
- Reactiva servicios automáticamente cuando el pago coincide con la deuda
- Registra saldos a favor por sobrepagos
- Maneja flujos multi-servicio (cliente con varios contratos)

El panel **nunca escribe en la base de datos**; solo consume los datos que n8n persiste en PostgreSQL.

---

## 🏗️ Arquitectura

```
Cliente (WhatsApp)
       │
       ▼
[n8n] CostyBot Whatsapp - Reactivación  (workflow de 159 nodos)
       │
       ├──► API Costanet/Mikrowisp  → GetInvoices, PaidInvoice, ActiveService
       ├──► Meta Graph API (WhatsApp) → enviar mensajes, stickers, guías
       ├──► OCR por IA (OpenAI / LangChain)
       ├──► Archivos CSV  → validación de duplicados
       ├──► Almacenamiento de imágenes
       └──► [PostgreSQL] costy_sesiones  ← Fuente de verdad
                        │
                        ▼
              [Panel Laravel] costyBotControl
              → Dashboard, Reportes, Auditoría, KPIs (solo LECTURA)
```

---

## 📊 Funcionalidades principales del panel

| Módulo | Descripción |
|--------|-------------|
| **Dashboard** | Métricas del día, KPIs (FCR/CES 7/30/90 días), últimos pagos, alertas operativas |
| **Interacciones** | Listado paginado de sesiones con línea de tiempo de eventos (cédula, comprobante, OCR, validación, reactivación) |
| **Pendientes** | Comprobantes por auditar, créditos pendientes, KYC en revisión |
| **Casos Operativos** | Detección automática de anomalías (pago sin comprobante, duplicados, monto no coincide, etc.) con asignación y SLA |
| **Resumen Gerencial** | Comparativo período actual vs anterior: interacciones, clientes, pagos, monto, créditos, casos, KPIs, serie temporal, top bancos, responsables |
| **Reportes** | Exportación a Excel de interacciones, pagos, comprobantes, auditoría |
| **Auditoría** | Log de acciones administrativas y de API (pagos, activaciones) |
| **Usuarios** | Gestión de accesos y roles (spatie/laravel-permission) |

---

## 🗄️ Modelo de datos (tablas clave)

| Tabla | Descripción |
|-------|-------------|
| `sesiones` | Una fila por conversación de WhatsApp. Campos: `sesion_id`, `numero_whatsapp`, `resultado` (`reactivado`, `cuenta_al_dia`, `transferido_pagos`, `cerrado_sin_comprobante`, `no_reconocido`), `comprobante_id`, `inicio`/`fin` (UTC), `estado_sesion` |
| `comprobantes` | Comprobantes recibidos. `estado` (`recibida`, `reactivacion_exitosa`, `duplicado`, `rechazada`), `origen` (`ocr_automatico`, `manual`), `monto`, `banco`, `numero_transaccion`, `fecha_hora` |
| `eventos_interaccion` | Línea de tiempo granular: `menu_mostrado`, `cedula_valida`, `comprobante_recibido`, `ocr_legible`, `monto_coincide`, `reactivacion_exitosa`, `encuesta_ces_respondida`, etc. |
| `clientes` | Datos del cliente sincronizados desde Costanet (`numero_whatsapp`, `nombre`, `cedula`, `ultima_interaccion`) |
| `casos_operativos` | Alertas detectadas automáticamente con tipo, severidad, asignación, SLA, resolución |
| `alertas_operativas` | Alertas de umbral (SLA vencido, acumulación pendientes, tasa de pago baja) |
| `encuestas_ces` | Encuestas CES enviadas/respondidas (escala 1-7) por tipo de gestión |
| `validaciones_identidad` | KYC: cédula, correo, derivación a revisión |
| `saldos_a_favor` | Excedentes por sobrepago, estado `pendiente`/`aplicado`/`rechazado` |
| `auditoria_logs` | Trazabilidad de acciones de API y administrativas |

---

## ⚙️ Indicadores KPI (FCR / CES)

El servicio `IndicadoresKpiService` calcula **First Contact Resolution (FCR)** y **Customer Effort Score (CES)** por tipo de gestión:

- **Reactivación**: cliente elige "Reactivar" → FCR = % sesiones resueltas en 1er contacto (pago válido → `reactivado`)
- **Consulta de valores**: cliente elige "Consultar saldo" → FCR = % sesiones donde se mostró el saldo correctamente

CES: encuesta 1-7 enviada tras cierre; se reporta promedio, % favorable (≥5), tasa de respuesta.

---

## 🚀 Requisitos

- PHP ≥ 8.2
- PostgreSQL ≥ 14
- Composer
- Node.js ≥ 18 + npm (para Vite/Tailwind)
- Extensiones PHP: `pdo_pgsql`, `bcmath`, `gd`, `intl`

---

## 📦 Instalación

```bash
# Clonar y entrar
cd costyBotControl

# Dependencias PHP
composer install

# Dependencias JS
npm install

# Configuración
cp .env.example .env
# Editar .env con credenciales de PostgreSQL (costy_sesiones)
php artisan key:generate

# Base de datos (migraciones + seeders si los hay)
php artisan migrate

# Compilar assets
npm run build

# Servidor de desarrollo
php artisan serve
```

> **Nota**: La base de datos `costy_sesiones` es **compartida con n8n**. Las migraciones de Laravel solo crean tablas auxiliares del panel (`auditoria_logs`, `casos_operativos`, `alertas_operativas`, `encuestas_ces`, `users`, `permissions`, etc.). Las tablas principales (`sesiones`, `comprobantes`, `eventos_interaccion`, `clientes`, `saldos_a_favor`, `documentos_identidad`, `validaciones_identidad`, `otp_verificaciones`) son **gestionadas 100% por n8n**.

---

## 🔐 Permisos (Spatie Laravel-Permission)

| Permiso | Módulo |
|---------|--------|
| `interacciones.ver` | Dashboard, Interacciones, Pendientes |
| `casos.ver` / `casos.gestionar` | Casos Operativos |
| `auditoria.ver` / `auditoria.exportar` | Auditoría, Resumen Gerencial |
| `reportes.ver` / `reportes.exportar` | Reportes |
| `usuarios.gestionar` | Usuarios |
| `configuracion.ver` | (futuro) |

Asignar via seeder o Tinker:
```php
$user->givePermissionTo('interacciones.ver');
$user->assignRole('operador'); // o 'supervisor', 'admin'
```

---

## 🧪 Testing

```bash
# Tests unitarios/feature (Pest/PHPUnit)
php artisan test

# Con coverage
php artisan test --coverage
```

---

## 📁 Estructura destacada

```
app/
├── Http/Controllers/        # Controladores web (Dashboard, Reportes, Casos, Resumen, Auditoría)
├── Models/                  # Eloquent models (Sesion, Comprobante, EventoInteraccion, CasoOperativo, ...)
├── Services/                # Lógica de negocio
│   ├── IndicadoresKpiService.php      # FCR / CES
│   ├── ResumenGerencialService.php    # Comparativo gerencial + serie temporal
│   ├── SeguimientoOperativo.php       # SLA de casos operativos
│   ├── ProcesarAlertasOperativas.php  # Detección automática de alertas
│   └── CasoOperativoDetector.php      # Reglas de detección de anomalías
├── Exports/                 # Excel exports (Maatwebsite/Laravel-Excel)
├── Casts/
│   └── BotDatetime.php      # Cast UTC → America/Guayaquil para fechas del bot
└── Enums/                   # Enums: ResultadoSesion, EstadoComprobante, TipoCasoOperativo, ...
```

---

## 🕐 Convención de zonas horarias (IMPORTANTE)

- **n8n escribe en UTC** (desde 2026-08-04): `sesiones.inicio`, `sesiones.fin`, `eventos_interaccion.fecha_evento`, `comprobantes.fecha_hora`, `clientes.ultima_interaccion`
- **Histórico previo**: algunos campos se guardaron en **hora local (America/Guayaquil)** → el cast `BotDatetime` les resta 5h extra al mostrar
- El panel asume **UTC en BD** y convierte a `America/Guayaquil` para visualización

---

## 🔧 Comandos útiles

```bash
# Limpiar cachés
php artisan optimize:clear

# Reconstruir cachés producción
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Ejecutar detección de casos operativos (programar en cron)
php artisan casos:detectar

# Procesar alertas operativas (programar en cron)
php artisan alertas:procesar

# Exportar resumen gerencial (también desde UI)
php artisan resumen:exportar --desde=2026-01-01 --hasta=2026-01-31
```

---

## 📚 Documentación técnica adicional

- [`costyN8N.md`](costyN8N.md) — Documentación completa del workflow n8n (159 nodos), fixes, bugs, convenciones
- [`docs/`](docs/) — Especificaciones por fase: auditoría SLA, conciliación, alertas, resumen gerencial, cierre operativo

---

## 📄 Licencia

Proyecto interno — TeleArseg / CostyBot. Uso restringido al equipo autorizado.