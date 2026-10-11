#!/usr/bin/env bash
# Copia de la base de la panificadora en DATOS/respaldos. Guarda las últimas 14.
#   bash deploy/vps/respaldar.sh
#
# La corre cron cada noche (ver README.md). Las imágenes de productos
# (DATOS/storage) no entran: cambian poco y las cubre el snapshot del VPS.
set -euo pipefail
cd "$(dirname "$0")"
# El volcado trae datos de clientes: solo root lo lee.
umask 077

GUARDAR=14
DATOS="$(sed -n 's/^DATOS=//p' .env)"
mkdir -p "$DATOS/respaldos"
chmod 700 "$DATOS/respaldos"

destino="$DATOS/respaldos/base-$(date +%F-%H%M).sql.gz"
temporal="${destino}.parcial"
# Un volcado que falla a mitad no deja un archivo que parezca una copia buena.
trap 'rm -f "$temporal"' EXIT

docker compose exec -T db sh -c 'exec mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" \
  --single-transaction --quick --routines --triggers panificadora' | gzip > "$temporal"
zcat "$temporal" | tail -1 | grep -q "Dump completed" || { echo "volcado incompleto" >&2; exit 1; }
mv "$temporal" "$destino"

# Las más viejas fuera, quedan las últimas $GUARDAR.
ls -1t "$DATOS"/respaldos/base-*.sql.gz | tail -n +$((GUARDAR + 1)) | xargs -r rm --

echo "$(date '+%F %T') copia ${destino} ($(du -h "$destino" | cut -f1))"
