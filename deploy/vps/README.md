# Panificadora Nancy en el VPS

Desde octubre de 2026 la panificadora ya no está en el hosting compartido de
Hostinger (venció el plan) sino en el VPS de RutaStar (Hostinger KVM 1,
`ssh tracking`), con el mismo dominio `panificadoranancy.com`.

```
internet ─► Caddy de RutaStar (80/443, certificados)
              ├─ rutastar.tech          ─► RutaStar
              └─ panificadoranancy.com  ─► web (nginx) ─► app (php-fpm) ─► db (MariaDB 11.8)
                                                          cola, scheduler
```

| Qué | Dónde (VPS) |
|---|---|
| Código (clon de este repo) | `/srv/panificadora`, compose en `deploy/vps/` |
| `.env` de Laravel (APP_KEY, correo, BD) | `/srv/panificadora-datos/laravel.env` |
| `storage/` (imágenes de productos, logs) | `/srv/panificadora-datos/storage` |
| Base de datos | volumen Docker `panificadora_db` |
| Copias diarias de la base | `/srv/panificadora-datos/respaldos` |
| Sitio en Caddy (dominio, `www` y `api`) | `/srv/tracking/deploy/sitios/panificadora.caddy` |

Mismo esquema de URLs que en Hostinger: la tienda en `/` y Laravel en `/api`
(la API queda en `/api/api/...`, las imágenes en `/api/storage/...`). Por eso
no cambió nada en el frontend ni en las URLs guardadas en la base.

## Instalación (una sola vez)

Los comandos de los pasos 1–3 y 5 se corren en el VPS, como root.

1. Clonar (el repo es público, no hace falta llave):
   ```bash
   git clone -b deploy/vps-docker https://github.com/VMKayser/Panificadora_Nancy.git /srv/panificadora
   ```
2. Subir el respaldo de Hostinger desde la máquina de desarrollo:
   ```bash
   scp -r ~/ProyectosPersonales/respaldos-panificadora/predeploy-20261006-185959 tracking:/root/
   ```
3. Restaurar y levantar. Crea `/srv/panificadora-datos`, el `.env` del compose
   con contraseñas nuevas para MariaDB, carga la base y construye las imágenes:
   ```bash
   bash /srv/panificadora/deploy/vps/restaurar-hostinger.sh /root/predeploy-20261006-185959
   ```
4. En el panel de Hostinger (Dominios → panificadoranancy.com → DNS), cambiar
   los registros **A** de `@` y de `api` a la IP del VPS (`179.199.151.87`).
   `www` es un CNAME a `@` y sigue solo; `api` hace falta porque hay imágenes
   guardadas en la base con ese subdominio. Esperar a que se vea:
   ```bash
   dig +short panificadoranancy.com api.panificadoranancy.com   # las dos: 179.199.151.87
   ```
5. Recién entonces, el sitio en Caddy (sin cortar RutaStar):
   ```bash
   cp /srv/panificadora/deploy/vps/panificadora.caddy /srv/tracking/deploy/sitios/
   cd /srv/tracking && docker compose exec caddy caddy reload --config /etc/caddy/Caddyfile
   ```
   Caddy saca el certificado en unos segundos.
6. Copia diaria de la base (03:45, después de la de RutaStar):
   ```bash
   (crontab -l; echo '45 3 * * * bash /srv/panificadora/deploy/vps/respaldar.sh >> /var/log/panificadora-respaldo.log 2>&1') | crontab -
   ```

## Verificar

```bash
curl -sI https://panificadoranancy.com/                 # 200 + CSP, HSTS, nosniff
curl -s  https://panificadoranancy.com/api/api/health   # {"status":"ok",...}
curl -s -o /dev/null -w '%{http_code}\n' -H 'Accept: application/json' \
  https://panificadoranancy.com/api/api/inventario/materias-primas   # 401 sin sesión
cd /srv/panificadora/deploy/vps && docker compose ps && docker compose logs --tail=50 app cola
tail -n 50 /srv/panificadora-datos/storage/logs/laravel.log
```

## Actualizar

```bash
cd /srv/panificadora && git pull
cd deploy/vps && bash respaldar.sh && docker compose up -d --build
```

`app` migra al arrancar. Para volver a la versión anterior: `git checkout <commit>`
y otra vez `docker compose up -d --build`.

## Volver al hosting compartido

Si se renueva el plan de Hostinger: `bash respaldar.sh`, cargar ese volcado en
la base de Hostinger y apuntar otra vez el registro A a `62.72.62.77`. Luego
`rm /srv/tracking/deploy/sitios/panificadora.caddy`, recargar Caddy y
`docker compose down` aquí.

## Pendiente

- **El dominio vence el 2026-10-23** (lo registra Hostinger). Renovarlo aparte
  del plan de hosting, o la tienda se cae otra vez.
- **Correo**: el `.env` manda por `smtp.hostinger.com` con
  `nancy@panificadoranancy.com`, que venía con el plan. El 2026-10-10, ya
  vencido el plan, todavía autenticaba; no tocar los registros MX. Si el correo
  de Hostinger deja de funcionar, los avisos de pedidos fallan (la tienda sigue;
  los trabajos quedan en `failed_jobs`). Opciones: renovar solo el correo, o
  un SMTP externo (Brevo, Gmail con contraseña de aplicación) en `laravel.env`
  y `docker compose restart app cola scheduler` para que lo lean.
- Los pedidos hechos en Hostinger después del respaldo del 2026-10-06 no
  están en esta base.
