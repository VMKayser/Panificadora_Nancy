# CHANGELOG

## 2026-10-06 — Seguridad: correcciones de la auditoría OWASP Top 10:2025 / ASVS L2

Resumen
-------
Se corrigieron los 21 hallazgos de la auditoría de seguridad (SIS316) que se pueden resolver desde el código y
se desplegó en producción el mismo día, con respaldo previo completo (código, imágenes, `.env` y base de datos).
Procedimiento de respaldo, reversión y registro del despliegue: `deploy/production/RESPALDOS-Y-DESPLIEGUE.md`.

Cambios por hallazgo
--------------------
- **H01 / H15 — Control de acceso.** `/api/inventario/*` (materias primas, recetas, producción, stock, caja) solo
  para `admin`; el panadero conserva `POST /inventario/producciones`. Clientes, panaderos, empleados, nómina
  (`empleado-pagos`), WhatsApp y configuración pasan a solo `admin`. Se eliminaron las rutas duplicadas entre los
  grupos `admin,vendedor` y `admin`; `PUT /admin/configuraciones/actualizar-multiples` era accesible al vendedor.
- **H02 — Venta de mostrador.** Solo `admin`/`vendedor` (401/403 en otro caso). Nueva ruta
  `POST /api/admin/ventas-mostrador`, que es la que usa el punto de venta.
- **H04 — Límite de tasa.** `POST /api/pedidos`: 5/min y 30/h por IP (el personal no tiene límite). Límite general
  de la API: 120/min por usuario y 60/min por IP. Cada 429 se registra.
- **H05 — SSRF.** La URL del proveedor de WhatsApp debe ser HTTPS, estar en `graph.facebook.com` o en
  `WHATSAPP_ALLOWED_HOSTS` y no resolver a IP privadas/loopback/reservadas; sin redirecciones, timeout 15 s y
  respuesta guardada recortada a 1000 caracteres.
- **H06 — Cabeceras.** `SecurityHeaders` registrado de verdad (`bootstrap/app.php`); CSP sin `unsafe-inline` en
  scripts; `frontend/public/.htaccess` con CSP, HSTS, X-Frame-Options, nosniff, Referrer-Policy y
  Permissions-Policy; sin `X-Powered-By`.
- **H07 — Errores.** Ninguna respuesta devuelve mensajes de SQL/PHP (`App\Support\MensajeError`); manejador global
  con `error_id`; `/api/health` sin detalles internos.
- **H08 / H09 — Registro.** Canal `security` (`storage/logs/security-*.log`, 90 días) con logins, accesos denegados,
  límites excedidos y cambios de contraseña (`App\Support\SecurityLog`). Se quitó el volcado de datos personales de
  los pedidos en `laravel.log`.
- **H10 — Contraseñas.** Política única (`App\Support\PasswordPolicy`: 8+ con mayúscula, minúscula y número) en
  registro, cambio de clave y altas del admin; al cambiar la clave se cierran las demás sesiones.
- **H11 — Credenciales por defecto.** `AdminUserSeeder` ya no restablece la clave del admin existente y fuera de
  local/testing la toma de `ADMIN_SEED_PASSWORD` o la genera al azar; los seeders de prueba no corren en producción.
- **H12 — Sesión.** Tokens Sanctum de 8 h (`SANCTUM_EXPIRATION`) y limpieza diaria de tokens vencidos.
- **H13 / H21 — Secretos.** Eliminados `test-smtp.php`, `enviar-correos-completos.php` y scripts temporales del
  repositorio y del servidor. Pendiente fuera del código: rotar la credencial SMTP y purgar el historial de Git.
- **H14 — Listados.** `sort_by`/`sort_order` con lista blanca y `per_page` con tope (trait `ListadoSeguro`).
- **H17 — CORS.** Orígenes de desarrollo solo fuera de producción; extras por `CORS_EXTRA_ORIGINS`.
- **H18 — Recuperación de contraseña.** `POST /api/forgot-password` y `/api/reset-password` (enlace de un solo uso,
  60 min, respuesta genérica, cierra todas las sesiones) y pantallas `/olvide-clave` y `/restablecer-clave`.
- **H19 — Ticket.** El ticket de impresión del vendedor escapa el HTML.
- **H03 / H16 / H20 — Dependencias y cadena de suministro.** Laravel 12.32.5 → 12.69.3 (composer audit sin
  advisories), `platform.php` fijado en 8.2.33 y `predis` en `^3.2`; frontend con `jspdf` 4, `jspdf-autotable`
  5.0.8 y `xlsx` 0.20.3 (CDN oficial de SheetJS), `npm audit fix`. Workflow `.github/workflows/security.yml`
  (composer audit, npm audit, gitleaks, CodeQL), Dependabot y `npm ci` en el despliegue del frontend.

Validación
----------
- 104 tests PHPUnit en verde con PHP 8.2 (14 nuevos en `tests/Feature/SeguridadAuditoriaTest.php`; 7 tests
  existentes se ajustaron porque usaban usuarios sin rol o contraseñas débiles).
- Verificación en producción tras el despliegue: ver `deploy/production/RESPALDOS-Y-DESPLIEGUE.md`.

Riesgos aceptados / pendientes
-----------------------------
- Vite 4 (advisories del servidor de desarrollo, no del build) y react-router 6 (open redirect no explotable aquí).
- El token sigue en `localStorage`; migrar a cookies HttpOnly es un cambio de arquitectura.
- `deploy-backend.sh` aún no implementa el procedimiento de despliegue documentado.

## 2025-10-24 — Corrección: desincronización de transacciones en tests, seeders idempotentes y limpieza

Resumen
-------
Se solucionó un fallo crítico reproducible únicamente en MySQL durante la ejecución de la suite de pruebas: errores de SAVEPOINT / "There is no active transaction" provocados por la destrucción tardía de objetos `PendingCommand` que invocaban el kernel/artisan durante el teardown de tests. También se corrigieron errores secundarios por seeders no idempotentes que provocaban entradas duplicadas en `metodos_pago`. Finalmente, se eliminó la instrumentación temporal utilizada para el diagnóstico y se verificó que la suite de tests queda completamente verde.

Cambios clave
-----------
- Evitar efectos secundarios de `PendingCommand` durante el bootstrap de tests: las invocaciones a `artisan()` usadas en la fase de bootstrap se ejecutan de forma que no devuelvan objetos `PendingCommand` que puedan ser destruidos mientras hay transacciones activas (previene desconexiones PDO y desincronizaciones de niveles de transacción).
- Seeders idempotentes: `database/seeders/MetodoPagoSeeder.php` y `database/seeders/DemoDataSeeder.php` cambiados para usar `DB::table(...)->upsert(...)`.
- Tests y observers adaptados para idempotencia: reemplazo de `MetodoPago::create(...)` por `MetodoPago::firstOrCreate(...)` o aislamiento explícito de datos en tests (`MetodoPago::query()->delete()` donde correspondía).
- Limpieza de diagnóstico temporal: eliminación de listeners, kernel proxy y logs (por ejemplo `storage/logs/test-transactions.log`, `storage/logs/pending-command-creation.log`) y reversión de `tests/TestCase.php` a una versión minimalista sin instrumentación.

Validación
----------
- Ejecución completa de la suite PHPUnit dentro del contenedor Docker de pruebas: OK (33 tests, 154 assertions).
- Reproducción del fallo original antes de los cambios; tras aplicar correcciones y adaptar tests, la suite pasó completamente.

Notas y próximos pasos
---------------------
- Recomendado: auditoría global de llamadas a transacciones (`DB::transaction`, `beginTransaction`, etc.) y aplicación de un guard (`SafeTransaction`) en call-sites críticos (Producción, Inventario, Pedidos) para prevenir regressiones.
- Añadir tests de regresión específicos que reproduzcan escenarios de desincronización de transacciones bajo MySQL.

Firmado: Equipo de desarrollo — Corrección investigada y aplicada el 2025-10-24.
