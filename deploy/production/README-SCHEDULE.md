Uso recomendado del scheduler

Puedes usar systemd timer o cron. Recomiendo systemd timer por mejor control y menor overhead.

Instalación (systemd timer):

# Copia los archivos a /etc/systemd/system/
sudo cp deploy/production/schedule-run.service /etc/systemd/system/schedule-run.service
sudo cp deploy/production/schedule-run.timer /etc/systemd/system/schedule-run.timer

# Recargar systemd y habilitar
sudo systemctl daemon-reload
sudo systemctl enable --now schedule-run.timer

# Verificar estado
sudo systemctl status schedule-run.timer
sudo journalctl -u schedule-run.service -f

Alternativa (cron):
* * * * * www-data php /var/www/panificadora/artisan schedule:run >> /dev/null 2>&1

Notas:
- Ajusta WorkingDirectory y rutas si tu instalación está en otro path.
- En servidores con 1GB RAM systemd timers consumen menos CPU/overhead que cron en algunos casos y permiten reinicios y logging centralizados.
