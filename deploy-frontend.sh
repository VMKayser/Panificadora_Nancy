#!/bin/bash
set -euo pipefail

# === Configuración ===
# Cambia estos valores si tu host usa otro usuario o rutas.
BRANCH="perf/queue-emails-n1-fixes"
REMOTE="panificadora"
FRONTEND_DIR="frontend"
DIST_DIR="$FRONTEND_DIR/dist"
PACKAGE="/tmp/frontend-panificadora.tar.gz"

# Directorio de destino en el servidor (puedes sobrescribirlo con REMOTE_FRONTEND_DIR=/ruta ./deploy-frontend.sh)
DEFAULT_REMOTE_TARGET="\$HOME/miapp/frontend/dist"

# === Validaciones previas ===
if [ ! -d "$FRONTEND_DIR" ]; then
  echo "[ERROR] No encontré el directorio $FRONTEND_DIR. Ejecuta el script desde la raíz del repo." >&2
  exit 1
fi

if ! command -v git >/dev/null 2>&1; then
  echo "[ERROR] git no está instalado en la máquina local." >&2
  exit 1
fi

if ! command -v npm >/dev/null 2>&1; then
  echo "[ERROR] npm no está instalado en la máquina local." >&2
  exit 1
fi

if ! command -v ssh >/dev/null 2>&1 || ! command -v scp >/dev/null 2>&1; then
  echo "[ERROR] Necesitas ssh y scp para desplegar." >&2
  exit 1
fi

cleanup() {
  rm -f "$PACKAGE"
}
trap cleanup EXIT

# === Paso 1: asegurar rama correcta ===
echo "→ Preparando branch local $BRANCH"
git checkout "$BRANCH"
if [ -z "${SKIP_GIT_SYNC:-}" ]; then
  git fetch origin "$BRANCH"
  git pull origin "$BRANCH"
else
  echo "   (SKIP_GIT_SYNC=1) Omitiendo fetch/pull; se usará el estado actual del repositorio"
fi

# === Paso 2: construir el frontend ===
echo "→ Instalando dependencias y construyendo el frontend"
pushd "$FRONTEND_DIR" >/dev/null
if [ -z "${SKIP_NPM_INSTALL:-}" ]; then
  # npm ci instala exactamente lo del package-lock.json (con verificación de integridad)
  npm ci --no-audit --no-fund
else
  echo "   (SKIP_NPM_INSTALL=1) Omitiendo npm install; se asumirá que node_modules está actualizado"
fi
npm run build
popd >/dev/null

if [ ! -d "$DIST_DIR" ]; then
  echo "[ERROR] No se generó la carpeta dist. Revisa los logs de npm run build." >&2
  exit 1
fi

# === Paso 3: empaquetar dist ===
echo "→ Empaquetando dist"
tar czf "$PACKAGE" -C "$DIST_DIR" .

# === Paso 4: subir y desplegar en el servidor ===
echo "→ Subiendo paquete al servidor"
scp "$PACKAGE" "$REMOTE":"$PACKAGE"

REMOTE_SCRIPT='set -euo pipefail
PACKAGE="'$PACKAGE'"
TARGET_DIR="'${REMOTE_FRONTEND_DIR:-$DEFAULT_REMOTE_TARGET}'"

echo "→ Destino remoto: $TARGET_DIR"
mkdir -p "$TARGET_DIR"
rm -rf "$TARGET_DIR"/*
tar xzf "$PACKAGE" -C "$TARGET_DIR"
rm -f "$PACKAGE"

echo "→ Frontend desplegado en $TARGET_DIR"

# Crear symlink 'api' apuntando al backend
echo "→ Creando symlink para API"
ln -sf /home/u308901279/miapp/backend/public "$TARGET_DIR/api"
'

echo "→ Ejecutando sincronización remota"
ssh "$REMOTE" "$REMOTE_SCRIPT"

echo "✅ Deploy del frontend terminado"
