# Respaldos y despliegue en producción (Hostinger)

Procedimiento que se sigue antes y durante cada despliegue, y registro de los despliegues hechos con él.
Las rutas son relativas al `$HOME` de la cuenta de hosting (alias SSH `panificadora`).

| Qué | Dónde (servidor) |
|---|---|
| Backend en producción | `~/miapp/backend` |
| Frontend en producción (docroot del dominio) | `~/miapp/frontend/dist` (con `api -> ~/miapp/backend/public`) |
| Releases preparados | `~/apps/panificadora-releases/<fecha>/backend` |
| Respaldos previos a cada despliegue | `~/backups/predeploy-<fecha UTC>/` |

> Los respaldos contienen el `.env` de producción y datos personales de clientes (volcado de la BD).
> Se guardan con permisos `600` en una carpeta `700`, **nunca** se suben al repositorio y la copia local
> vive fuera del repo (`~/ProyectosPersonales/respaldos-panificadora/`).

---

## 1. Respaldo previo (obligatorio antes de desplegar)

Cada respaldo es una carpeta `~/backups/predeploy-AAAAMMDD-HHMMSS` (hora UTC del servidor) con:

| Archivo | Contenido |
|---|---|
| `backend.tar.gz` | `~/miapp/backend` completo: código, `vendor/`, `.env`, `storage/` (imágenes subidas y logs). Excluye `node_modules/` y la caché de datos. |
| `frontend-dist.tar.gz` | `~/miapp/frontend/dist` tal como estaba publicado. |
| `db.sql.gz` | Volcado completo de la base MySQL/MariaDB (`--single-transaction --routines --triggers`). |
| `estado-antes.txt` | Conteo de filas de las tablas principales y versión de Laravel, para comparar después. |
| `SHA256SUMS` | Sumas de verificación de los tres `.gz`. |
| `revertir.sh` | Script que vuelve producción al estado del respaldo (ver sección 3). |

Comandos (en el servidor). Las credenciales de la BD se leen de la configuración de Laravel y se pasan a
`mysqldump` por la variable `MYSQL_PWD` del propio proceso: no se imprimen ni se escriben en disco. El usuario
de la BD solo tiene permiso por socket local, por eso se usa `--protocol=socket` (indicar `-P` fuerza TCP y falla).

```bash
TS="$(date -u +%Y%m%d-%H%M%S)"; B="$HOME/backups/predeploy-$TS"
mkdir -p "$B" && chmod 700 "$B"
cd ~/miapp
tar czf "$B/backend.tar.gz" --exclude=backend/node_modules --exclude='backend/storage/framework/cache/data/*' backend
tar czf "$B/frontend-dist.tar.gz" -C ~/miapp/frontend dist

cd ~/miapp/backend
BOOT='require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $c = config("database.connections.".config("database.default"));'
DB="$(php -r "$BOOT echo \$c['database'];")"; DBUSER="$(php -r "$BOOT echo \$c['username'];")"
MYSQL_PWD="$(php -r "$BOOT echo \$c['password'];")" mysqldump -u "$DBUSER" --protocol=socket \
  --single-transaction --quick --routines --triggers --no-tablespaces "$DB" | gzip > "$B/db.sql.gz"
gzip -t "$B/db.sql.gz" && zcat "$B/db.sql.gz" | tail -1      # debe terminar en "-- Dump completed on ..."

cd "$B" && sha256sum backend.tar.gz frontend-dist.tar.gz db.sql.gz > SHA256SUMS && chmod 600 "$B"/*
```

Copia externa (desde la máquina de desarrollo) y verificación:

```bash
scp -r panificadora:backups/predeploy-$TS ~/ProyectosPersonales/respaldos-panificadora/
cd ~/ProyectosPersonales/respaldos-panificadora/predeploy-$TS && sha256sum -c SHA256SUMS
```

---

## 2. Despliegue

1. **Pruebas en local** (PHP 8.2, igual que producción): `vendor/bin/phpunit` en verde, `composer audit` y
   `npm audit --omit=dev --audit-level=high` sin hallazgos.
2. **Paquete limpio del backend**: solo código. Se excluyen `vendor/`, `node_modules/`, `storage/`, todos los
   `.env*` (salvo `.env.example`), `tests/`, `phpunit.xml`, `bootstrap/cache/*.php`, `public/storage` y `*.sqlite`.
   Se calcula su SHA-256.
3. **Subida y verificación**: `scp` a `/tmp` del servidor y `sha256sum -c` antes de usarlo.
4. **Release aparte**: se extrae en `~/apps/panificadora-releases/<fecha>/backend` y ahí se ejecuta
   `composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts` (versiones de `composer.lock`) y
   `composer audit --locked --no-dev`.
5. **Prueba de arranque** del release con el `.env` de producción enlazado (`php artisan --version`,
   `php artisan route:list`), sin tocar la BD.
6. **Simulación**: `rsync -an --delete --itemize-changes ...` para revisar qué se agrega y qué se borra.
7. **Cambio** (segundos de mantenimiento):
   ```bash
   cd ~/miapp/backend && php artisan down --retry=15
   rsync -a --delete --exclude=/.env --exclude='/.env.*' --exclude=/storage --exclude=/node_modules \
     --exclude=/public/storage ~/apps/panificadora-releases/<fecha>/backend/ ~/miapp/backend/
   php artisan package:discover && php artisan migrate --force
   php artisan config:cache && php artisan route:cache && php artisan queue:restart
   php artisan up
   ```
   Los excludes van **sin barra final** (`/storage`, no `/storage/`) para proteger también los enlaces simbólicos.
8. **Frontend**: `npm ci && npm run build` en local, paquete de `dist/` con SHA-256, extracción en
   `~/miapp/frontend/dist.new-<fecha>`, enlace `api -> ~/miapp/backend/public`, y cambio con dos `mv`
   (el anterior queda como `dist.prev-<fecha>` para revertir al instante).
9. **Verificación** (sección 4).

> `deploy-backend.sh` todavía **no** sigue este procedimiento: empaqueta el backend entero (incluido el `.env`
> local, `vendor/` y `storage/`) y lo extrae encima del clon de `~/apps/panificadora`, por lo que los archivos
> borrados del repositorio no se borran en producción. Hasta actualizarlo, usar los pasos de esta sección.
> `deploy-frontend.sh` ya usa `npm ci`.

---

## 3. Revertir

- **Rápido (código + frontend)**: `bash ~/backups/predeploy-<fecha>/revertir.sh`. Restaura el backend (código y
  `vendor/`) desde `backend.tar.gz` conservando el `.env`, `storage/` y `node_modules/` actuales, regenera las
  cachés y devuelve el `dist` anterior. No toca la base de datos.
- **Base de datos completa** (solo en emergencia; se pierde lo registrado después del respaldo):
  `zcat ~/backups/predeploy-<fecha>/db.sql.gz | mysql -u <usuario> -p <base>`.
- **Restaurar un archivo puntual**: `tar xzf backend.tar.gz -C /tmp/restaurar backend/ruta/al/archivo`.

---

## 4. Verificación posterior

```bash
curl -sI https://panificadoranancy.com/                 # 200 + CSP, X-Frame-Options, HSTS, nosniff
curl -s  https://panificadoranancy.com/api/api/health   # {"status":"ok","details":[]}
curl -s -o /dev/null -w '%{http_code}\n' -H 'Accept: application/json' \
  https://panificadoranancy.com/api/api/inventario/materias-primas   # 401 sin sesión
```

Además: sin `X-Powered-By` en las respuestas, imágenes de productos con 200, y `storage/logs/laravel.log` sin
errores nuevos. Los eventos de seguridad quedan en `storage/logs/security-AAAA-MM-DD.log`.

Nota: LiteSpeed aplica las cabeceras de `frontend/public/.htaccess` a todo el sitio, también a `/api/`, y
sustituye las que pone Laravel con el mismo nombre. Por eso la CSP que se ve en la API es la de la tienda.

---

## 5. Registro de despliegues

### 2026-10-06 — Correcciones de seguridad (auditoría OWASP Top 10:2025 / ASVS L2)

| | |
|---|---|
| Respaldo | `~/backups/predeploy-20261006-185959/` (copia local verificada en `~/ProyectosPersonales/respaldos-panificadora/`) |
| SHA-256 `backend.tar.gz` | `2590c14e58a02b43b238b94d4b57e6a0b14b50818d81ec46a6f73b6885f359b9` |
| SHA-256 `frontend-dist.tar.gz` | `69e7048cc272e4df11c93eb8d18fd544d7ef7bbe541b3286a1149627bbe9c9c2` |
| SHA-256 `db.sql.gz` | `c74352ecb1e45908f1c9b6f6eb70e7196ff3e82019d45e46ee128955087e5bf7` (36 tablas) |
| Estado antes | 4 usuarios, 5 clientes, 9 pedidos, 29 productos, 65 migraciones; Laravel 12.32.5 |
| Paquete backend | 318 archivos, SHA-256 `2cfe540fb430fec1735cc48b082b34cd40d6a3d28c41725d289eb487c61c9bf5` |
| Paquete frontend | SHA-256 `3da6a6d5da809814ef656cb63b7b6718a486223f3b19ec13d7f87697f401b6b7` |
| Mantenimiento backend | 19:03:25 → 19:03:29 UTC (15:03 hora de Bolivia), sin migraciones pendientes |
| Frontend activo | 19:04:45 UTC; el anterior quedó en `~/miapp/frontend/dist.prev-20261006-185959` |
| Versión | Laravel 12.69.3, `composer audit` sin advisories |
| Pruebas | 104 tests PHPUnit en verde (PHP 8.2) |
| Archivos retirados de producción | `test-smtp.php`, `enviar-correos-completos.php`, `tmp_send_mail.php`, `tmp_test_pedido.php`, `public/test_probe.php`, `tests/`, SQLite de desarrollo |
| Verificación | Portada y SPA 200 con CSP/HSTS/XFO/nosniff; `/health` 200; `/inventario` sin sesión 401; venta de mostrador anónima 401; imágenes 200; sin `X-Powered-By`; 0 errores en `laravel.log` |

Detalle de los cambios: `backend/CHANGELOG.md` (entrada 2026-10-06).
