# Sistema Administrativo de Auditoría — Costy (Costanet)
### Documento técnico — Backend Laravel 11 + PostgreSQL

**Supuestos asumidos** (no confirmados en la conversación, ajustar si difieren):
- Backend: Laravel 11 + PHP 8.3, siguiendo tu stack actual en NEXUS Lite.
- Frontend: Blade + Livewire (evita duplicar SPA si el equipo es reducido) — alternativa: Vue si prefieres SPA desacoplada. Se documentan ambas rutas.
- Autenticación: Laravel Breeze/Fortify + Spatie Laravel-Permission para roles/permisos (evita reinventar RBAC).
- Almacenamiento de comprobantes: actualmente vía `media_id` de WhatsApp Business API descargado por n8n. Se asume que las imágenes se guardan en disco/S3 y que `ruta_imagen` en `comprobantes` apunta a esa ubicación. **Debes confirmar dónde n8n está dejando los archivos físicamente** (¿local, S3, Google Drive?) antes de implementar el visualizador.
- Volumen: bajo-medio (bot conversacional 1 a 1), por lo que no se requiere particionamiento de tablas por ahora.
- Un solo tenant (Costanet). Si luego se agrega Telearseg u otro cliente, se recomienda agregar `empresa_id` desde ya en `usuarios` para no migrar después.

---

## 1. Introducción

Costy es un bot de WhatsApp (n8n) que gestiona reactivaciones de servicio, valida comprobantes bancarios vía OCR/IA y registra todo en PostgreSQL (`costy_sesiones`). Este documento diseña el sistema web administrativo que permitirá al área de Contabilidad auditar esas interacciones, sin modificar el funcionamiento del bot.

## 2. Objetivos

- Dar visibilidad total sobre sesiones, comprobantes y documentos de identidad procesados por el bot.
- Permitir revisión, aprobación, rechazo y anotación de comprobantes por parte de Contabilidad.
- Registrar de forma inalterable quién hizo qué, cuándo y desde dónde (auditoría).
- No romper ni interferir con las tablas que n8n ya escribe (`sesiones`, `eventos_interaccion`, `comprobantes`, `documentos_identidad`, `clientes`).

## 3. Alcance

**Incluye:** autenticación, gestión de usuarios/roles/permisos, dashboard, listado y detalle de interacciones, visor de comprobantes, observaciones, historial de estados, auditoría, exportación.

**No incluye (fase 1):** modificar el flujo de n8n, notificaciones push, app móvil nativa, multi-tenant real.

## 4. Actores

| Rol | Descripción |
|---|---|
| Superadministrador | Control total, incluida gestión de usuarios y configuración |
| Administrador | Auditoría completa + edición de campos + reportes, sin gestionar superadmin |
| Contabilidad | Revisión/aprobación de comprobantes, observaciones, sin eliminar ni administrar usuarios |

## 5. Arquitectura recomendada

```
n8n (Costy bot) ──escribe──▶ PostgreSQL (costy_sesiones)
                                     ▲
                                     │  Eloquent (solo lectura sobre tablas de n8n)
                              Laravel 11 App
                              ├─ app/Models        (Sesion, Comprobante, DocumentoIdentidad → readonly)
                              ├─ app/Models         (Usuario, Rol, Permiso, AuditoriaLog → CRUD)
                              ├─ Spatie Permission  (roles/permisos)
                              ├─ Laravel Sanctum     (si se expone API para móvil futuro)
                              ├─ Policies            (autorización por modelo)
                              ├─ Livewire/Vue        (frontend)
                              └─ Observers/Middleware (auditoría automática)
```

**Principio clave:** las tablas del bot (`sesiones`, `comprobantes`, `eventos_interaccion`, `documentos_identidad`, `clientes`) se tratan como **solo lectura** desde Laravel para las columnas que n8n gestiona. Los campos nuevos de auditoría (`estado_auditoria`, `revisado_por`, etc.) se agregan a esas mismas tablas mediante migración, pero solo Laravel los escribe — n8n nunca los toca.

## 6. Módulos (resumen — detalle completo ya definido en tu prompt original)

1. Login (Breeze/Fortify) — bloqueo tras intentos fallidos, registro de accesos.
2. Dashboard — KPIs vía Eloquent con caché (Redis o `cache()` de Laravel, TTL 5 min).
3. Interacciones — `Livewire\Component` con tabla paginada + filtros (usa `spatie/laravel-query-builder` para filtros dinámicos).
4. Detalle de interacción — vista de dos columnas + timeline de `eventos_interaccion`.
5. Visor de comprobante — componente con zoom/rotación en JS (ej. Viewer.js), descarga controlada por `Storage::temporaryUrl()` si usas S3.
6. Gestión de usuarios/roles/permisos — CRUD estándar sobre Spatie Permission.
7. Auditoría — tabla append-only, sin rutas de edición/eliminación en el controller.
8. Reportes/exportación — `maatwebsite/excel` para exportar a XLSX/CSV.

## 7. Roles y permisos

Usa **Spatie Laravel-Permission** en vez de tablas propias `roles`/`permisos`/`rol_permisos` — es el paquete estándar de Laravel, ya te da las tablas `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` con migraciones probadas. El script SQL más abajo NO reinventa estas tablas; usa `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"` para generarlas, y solo se agregan los permisos custom vía seeder.

Permisos sugeridos (seeder):
```
usuarios.ver, usuarios.crear, usuarios.editar, usuarios.eliminar
roles.ver, roles.administrar
interacciones.ver, interacciones.editar, interacciones.eliminar
comprobantes.ver, comprobantes.descargar, comprobantes.revisar, comprobantes.aprobar, comprobantes.rechazar
observaciones.crear
reportes.ver, reportes.exportar
auditoria.ver
configuracion.ver, configuracion.editar
```

Autorización: cada acción protegida con `Gate::authorize()` o `$this->authorize()` en Policies — nunca confiar solo en que el frontend oculte el botón.

## 8. Seguridad

- Password hashing: Laravel usa `bcrypt` por defecto (`Hash::make`); puedes cambiar a Argon2id en `config/hashing.php` (`'driver' => 'argon2id'`).
- Rate limiting de login: `Fortify` ya trae throttle; configurar 5 intentos / 1 min con bloqueo temporal (`RateLimiter::for('login', ...)`).
- Sesiones: Laravel Sanctum o sesiones de cookie firmadas; para "sesiones activas" usa `database` como session driver (tabla `sessions` nativa de Laravel) — no reinventes `sesiones_usuario`, la tabla `sessions` de Laravel ya cubre eso.
- Comprobantes: nunca servir la ruta física directamente. Usa una ruta controlada `GET /comprobantes/{id}/imagen` que valide permiso y haga `Storage::response()` o URL firmada temporal.
- CSRF: automático en Laravel (`@csrf` en formularios Blade / VerifyCsrfToken middleware).
- SQLi: Eloquent/Query Builder parametriza por defecto — evita `DB::raw()` con interpolación de strings del usuario.
- XSS: Blade escapa con `{{ }}` por defecto; nunca usar `{!! !!}` con datos de usuario.
- HTTPS obligatorio en producción (`URL::forceScheme('https')` en `AppServiceProvider`).
- Backups: `pg_dump` programado (cron) + retención de logs de auditoría según política interna (sugerido: mínimo 1 año).

## 9. Flujo de estados de auditoría

```
PENDIENTE → EN_REVISION → APROBADO
                        → RECHAZADO → (opcional) ESCALADO
          → DUPLICADO (automático si comprobantes.numero_transaccion ya existe)
          → CON_NOVEDAD (requiere observación obligatoria)
          → ANULADO (solo Superadmin/Admin, requiere motivo)
```

| Transición | Permiso requerido |
|---|---|
| PENDIENTE → EN_REVISION | `comprobantes.revisar` |
| EN_REVISION → APROBADO | `comprobantes.aprobar` |
| EN_REVISION → RECHAZADO | `comprobantes.rechazar` |
| Cualquiera → ANULADO | `configuracion.editar` (reservado a Admin+) |

Implementación recomendada: un **Enum de PHP** (`App\Enums\EstadoAuditoria`) + método `puedeTransicionarA()` en el modelo `Comprobante`, en vez de dejar la lógica dispersa en controllers.

## 10. Modelo de datos

Ver `script_bd_costy_admin.sql`. Resumen de decisiones:
- Se agregan columnas de auditoría directamente a `comprobantes` (no una tabla paralela), porque 1 comprobante = 1 ciclo de revisión. `revisiones_comprobante` se mantiene como **historial** (una fila por cada cambio de estado).
- `usuarios`, roles y permisos: gestionados por Spatie (no se crean tablas propias).
- `auditoria_logs`: tabla append-only, sin FK de borrado en cascada, protegida por Policy que bloquea `update`/`delete` para todos los roles.
- Eliminación lógica: se usa en `comprobantes` y `observaciones_interaccion` vía `deleted_at` nativo de Laravel (**Soft Deletes**), no campos custom `eliminado`/`eliminado_en` — así aprovechas `SoftDeletes` trait, scopes automáticos y `restore()` sin código adicional.

## 11. Endpoints sugeridos (API interna, si usas Vue/SPA; con Livewire muchos de estos son componentes, no rutas API)

| Método | Ruta | Descripción | Permiso |
|---|---|---|---|
| POST | `/login` | Autenticación | público |
| POST | `/logout` | Cierre de sesión | autenticado |
| GET | `/api/dashboard` | KPIs | `reportes.ver` |
| GET | `/api/interacciones` | Listado + filtros | `interacciones.ver` |
| GET | `/api/interacciones/{sesion_id}` | Detalle + timeline | `interacciones.ver` |
| GET | `/comprobantes/{id}/imagen` | Imagen protegida | `comprobantes.ver` |
| GET | `/api/comprobantes/{id}/descargar` | Descarga | `comprobantes.descargar` |
| PATCH | `/api/comprobantes/{id}/estado` | Cambiar estado | `comprobantes.revisar\|aprobar\|rechazar` |
| POST | `/api/observaciones` | Crear observación | `observaciones.crear` |
| GET | `/api/usuarios` | Listado usuarios | `usuarios.ver` |
| POST | `/api/usuarios` | Crear usuario | `usuarios.crear` |
| PATCH | `/api/usuarios/{id}` | Editar/activar/desactivar | `usuarios.editar` |
| GET | `/api/roles` | Listado roles | `roles.ver` |
| POST | `/api/roles` | Crear/clonar rol | `roles.administrar` |
| GET | `/api/auditoria` | Logs | `auditoria.ver` |
| GET | `/api/reportes/export` | Exportar XLSX | `reportes.exportar` |

Ejemplo de respuesta (`PATCH /api/comprobantes/{id}/estado`):
```json
{
  "id": 145,
  "estado_anterior": "EN_REVISION",
  "estado_nuevo": "APROBADO",
  "revisado_por": "luisfer@devstar.ec",
  "revisado_en": "2026-07-30T14:22:00-05:00"
}
```

## 12. Auditoría automática (backend, no confiar en el frontend)

Usa un **Observer de Eloquent** global o un **trait `Auditable`** aplicado a `Comprobante`, `Usuario`, `ObservacionInteraccion`:

```php
// app/Observers/AuditoriaObserver.php
public function updated(Model $model): void
{
    AuditoriaLog::create([
        'usuario_id'      => auth()->id(),
        'accion'          => 'update',
        'modulo'          => class_basename($model),
        'entidad_id'      => $model->id,
        'datos_anteriores'=> $model->getOriginal(),
        'datos_nuevos'    => $model->getChanges(),
        'direccion_ip'    => request()->ip(),
        'user_agent'      => request()->userAgent(),
        'resultado'       => 'exitoso',
    ]);
}
```
El `usuario_id` sale siempre de `auth()->id()` (sesión/token), nunca de un campo enviado por el frontend.

## 13. Estructura de carpetas Laravel sugerida

```
app/
 ├─ Enums/EstadoAuditoria.php
 ├─ Models/{Sesion,Comprobante,DocumentoIdentidad,Cliente,ObservacionInteraccion,AuditoriaLog}.php
 ├─ Observers/AuditoriaObserver.php
 ├─ Policies/{ComprobantePolicy,UsuarioPolicy}.php
 ├─ Livewire/{Dashboard,InteraccionesTable,DetalleInteraccion,VisorComprobante,GestionUsuarios}.php
 └─ Http/Controllers/Api/...
```

## 14. Plan de desarrollo por fases

1. **Fase 0** — Migraciones sobre BD existente + Spatie Permission + seeders de roles/permisos.
2. **Fase 1** — Login, dashboard básico, listado de interacciones (solo lectura).
3. **Fase 2** — Detalle de interacción + visor de comprobante + observaciones.
4. **Fase 3** — Flujo de estados (revisar/aprobar/rechazar) + auditoría automática.
5. **Fase 4** — Gestión de usuarios/roles + reportes/exportación.
6. **Fase 5** — Hardening de seguridad (rate limiting, URLs firmadas, backups) + QA.

## 15. Riesgos técnicos

- Concurrencia de n8n escribiendo en `comprobantes` mientras Laravel también migra esa tabla → hacer las migraciones en horario de bajo tráfico y con `numero_transaccion` como ancla de integridad.
- Si las imágenes están en el filesystem del servidor de n8n y Laravel corre en otro servidor, hay que definir un mecanismo de acceso compartido (NFS, S3, o endpoint intermedio) — **esto hay que confirmarlo antes de construir el visor**.
- Doble fuente de verdad en roles si en el futuro n8n necesita saber el estado de auditoría — mitigarlo dejando que n8n solo lea, nunca escriba, esas columnas nuevas.

## 16. Criterios de aceptación (fase 1)

- Un usuario Contabilidad puede iniciar sesión, ver la lista de interacciones con filtros por fecha/banco/estado, abrir el detalle, ver la imagen del comprobante y cambiar su estado a EN_REVISION/APROBADO/RECHAZADO, quedando esto registrado en `auditoria_logs` sin intervención manual.
