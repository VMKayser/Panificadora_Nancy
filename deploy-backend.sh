#!/bin/bash
set -euo pipefail

BRANCH="perf/queue-emails-n1-fixes"
REMOTE="panificadora"
REMOTE_REPO_DIR="~/apps/panificadora"
REMOTE_DOCROOT="~/miapp/backend"
REPO_URL="https://github.com/VMKayser/Panificadora_Nancy.git"

if ! command -v git >/dev/null 2>&1; then
  echo "[ERROR] git no está instalado en la máquina local." >&2
  exit 1
fi

if ! command -v ssh >/dev/null 2>&1; then
  echo "[ERROR] ssh no está disponible en la máquina local." >&2
  exit 1
fi

if ! command -v scp >/dev/null 2>&1; then
  echo "[ERROR] scp no está disponible en la máquina local." >&2
  exit 1
fi

cleanup() {
  rm -f /tmp/backend-panificadora.tar.gz
}
trap cleanup EXIT

echo "→ Preparando branch local $BRANCH"
git checkout "$BRANCH"

if [ -z "${SKIP_GIT_SYNC:-}" ]; then
  git fetch origin "$BRANCH"
  git pull origin "$BRANCH"
else
  echo "   (SKIP_GIT_SYNC=1) Omitiendo fetch/pull; se usará el estado actual del repositorio local"
fi

echo "→ Empaquetando backend"
tar czf /tmp/backend-panificadora.tar.gz backend

echo "→ Subiendo paquete al servidor"
scp /tmp/backend-panificadora.tar.gz "$REMOTE":/tmp/backend-panificadora.tar.gz

echo "→ Ejecutando despliegue remoto"
ssh "$REMOTE" bash -s -- "${SKIP_GIT_SYNC:-}" <<'EOF'
SKIP_GIT_SYNC="${1:-}"
shift || true
set -euo pipefail
BRANCH="perf/queue-emails-n1-fixes"
REMOTE_REPO_DIR="$HOME/apps/panificadora"
REMOTE_DOCROOT="$HOME/miapp/backend"
REPO_URL="https://github.com/VMKayser/Panificadora_Nancy.git"
PACKAGE="/tmp/backend-panificadora.tar.gz"

mkdir -p "$REMOTE_REPO_DIR"
cd "$REMOTE_REPO_DIR"

if [ -z "$SKIP_GIT_SYNC" ]; then
  if [ -d .git ]; then
    git fetch origin "$BRANCH"
    git checkout "$BRANCH"
    git pull origin "$BRANCH"
  else
    if [ "$(ls -A .)" ]; then
      echo "[ERROR] $REMOTE_REPO_DIR existe pero no es un repositorio Git. Limpia el directorio o apunta REMOTE_REPO_DIR a otra ruta." >&2
      exit 2
    fi
    git clone "$REPO_URL" .
    git checkout "$BRANCH"
  fi
else
  echo "   (SKIP_GIT_SYNC=1) Omitiendo sincronización de Git en el servidor; se usará el paquete subido"
fi

if [ -f "$PACKAGE" ]; then
  tar xzf "$PACKAGE" -C "$REMOTE_REPO_DIR"
  rm -f "$PACKAGE"
fi

mkdir -p "$REMOTE_DOCROOT"

echo "→ Sincronizando hacia el docroot"
rsync -av --delete \
  --exclude=.env \
  --exclude=storage/logs \
  --exclude=storage/app/public \
  --exclude=storage/framework/cache/data \
  --exclude=storage/framework/sessions \
  "$REMOTE_REPO_DIR/backend/" "$REMOTE_DOCROOT/"

cd "$REMOTE_DOCROOT"

rm -f bootstrap/cache/config.php \
  bootstrap/cache/packages.php \
  bootstrap/cache/services.php || true

if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts
else
  echo "[ADVERTENCIA] Composer no está instalado en el servidor." >&2
fi

if command -v php >/dev/null 2>&1; then
  php artisan migrate --force
  php artisan config:cache
  php artisan route:cache
  php artisan queue:restart || true
else
  echo "[ADVERTENCIA] PHP no está disponible en el servidor." >&2
fi

# Hostinger tiene deshabilitado `proc_open/exec`, por lo que `php artisan storage:link`
# falla. Re-creamos manualmente el symlink cada vez para asegurar que apunte
# al storage del release actual.
STORAGE_TARGET="$REMOTE_DOCROOT/storage/app/public"
STORAGE_LINK="$REMOTE_DOCROOT/public/storage"
if [ -e "$STORAGE_LINK" ] || [ -L "$STORAGE_LINK" ]; then
  rm -rf "$STORAGE_LINK"
fi
ln -s "$STORAGE_TARGET" "$STORAGE_LINK"

find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

echo "→ Despliegue remoto completado"
EOF

echo "✅ Deploy finalizado"