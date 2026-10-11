# syntax=docker/dockerfile:1
#
# Imágenes de Panificadora Nancy para el VPS (ver deploy/vps/README.md).
# Contexto: la raíz del repo. Dos imágenes salen de aquí:
#   app  -> Laravel en php-fpm (también corre la cola y el scheduler)
#   web  -> nginx con el frontend compilado y el public/ del backend en /api,
#           igual que en Hostinger (docroot = dist, con api -> backend/public)

# --- Frontend: build estático de Vite -----------------------------------------
FROM node:22-alpine AS frontend
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY frontend/ ./
# frontend/.env.production no está en git: el valor de producción va aquí.
ENV VITE_API_URL=/api/api
RUN npm run build

# --- Backend: Laravel en php-fpm ----------------------------------------------
# PHP 8.2, la misma versión que fija composer.json (platform.php).
FROM php:8.2-fpm-alpine AS app
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
# gd con webp: las miniaturas de productos (app/Support/Miniaturas.php).
RUN install-php-extensions pdo_mysql gd intl bcmath pcntl zip exif opcache \
 && apk add --no-cache su-exec
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY deploy/vps/php.ini /usr/local/etc/php/conf.d/zz-panificadora.ini
COPY deploy/vps/php-fpm.conf /usr/local/etc/php-fpm.d/zz-panificadora.conf

WORKDIR /var/www/backend
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-scripts --no-autoloader --no-interaction
COPY backend/ ./
# storage/ llega montado desde el VPS (imágenes, logs, sesiones); lo de pruebas
# y desarrollo no entra a producción.
RUN composer dump-autoload --optimize --no-dev --no-scripts \
 && rm -rf storage tests phpunit.xml .env .env.* public/storage bootstrap/cache/*.php \
           node_modules database/*.sqlite compose.yaml \
 && mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data bootstrap/cache

COPY deploy/vps/entrypoint.sh /usr/local/bin/panificadora-entrypoint
ENTRYPOINT ["panificadora-entrypoint"]
CMD ["php-fpm"]

# --- Web: nginx delante de php-fpm --------------------------------------------
FROM nginx:1.27-alpine AS web
COPY deploy/vps/nginx.conf /etc/nginx/conf.d/default.conf
COPY deploy/vps/cabeceras.conf /etc/nginx/cabeceras.conf
COPY --from=frontend /app/dist /usr/share/nginx/html
# Solo lo estático de public/; ningún .php, que aquí se serviría como texto.
COPY --from=app /var/www/backend/public /usr/share/nginx/html/api
RUN find /usr/share/nginx/html/api -name '*.php' -delete
