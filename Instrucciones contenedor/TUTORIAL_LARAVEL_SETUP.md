# 🚀 Tutorial: Setup de Laravel para Panificadora Nancy

## 📋 Pre-requisitos

Antes de empezar, necesitas tener instalado:

1. **PHP 8.1 o superior**
   ```bash
   php --version
   # Debe mostrar: PHP 8.1.x o superior
   ```

2. **Composer** (gestor de paquetes de PHP)
   ```bash
   composer --version
   # Si no lo tienes, instala desde: https://getcomposer.org/
   ```

3. **MySQL o MariaDB**
   ```bash
   mysql --version
   ```

4. **Node.js y NPM** (para el frontend después)
   ```bash
   node --version
   npm --version
   ```

---

## 🏗️ PASO 1: Crear Proyecto Laravel

Abre tu terminal en la carpeta del proyecto y ejecuta:

```bash
# Ve a la carpeta raíz del proyecto
cd "/media/kayser/7EF687B8F6876EE920/proyecto Panificadora Nancy/Panificadora_Nancy"

# Crea el proyecto Laravel llamado "backend"
composer create-project laravel/laravel backend

# Entra a la carpeta
cd backend
```

**¿Qué acabas de hacer?**
- Creaste un proyecto Laravel completo en la carpeta `backend/`
- Composer descargó todas las dependencias necesarias
- Laravel creó toda la estructura de carpetas

---

## ⚙️ PASO 2: Configurar Base de Datos

### 2.1 Crear la Base de Datos en MySQL

```bash
# Abre MySQL (ajusta usuario/contraseña según tu instalación)
mysql -u root -p

# Dentro de MySQL, crea la base de datos:
CREATE DATABASE panificadora_nancy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Sal de MySQL
EXIT;
```

### 2.2 Configurar archivo .env

Abre el archivo `backend/.env` y edita estas líneas:

```env
APP_NAME="Panificadora Nancy"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=panificadora_nancy
DB_USERNAME=root
DB_PASSWORD=tu_contraseña_mysql
```

**⚠️ IMPORTANTE:** Cambia `DB_PASSWORD` por tu contraseña real de MySQL.

---

## 🧪 PASO 3: Probar que Funciona

```bash
# Dentro de la carpeta backend/
php artisan serve
```

Deberías ver algo como:
```
INFO  Server running on [http://127.0.0.1:8000].
```

Abre tu navegador en: **http://localhost:8000**

Si ves la página de bienvenida de Laravel, **¡FELICIDADES!** ✅

Presiona `Ctrl+C` para detener el servidor.

---

## 📦 PASO 4: Instalar Dependencias Adicionales

```bash
# Laravel Sanctum (para autenticación API)
php artisan install:api

# Confirma con "yes" cuando te pregunte
```

---

## 📝 PASO 5: Entender la Estructura de Laravel

```
backend/
├── app/
│   ├── Models/          ← Aquí crearás tus modelos (Product, Order, etc.)
│   └── Http/
│       └── Controllers/ ← Aquí crearás los controladores (API)
│
├── database/
│   ├── migrations/      ← Aquí crearás las migraciones (tablas)
│   └── seeders/         ← Aquí crearás datos de prueba
│
├── routes/
│   ├── api.php          ← Aquí definirás las rutas de tu API
│   └── web.php          ← (No lo usaremos, solo API)
│
└── .env                 ← Configuración (base de datos, etc.)
```

---

## ✅ Checklist de Verificación

Marca cada punto cuando lo completes:

- [ ] PHP 8.1+ instalado
- [ ] Composer instalado
- [ ] MySQL instalado y corriendo
- [ ] Base de datos `panificadora_nancy` creada
- [ ] Proyecto Laravel creado en carpeta `backend/`
- [ ] Archivo `.env` configurado correctamente
- [ ] Servidor Laravel funciona (`php artisan serve`)
- [ ] Laravel Sanctum instalado

---

## 🎯 Próximo Paso

Una vez que tengas todo ✅, el siguiente paso es:

**CREAR LAS MIGRACIONES** (convertir el diseño de BD a código Laravel)

---

## 🆘 Problemas Comunes

### Error: "composer: command not found"
**Solución:** Instala Composer desde https://getcomposer.org/

### Error: "PDOException: could not find driver"
**Solución:** Habilita la extensión de MySQL en PHP:
```bash
# En Ubuntu/Debian:
sudo apt-get install php-mysql

# Reinicia después
```

### Error: "Access denied for user 'root'@'localhost'"
**Solución:** Tu contraseña en `.env` está incorrecta. Verifica tu contraseña de MySQL.

### Error: Puerto 8000 en uso
**Solución:** Usa otro puerto:
```bash
php artisan serve --port=8001
```

---

## 💡 Conceptos que Estás Aprendiendo

- **Laravel**: Framework de PHP para crear APIs robustas
- **Composer**: Gestor de dependencias de PHP (como npm en Node.js)
- **Artisan**: CLI (Command Line Interface) de Laravel
- **.env**: Archivo de configuración (no se sube a Git)
- **API**: Backend que responde JSON (para React)

---

¿Completaste este tutorial? **¡Avísame para continuar con las migraciones!** 🚀
