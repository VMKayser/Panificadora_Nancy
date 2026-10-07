# Panificadora Nancy

Monorepo que contiene:
- `backend/` - Laravel API
- `frontend/` - React + Vite frontend

## Despliegue rápido

> **Antes de desplegar, respalda.** El procedimiento de respaldo, despliegue, verificación y reversión que se usa en
> producción está en [`deploy/production/RESPALDOS-Y-DESPLIEGUE.md`](deploy/production/RESPALDOS-Y-DESPLIEGUE.md),
> junto con el registro de cada despliegue. Los cambios de seguridad están en `backend/CHANGELOG.md`.

### Backend (Hostinger / servidor remoto)

- Asegúrate de tener acceso SSH configurado como `panificadora` (puedes ajustar el usuario editando `deploy-backend.sh`).
- Desde la raíz del repo ejecuta:

```bash
chmod +x deploy-backend.sh
./deploy-backend.sh
```

El script crea un paquete del backend, lo sube al servidor y sincroniza en `~/miapp/backend` (por defecto), ejecutando `composer install`, migraciones y refrescando caches. Puedes establecer `SKIP_GIT_SYNC=1` si no deseas hacer `git fetch/pull` durante el deploy.

### Frontend (Vite build estático)

1. Ejecuta el script para compilar y subir el build:

```bash
chmod +x deploy-frontend.sh
./deploy-frontend.sh
```

2. Por defecto el script copia el contenido de `frontend/dist` a `~/miapp/frontend/dist` del servidor `panificadora` (el docroot del dominio, con el enlace `api` hacia el backend). Si tu hosting usa otra ruta, sobrescribe al vuelo:

```bash
REMOTE_FRONTEND_DIR="/home/usuario/domains/midominio.com/public_html" ./deploy-frontend.sh
```

Variables útiles:

- `SKIP_GIT_SYNC=1` evita `git fetch/pull` local.
- `SKIP_NPM_INSTALL=1` salta `npm ci` (usa cuando ya instalaste deps).
- `REMOTE_FRONTEND_DIR=/ruta` define el docroot remoto donde se extraerá el build.

Requisitos:
- Node 22.x
- PHP 8.2 (la versión de producción; `composer.json` fija `platform.php` en 8.2.33)
- Composer
- Docker (opcional, Sail)

Instrucciones rápidas de desarrollo

Backend (Laravel):

```bash# comprobar listener en 3306
sudo ss -tulpn | grep -E ':3306\b' || true

# o con lsof
sudo lsof -i :3306 -sTCP:LISTEN -Pn || true
cd backend
composer install
cp .env.example .env
# Ajusta .env (DB, etc.)
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

Frontend (React + Vite):

```bash
cd frontend
npm install
npm run dev
# Abre http://localhost:5173
```

Notas:
- Las variables sensibles no deben subirse al repo (.env está en .gitignore)
- Para producción, construir el frontend y entregar los archivos estáticos

Contribuir

- Crear ramas por feature: `git checkout -b feat/nueva-funcion`
- Hacer PR y asignar revisores
- Mantener ramas actualizadas con `main`
