#!/usr/bin/env bash
set -euo pipefail

# Deploy seguro para producción - no ejecuta nada que requiera credenciales externas
# Uso: desde el directorio backend: sudo ./scripts/deploy_production.sh

# 1) Verificar archivo .env.production
ENV_FILE=.env.production
if [ ! -f "$ENV_FILE" ]; then
  echo "ERROR: $ENV_FILE no encontrado. Crea .env.production con variables de producción." >&2
  exit 2
fi

echo "1/9 Verificando .env.production..."
# Comprobaciones básicas
if grep -q "APP_DEBUG=true" "$ENV_FILE"; then
  echo "ADVERTENCIA: APP_DEBUG=true en .env.production — cambiando a false"
  sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" "$ENV_FILE"
fi

# 2) Composer install (sin dev) y optimizaciones
echo "2/9 Ejecutando composer install --no-dev --optimize-autoloader"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 3) Migraciones y seeders (opcional: comentar si prefieres manual)
echo "3/9 Ejecutando migraciones (php artisan migrate --force)"
php artisan migrate --force

# 4) Generar enlaces de storage
echo "4/9 Ejecutando storage:link"
php artisan storage:link || true

# 5) Construir frontend (asume que frontend/dist está ya generado o se hará en CI)
echo "5/9 Sugerido: construir frontend en el pipeline CI/CD y copiar a public/app"
# Opcional (descomenta si quieres construir aquí y tienes node disponible)
# cd ../frontend && npm ci --production && npm run build && cd -
# rm -rf public/app && cp -r ../frontend/dist public/app

# 6) Cachear configuración, rutas y vistas
echo "6/9 Cacheando config, rutas y vistas"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7) Limpiar caché de la aplicación
echo "7/9 Limpiando cachés y optimizaciones finales"
php artisan cache:clear
php artisan optimize:clear
php artisan optimize

# 8) Ajustar permisos (sólo sugiere, ejecutar con sudo en servidor si es necesario)
echo "8/9 Ajuste de permisos sugerido (ejecuta como usuario del servidor)"
echo "sudo chown -R www-data:www-data storage bootstrap/cache public/storage || true"

# 9) Reiniciar queue workers / servicios (si aplica)
echo "9/9 Reinicia workers si usas queues (p.ej. php artisan queue:restart)"

echo "Despliegue finalizado. Revisa logs con: php artisan tail:logs (si tienes un comando) o tail -f storage/logs/laravel.log"

echo "NOTA: Revisa FRONTEND_URL y SANCTUM_STATEFUL_DOMAINS en .env.production y asegúrate de que CORS en config/cors.php permita sólo tu dominio de producción"

exit 0
