#!/usr/bin/env bash
# Primera instalación en el VPS a partir de un respaldo de Hostinger
# (una carpeta predeploy-<fecha> de deploy/production/RESPALDOS-Y-DESPLIEGUE.md).
#
#   bash deploy/vps/restaurar-hostinger.sh /root/predeploy-20261006-185959
#
# Del respaldo toma el .env de producción (APP_KEY, correo), storage/ (las
# imágenes de productos) y el volcado de la base. Crea DATOS, el .env del
# compose con contraseñas nuevas para MariaDB, carga la base y levanta todo.
# No imprime ningún secreto. Se niega a correr si DATOS ya tiene un .env, para
# no pisar una instalación que ya funciona.
set -euo pipefail

RESPALDO="${1:?uso: $0 <carpeta del respaldo predeploy-...>}"
RESPALDO="$(cd "$RESPALDO" && pwd)"
DATOS="${DATOS:-/srv/panificadora-datos}"
cd "$(dirname "$0")"

if [ -e "$DATOS/laravel.env" ]; then
  echo "Ya existe $DATOS/laravel.env: la panificadora ya está instalada aquí." >&2
  exit 1
fi

echo "→ Verificando el respaldo"
(cd "$RESPALDO" && sha256sum -c SHA256SUMS)

echo "→ Datos en $DATOS"
mkdir -p "$DATOS"
chmod 700 "$DATOS"
temporal="$(mktemp -d)"
trap 'rm -rf "$temporal"' EXIT
tar xzf "$RESPALDO/backend.tar.gz" -C "$temporal" backend/.env backend/storage
mv "$temporal/backend/storage" "$DATOS/storage"
# Cachés, vistas compiladas y sesiones de Hostinger no sirven aquí.
rm -rf "$DATOS/storage/framework/cache/data/"* "$DATOS/storage/framework/views/"* \
       "$DATOS/storage/framework/sessions/"*
echo "   $(find "$DATOS/storage/app/public" -type f | wc -l) archivos en storage/app/public"

# Contraseñas nuevas: las de Hostinger eran de su MariaDB, que ya no existe.
db_password="$(openssl rand -hex 24)"
db_root_password="$(openssl rand -hex 24)"

install -m 600 "$temporal/backend/.env" "$DATOS/laravel.env"
sed -i -E \
  -e 's|^DB_HOST=.*|DB_HOST=db|' \
  -e 's|^DB_PORT=.*|DB_PORT=3306|' \
  -e 's|^DB_DATABASE=.*|DB_DATABASE=panificadora|' \
  -e 's|^DB_USERNAME=.*|DB_USERNAME=panificadora|' \
  -e "s|^DB_PASSWORD=.*|DB_PASSWORD=${db_password}|" \
  "$DATOS/laravel.env"
# 82 es www-data en las imágenes alpine de PHP: la cola y el scheduler leen
# este .env como www-data.
chown 82:82 "$DATOS/laravel.env"

umask 077
cat > .env <<EOF
# Lo crea restaurar-hostinger.sh. No va a git.
DATOS=$DATOS
DB_PASSWORD=$db_password
DB_ROOT_PASSWORD=$db_root_password
EOF

echo "→ Levantando MariaDB"
docker compose up -d --wait db

echo "→ Cargando la base"
zcat "$RESPALDO/db.sql.gz" | docker compose exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" panificadora'
# Trabajos que quedaron en la cola de Hostinger: mandarlos ahora sería avisar
# tarde de pedidos viejos.
docker compose exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" panificadora -e "DELETE FROM jobs"'

echo "→ Filas cargadas (compárelas con estado-antes.txt del respaldo)"
docker compose exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" panificadora -N -e "
  SELECT \"users\", COUNT(*) FROM users UNION ALL
  SELECT \"clientes\", COUNT(*) FROM clientes UNION ALL
  SELECT \"pedidos\", COUNT(*) FROM pedidos UNION ALL
  SELECT \"productos\", COUNT(*) FROM productos UNION ALL
  SELECT \"migrations\", COUNT(*) FROM migrations"'

echo "→ Construyendo y levantando la aplicación (tarda unos minutos la primera vez)"
docker compose up -d --build --wait

echo "Listo. Falta el sitio en Caddy: ver deploy/vps/README.md, paso 4."
