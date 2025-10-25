Panificadora Nancy - Despliegue producción

Resumen rápido
-------------
Este directorio contiene ejemplos y pasos para desplegar la aplicación en producción.

Checklist rápido
----------------
1. Copia `deploy/production/.env.production.example` a `backend/.env.production` y llena las credenciales.
2. Configura el servidor web (nginx) usando `deploy/production/nginx.conf` y ajusta rutas y sockets PHP-FPM.
3. Instala Redis y configura `REDIS_HOST` en `.env.production`.
4. Instala composer dependencies: `composer install --no-dev --optimize-autoloader`.
5. Ejecuta migraciones: `php artisan migrate --force`.
6. Cachea config/rutas: `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
7. Construye frontend: `cd frontend && npm ci && npm run build`.
8. Crea `storage:link` si fuera necesario: `php artisan storage:link`.
9. Configura worker de colas usando systemd (archivo `queue-worker.service`) o supervisor (`supervisor-queue.conf`).
10. Configura cron job: `* * * * * php /var/www/panificadora/artisan schedule:run >> /dev/null 2>&1`.
11. Configura SSL (Let's Encrypt) y habilita HTTP/2.

Comandos útiles
---------------
# Desde carpeta backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link

# Frontend
cd frontend
npm ci
npm run build

Systemd (worker)
-----------------
# Copia deploy/production/queue-worker.service a /etc/systemd/system/queue-worker.service
sudo systemctl daemon-reload
sudo systemctl enable --now queue-worker.service

Supervisor
----------
# Copia deploy/production/supervisor-queue.conf a /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start panificadora-queue

Healthcheck
-----------
Coloca `deploy/production/healthcheck.php` en `public/healthcheck.php` o configura la ruta `/health`. Verifica que devuelve 200.

Backup
------
Implementa respaldos periódicos de la DB (mysqldump) y del directorio `storage/`. Guarda en S3 o disco remoto.

Notas de seguridad
------------------
- APP_DEBUG=false en producción.
- Mantén `backend/.env.production` fuera del repo. Usa un secreto o vault.
- Revisa `whatsapp` y `mail` credentials y permisos.

Problemas conocidos y recomendaciones
------------------------------------
- Para servidores con 1GB RAM, usar Redis para cache/queue/session y limitar worker memory con `--memory`.
- Evitar `php artisan queue:work` sin `--memory` en sistemas con poca RAM.
- Si usas la API de WhatsApp Cloud, revisa plantillas y límites de mensajes.

Soporte
-------
Si quieres, puedo automatizar la creación de los archivos systemd/supervisor adaptados a tu ruta de instalación y puedo añadir un script de deploy que ejecute los pasos anteriores.
