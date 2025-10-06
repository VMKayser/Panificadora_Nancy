# 🐳 Setup Completo con Docker en Linux - Panificadora Nancy

## 🎯 ¿Por qué Docker en Linux?

✅ **Instalación más rápida** que PHP + MySQL + Composer por separado  
✅ **No contaminas tu sistema** con versiones específicas  
✅ **Todo en contenedores** (fácil de limpiar si algo falla)  
✅ **Laravel Sail** automatiza todo  
✅ **Mismo entorno** que tendrá tu amigo  

---

## 📦 PASO 1: Instalar Docker y Docker Compose

### 1.1 Actualizar el sistema
```bash
sudo apt update
sudo apt upgrade -y
```

### 1.2 Instalar Docker
```bash
# Instalar dependencias
sudo apt install apt-transport-https ca-certificates curl software-properties-common -y

# Agregar clave GPG oficial de Docker
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Agregar repositorio de Docker (Ubuntu/Debian/Linux Mint)
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Actualizar e instalar Docker
sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io docker-compose-plugin -y
```

### 1.3 Configurar Docker (para usarlo sin sudo)
```bash
# Agregar tu usuario al grupo docker
sudo usermod -aG docker $USER

# Habilitar Docker al inicio
sudo systemctl enable docker
sudo systemctl start docker

# ⚠️ IMPORTANTE: Cierra sesión y vuelve a entrar para que los cambios surtan efecto
# O ejecuta:
newgrp docker
```

### 1.4 Verificar instalación
```bash
docker --version
# Debe mostrar: Docker version 24.x.x o superior

docker compose version
# Debe mostrar: Docker Compose version 2.x.x
```

---

## 🚀 PASO 2: Instalar Composer (Solo para crear el proyecto)

Necesitamos Composer solo UNA VEZ para crear el proyecto Laravel:

```bash
# Descargar instalador de Composer
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php

# Instalar globalmente
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Limpiar
rm composer-setup.php

# Verificar
composer --version
# Debe mostrar: Composer version 2.x.x
```

**Nota:** Si no tienes PHP instalado para este paso, instala solo PHP CLI temporalmente:
```bash
sudo apt install php-cli php-mbstring php-xml unzip -y
```

---

## 🏗️ PASO 3: Crear Proyecto Laravel con Sail (Docker)

### 3.1 Ir a la carpeta del proyecto
```bash
cd "/media/kayser/7EF687B8F6876EE920/proyecto Panificadora Nancy/Panificadora_Nancy"
```

### 3.2 Crear proyecto Laravel con Sail
```bash
# Crear el proyecto con Laravel Sail
curl -s "https://laravel.build/backend?with=mysql,redis" | bash
```

**¿Qué hace este comando?**
- Descarga Laravel 11
- Configura Docker automáticamente
- Instala MySQL en contenedor
- Instala Redis (caché)
- Crea todo en carpeta `backend/`

**⏳ Esto tomará 5-10 minutos** (descarga imágenes Docker)

### 3.3 Entrar al proyecto
```bash
cd backend
```

---

## ⚙️ PASO 4: Configurar y Levantar el Proyecto

### 4.1 Iniciar contenedores Docker
```bash
# Primera vez (construye las imágenes)
./vendor/bin/sail up -d

# El flag -d ejecuta en segundo plano (detached)
```

**¿Qué acabas de hacer?**
- Iniciaste contenedores de: PHP, MySQL, Redis
- Laravel está corriendo en: **http://localhost**
- MySQL está en: **localhost:3306**

### 4.2 Verificar que funciona
```bash
# Ver contenedores corriendo
./vendor/bin/sail ps

# Deberías ver:
# - backend-laravel.test-1 (PHP/Laravel)
# - backend-mysql-1 (MySQL)
# - backend-redis-1 (Redis)
```

Abre tu navegador en: **http://localhost**

✅ Si ves la página de Laravel, **¡FUNCIONA!**

---

## 🎯 PASO 5: Alias para Comandos más Cortos

En lugar de escribir `./vendor/bin/sail` cada vez, crea un alias:

```bash
# Agregar alias temporal (solo sesión actual)
alias sail='./vendor/bin/sail'

# O permanente (agrega a tu ~/.bashrc)
echo "alias sail='./vendor/bin/sail'" >> ~/.bashrc
source ~/.bashrc
```

Ahora puedes usar:
```bash
sail up -d        # En lugar de ./vendor/bin/sail up -d
sail artisan ...  # En lugar de ./vendor/bin/sail artisan ...
sail composer ... # En lugar de ./vendor/bin/sail composer ...
```

---

## 🗄️ PASO 6: Configurar Base de Datos

### 6.1 Verificar archivo .env

El archivo `backend/.env` ya está configurado automáticamente por Sail:

```env
DB_CONNECTION=mysql
DB_HOST=mysql              # ← Nombre del contenedor
DB_PORT=3306
DB_DATABASE=panificadora_nancy
DB_USERNAME=sail
DB_PASSWORD=password
```

### 6.2 Crear la base de datos

```bash
# Conectarse a MySQL dentro del contenedor
sail mysql

# Dentro de MySQL:
CREATE DATABASE panificadora_nancy;
SHOW DATABASES;
EXIT;
```

O directamente:
```bash
sail mysql -e "CREATE DATABASE panificadora_nancy;"
```

### 6.3 Probar conexión
```bash
sail artisan migrate
```

✅ Si no da error, **la conexión funciona**

---

## 📝 PASO 7: Comandos Útiles de Sail

```bash
# Iniciar contenedores
sail up -d

# Detener contenedores
sail down

# Ver logs en tiempo real
sail logs -f

# Ejecutar comandos Artisan
sail artisan migrate
sail artisan make:model Product

# Ejecutar Composer
sail composer install
sail composer require nombre/paquete

# Ejecutar npm (para React después)
sail npm install
sail npm run dev

# Acceder a la terminal de PHP
sail shell

# Acceder a MySQL
sail mysql

# Reiniciar contenedores
sail restart

# Ver estado de contenedores
sail ps
```

---

## 🧪 PASO 8: Verificación Final

### Checklist:
- [ ] Docker instalado (`docker --version`)
- [ ] Docker Compose instalado (`docker compose version`)
- [ ] Usuario en grupo docker (`groups | grep docker`)
- [ ] Proyecto Laravel creado en `backend/`
- [ ] Contenedores corriendo (`sail ps`)
- [ ] Laravel visible en http://localhost
- [ ] Base de datos `panificadora_nancy` creada
- [ ] Migraciones funcionan (`sail artisan migrate`)

---

## 🎓 Conceptos que Aprendiste

| Concepto | Explicación |
|----------|-------------|
| **Docker** | Virtualización ligera (contenedores) |
| **Contenedor** | Aplicación aislada con sus dependencias |
| **Laravel Sail** | Docker preconfigurido para Laravel |
| **docker-compose** | Orquestador de múltiples contenedores |
| **Imagen** | Plantilla para crear contenedores |

---

## 🆘 Solución de Problemas

### Error: "Cannot connect to Docker daemon"
```bash
# Verificar que Docker esté corriendo
sudo systemctl status docker

# Si no está activo:
sudo systemctl start docker
```

### Error: "Permission denied"
```bash
# Agregarte al grupo docker
sudo usermod -aG docker $USER
newgrp docker
```

### Puerto 80 en uso
```bash
# Editar docker-compose.yml y cambiar:
# APP_PORT=80 → APP_PORT=8000

# Luego:
sail down
sail up -d
```

### Contenedores no inician
```bash
# Ver logs de errores
sail logs

# Reconstruir contenedores
sail down
sail build --no-cache
sail up -d
```

---

## 🌟 Ventajas de este Setup

✅ **PHP 8.3** (última versión, automático)  
✅ **MySQL 8** (sin instalarlo en tu sistema)  
✅ **Redis** (caché listo)  
✅ **Node.js** incluido (para React después)  
✅ **Todo aislado** (no contamina tu sistema)  
✅ **Fácil de compartir** con tu amigo  
✅ **Fácil de eliminar** si quieres empezar de cero  

---

## 🚀 Próximos Pasos

Una vez que tengas todo ✅:

1. **Crear las migraciones** (tablas de la BD)
2. **Crear los modelos** (representar datos)
3. **Crear la API** (endpoints REST)
4. **Conectar React** (frontend)

---

## 🎯 ¿Listo para Continuar?

Cuando tengas:
- ✅ Docker funcionando
- ✅ Laravel corriendo en http://localhost
- ✅ Base de datos creada

**Avísame diciendo: "Docker listo ✅"**

Y te generaré el **Módulo 1 completo** (Productos con migraciones, modelos, API).

---

## 💡 Tip Final

Guarda estos comandos:

```bash
# Inicio del día
cd "/media/kayser/7EF687B8F6876EE920/proyecto Panificadora Nancy/Panificadora_Nancy/backend"
sail up -d

# Final del día
sail down
```

¡Eso es todo! 🎉
