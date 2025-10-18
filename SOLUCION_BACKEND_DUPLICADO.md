# 🔧 Solución: Unificación del Backend y Configuración Correcta

## ❌ Problema Identificado

Tenías **2 servidores PHP corriendo simultáneamente** en el puerto 8000:
- Proceso 178992: `php artisan serve`
- Proceso 193942: `php artisan serve`

Además, el frontend estaba configurado con `VITE_API_URL=/api` (ruta **relativa**), lo que hacía que intentara llamar al backend en el mismo servidor donde corre Vite (puerto 5174), no en el backend real.

## ✅ Solución Aplicada

### 1. **Detener procesos duplicados**
```bash
kill 178992 193942
```

### 2. **Usar Docker Compose (recomendado)**
El backend ahora corre en **puerto 80** usando Docker Compose, que es más robusto y profesional:

```bash
cd backend
docker compose up -d
```

**Contenedores corriendo:**
- `backend-laravel.test-1` → Backend Laravel (puerto 80)
- `backend-mysql-1` → Base de datos MySQL (puerto 3306)
- `backend-redis-1` → Redis para cache (puerto 6379)
- `backend-phpmyadmin-1` → PHPMyAdmin (puerto 8080)

### 3. **Corregir configuración del Frontend**

**Antes (❌ incorrecto):**
```env
VITE_API_URL=/api
```

**Después (✅ correcto):**
```env
VITE_API_URL=http://localhost/api
```

### 4. **Reiniciar Frontend**
```bash
cd frontend
npm run dev
```

---

## 🚀 Cómo Iniciar el Sistema Correctamente

### **Opción A: Docker Compose (Recomendado)**

```bash
# 1. Iniciar backend con Docker
cd backend
docker compose up -d

# 2. Verificar que esté corriendo
docker ps

# 3. Iniciar frontend
cd ../frontend
npm run dev
```

**URLs:**
- Frontend: http://localhost:5174/app/
- Backend API: http://localhost/api
- PHPMyAdmin: http://localhost:8080

---

### **Opción B: PHP Artisan Serve (Solo desarrollo rápido)**

```bash
# 1. Iniciar MySQL (si no está con Docker)
docker compose up -d mysql

# 2. Iniciar backend en puerto 8000
cd backend
php artisan serve --host=0.0.0.0 --port=8000

# 3. Actualizar frontend/.env
VITE_API_URL=http://localhost:8000/api

# 4. Iniciar frontend
cd frontend
npm run dev
```

---

## 🔍 Script de Verificación

Ejecuta este comando para verificar que todo esté configurado correctamente:

```bash
bash verificar-conexion.sh
```

**Salida esperada:**
```
✅ Backend Laravel corriendo
✅ MySQL corriendo
✅ API respondiendo correctamente (HTTP 200)
✅ Frontend corriendo en puerto 5174
✅ Configuración correcta
```

---

## 📝 Comandos Útiles

### Ver logs del backend (Docker)
```bash
docker compose logs -f laravel.test
```

### Detener todo
```bash
# Backend
docker compose down

# Frontend (Ctrl+C en la terminal donde corre npm run dev)
```

### Reiniciar solo el backend
```bash
docker compose restart laravel.test
```

### Ver procesos corriendo
```bash
# Ver contenedores Docker
docker ps

# Ver procesos PHP
ps aux | grep php | grep -v grep

# Ver qué está usando el puerto 80
sudo lsof -i :80
```

---

## ⚠️ Problemas Comunes

### Frontend no se conecta al backend

**Síntoma:** "Failed to fetch" o errores 401/404

**Solución:**
1. Verifica que `frontend/.env` tenga: `VITE_API_URL=http://localhost/api`
2. Reinicia el frontend: `Ctrl+C` y luego `npm run dev`
3. Verifica que el backend responda: `curl http://localhost/api/productos`

### Puerto 80 ya está en uso

**Solución:**
```bash
# Ver qué está usando el puerto
sudo lsof -i :80

# Detener Apache/Nginx si está corriendo
sudo systemctl stop apache2
# o
sudo systemctl stop nginx
```

### No aparece el carrusel ni productos

**Causa:** El frontend está llamando al backend incorrecto o el backend no responde

**Solución:**
1. Abre la consola del navegador (F12)
2. Busca errores en la pestaña "Network"
3. Verifica que las peticiones vayan a `http://localhost/api/...`
4. Si van a otro lado (ej: `/api/...` sin dominio), el `.env` está mal

---

## 🎯 Configuración Recomendada de Producción

Para **producción** (cuando tengas el dominio configurado):

```env
# backend/.env
APP_URL=https://panificadoranancy.com
FRONTEND_URL=https://panificadoranancy.com

# frontend/.env
VITE_API_URL=https://panificadoranancy.com/api
```

---

## 📊 Estado Actual del Sistema

✅ **Backend:** Corriendo en Docker (puerto 80)
✅ **MySQL:** Corriendo en Docker (puerto 3306)
✅ **Redis:** Corriendo en Docker (puerto 6379)
✅ **Frontend:** Configurado para apuntar a `http://localhost/api`

**Próximos pasos:**
1. ✅ Verificar que el carrusel aparezca en el frontend
2. ✅ Probar login/registro
3. ✅ Probar ventas en el POS (VendedorPanel)
4. ⏳ Configurar email verification cuando tengas dominio

---

**Fecha:** 15 de octubre de 2025  
**Problema resuelto:** Unificación de backend y corrección de configuración del frontend
