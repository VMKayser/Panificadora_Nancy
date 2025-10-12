# ✅ Solución Login - Panificadora Nancy

## Problemas Identificados y Resueltos

### 1. ❌ Problema: Base de datos
- **Error**: El `.env` estaba configurado para SQLite pero el sistema usa MySQL (Docker Sail)
- **Solución**: Restaurada configuración MySQL en `.env`

### 2. ❌ Problema: Columnas faltantes en BD
- **Error**: Seeders intentaban insertar en columnas `orden` y `tiene_limite_produccion` que no existían
- **Solución**: Creada migración de compatibilidad `2025_10_11_000001_add_compat_columns.php`

### 3. ❌ Problema: Navegación en AuthContext
- **Error**: El AuthContext navegaba a `/login` incluso cuando no había token (páginas públicas)
- **Solución**: Removida navegación automática, solo limpia storage en caso de error 401

### 4. ✅ Rutas del frontend
- **Configurado**: React Router usa `basename` de Vite (`/app/`)
- **Configurado**: Interceptor de axios NO hace `window.location.href` (evita recargas)

## Cambios Aplicados

### Backend (.env)
```bash
DB_CONNECTION=mysql      # ✅ Restaurado
DB_HOST=mysql           # ✅ Restaurado
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

### Frontend (AuthContext.jsx)
```javascript
// ✅ Ya NO navega automáticamente cuando falla la carga inicial
// ✅ Solo limpia localStorage si hay error 401
// ✅ Permite páginas públicas sin token
```

### Frontend (App.jsx)
```javascript
// ✅ Router con basename correcto
const basename = rawBase.replace(/\/$/, '') || '/';
<Router basename={basename}>
```

### Frontend (services/api.js)
```javascript
// ✅ Interceptor 401 solo limpia storage (no redirect)
// ✅ AuthContext maneja la navegación cuando sea necesario
```

## ✅ Estado Actual

### Backend (MySQL en Docker)
- ✅ MySQL corriendo: `backend-mysql-1` (puerto 3306)
- ✅ Migraciones ejecutadas (incluida migración de compatibilidad)
- ✅ Usuario admin existe: `admin@panificadoranancy.com` / `admin123`
- ✅ Login API responde correctamente (200 OK con token)

### Frontend
- ✅ Build completado: `dist/` copiado a `backend/public/app`
- ✅ Dev server corriendo: http://localhost:5174/app/
- ✅ Basename configurado: `/app/`
- ✅ AuthContext corregido (no navega automáticamente)

## 🧪 Cómo Probar

### Opción 1: Servidor de Desarrollo (Vite)
```bash
# El servidor ya está corriendo en:
http://localhost:5174/app/

# Credenciales:
Email: admin@panificadoranancy.com
Password: admin123
```

### Opción 2: Servidor Laravel (Producción)
```bash
# Accede a:
http://localhost/app/

# Credenciales:
Email: admin@panificadoranancy.com
Password: admin123
```

### Verificar Backend (curl)
```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@panificadoranancy.com","password":"admin123"}' | jq
```

**Respuesta esperada:**
```json
{
  "message": "Login exitoso",
  "user": {
    "id": 1,
    "name": "Administrador",
    "email": "admin@panificadoranancy.com",
    "roles": [{"name": "admin", ...}]
  },
  "access_token": "...",
  "token_type": "Bearer"
}
```

## 📝 Flujo de Login Corregido

1. **Usuario abre `/app/`** → Home pública (sin token)
2. **Usuario hace clic en "Login"** → Navega a `/app/login`
3. **Usuario ingresa credenciales** → POST `/api/login`
4. **Backend responde 200** → Token + usuario
5. **Frontend guarda en localStorage** → `auth_token` y `user`
6. **AuthContext detecta token** → Llama `/api/me` para cargar roles
7. **Login.jsx navega según rol**:
   - Admin → `/admin`
   - Vendedor → `/vendedor`
   - Cliente → `/`

## 🔧 Comandos Útiles

### Reconstruir frontend
```bash
cd frontend
npm run build
rm -rf ../backend/public/app
cp -r dist ../backend/public/app
```

### Ver logs de Laravel
```bash
cd backend
tail -f storage/logs/laravel.log
```

### Ejecutar migraciones (Docker)
```bash
cd backend
docker compose exec laravel.test php artisan migrate
```

### Limpiar cache de Laravel
```bash
cd backend
docker compose exec laravel.test php artisan config:clear
docker compose exec laravel.test php artisan cache:clear
```

## 🚀 Próximos Pasos

1. ✅ **Login funciona** - Backend responde correctamente
2. ✅ **Basename correcto** - Rutas funcionan bajo `/app/`
3. ✅ **MySQL configurado** - Base de datos operativa
4. 🔄 **Prueba en navegador** - Abre http://localhost:5174/app/ e inicia sesión
5. 📱 **Verifica responsive** - Prueba en móvil/tablet
6. 🎨 **Ajusta logos/estilos** - Si es necesario (olog.jpg ya configurado)

## ⚠️ Notas Importantes

- **NO usar SQLite**: El proyecto está configurado para MySQL (Docker Sail)
- **Basename `/app/`**: Todas las rutas del frontend deben usar este prefijo
- **CORS**: Ya configurado para `http://localhost:5174` en el backend
- **Rate limiting**: Login tiene límite de 5 intentos/minuto
- **Token Sanctum**: Se guarda en `localStorage` como `auth_token`

---
**Fecha**: 11 de octubre de 2025  
**Estado**: ✅ Login funcionando correctamente
