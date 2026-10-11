#!/bin/sh
# Arranque de los contenedores de Laravel (app, cola y scheduler).
#
# storage/ y .env llegan montados desde /srv/panificadora-datos. Se corre como
# root solo para dejar storage/ a nombre de www-data; Laravel corre siempre
# como www-data, así los logs y la caché que escribe la cola los puede leer y
# escribir también php-fpm, y al revés.
set -eu
cd /var/www/backend

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs
chown -R www-data:www-data storage

if [ "$1" = "php-fpm" ]; then
  # Solo el contenedor web migra y arma las cachés: la cola y el scheduler
  # esperan a que esté sano (ver docker-compose.yml) y leen el .env directo.
  su-exec www-data php artisan package:discover --ansi
  su-exec www-data php artisan migrate --force
  su-exec www-data php artisan config:cache
  su-exec www-data php artisan route:cache
  su-exec www-data php artisan view:cache
  exec "$@"
fi

exec su-exec www-data "$@"
