# Recuperación de base de datos — 21 de agosto de 2026

## Incidente

Una ejecución de PHPUnit se inició mientras existía el caché de configuración
productivo. Laravel ignoró temporalmente la conexión SQLite declarada en
`phpunit.xml` y `RefreshDatabase` operó sobre PostgreSQL de producción.

## Recuperación aplicada

- Se detuvieron temporalmente n8n, el programador y el acceso al backoffice.
- Se preservó el estado posterior en:
  - `costy_sesiones_20260821_154805.sql`
  - `costy_sesiones_20260821_155003.sql`
- Se restauró en una base paralela el respaldo íntegro:
  - `costy_sesiones_20260821_144504.sql`
- Se fusionaron únicamente las interacciones reales posteriores:
  - 4 registros de clientes, incluyendo 3 actualizaciones.
  - 8 sesiones.
  - 5 comprobantes nuevos y 1 comprobante histórico reutilizado por número de transacción.
  - 21 eventos de interacción.
- La base afectada se conservó como `costy_sesiones_incidente_20260821_1550` para reversión y auditoría.
- Se generó el respaldo final restaurado:
  - `costy_sesiones_20260821_163744.sql`

## Validación final

- 8 usuarios y 3 roles.
- 840 clientes.
- 1.193 sesiones.
- 1.971 comprobantes.
- 1.655 eventos de interacción.
- 3.197 registros de auditoría.
- `desarrollador@costanetplus.net`: activo, no bloqueado, cero intentos fallidos y rol `superadministrador`.
- Todas las migraciones se encuentran aplicadas.
- CostyBO `/up`: HTTP 200.
- CostyBO `/login`: HTTP 200.
- Servicios CostyBO, n8n y programador: activos.

## Prevención permanente

`tests/TestCase.php` bloquea PHPUnit antes de inicializar Laravel cuando existe
un caché de configuración productivo. Después del arranque vuelve a comprobar
que el entorno sea `testing`, la conexión sea `sqlite` y la base sea `:memory:`.
Las pruebas ya no pueden operar sobre PostgreSQL productivo.
