# 📂 Estructura de Carpetas de Laravel - Guía Completa

## 🎯 Visión General

Laravel sigue el patrón **MVC (Model-View-Controller)** y organiza el código de forma lógica:

```
backend/
├── app/                 ← Tu código principal (Modelos, Controladores, etc.)
├── bootstrap/           ← Archivos de inicio de Laravel (NO TOCAR)
├── config/              ← Configuraciones del sistema
├── database/            ← Migraciones, Seeders, Factories
├── public/              ← Punto de entrada (index.php, assets públicos)
├── resources/           ← Vistas, CSS, JS (para Blade, no React)
├── routes/              ← Definición de rutas (API, Web)
├── storage/             ← Archivos generados (logs, uploads, caché)
├── tests/               ← Tests automatizados
├── vendor/              ← Dependencias de Composer (NO TOCAR)
├── .env                 ← Configuración de entorno (IMPORTANTE)
├── artisan              ← CLI de Laravel
├── composer.json        ← Dependencias PHP
└── package.json         ← Dependencias JavaScript
```

---

## 📁 **Carpetas PRINCIPALES (Las que usarás)**

### **1. app/ - Tu Código Principal** ⭐⭐⭐

```
app/
├── Http/
│   ├── Controllers/        ← AQUÍ crearás los controladores de API
│   │   ├── Api/           ← Mejor práctica: subcarpeta para API
│   │   │   ├── ProductController.php
│   │   │   ├── OrderController.php
│   │   │   └── AuthController.php
│   │   └── Controller.php  ← Controlador base
│   │
│   ├── Middleware/         ← Filtros de peticiones (autenticación, CORS, etc.)
│   │   ├── Authenticate.php
│   │   └── CheckRole.php
│   │
│   └── Requests/           ← Validaciones de formularios
│       ├── StoreProductRequest.php
│       └── UpdateProductRequest.php
│
├── Models/                 ← AQUÍ crearás los modelos (representan tablas)
│   ├── User.php
│   ├── Product.php
│   ├── Category.php
│   ├── Order.php
│   └── OrderItem.php
│
├── Services/               ← Lógica de negocio compleja (OPCIONAL)
│   ├── PaymentService.php
│   └── InventoryService.php
│
└── Providers/              ← Configuración de servicios (raro modificar)
```

**¿Qué va en cada parte?**

- **Models**: Representan tablas de la BD (Product, Order, etc.)
- **Controllers**: Procesan las peticiones HTTP (API endpoints)
- **Middleware**: Filtran peticiones (ej: verificar que el usuario esté logueado)
- **Requests**: Validan datos antes de llegar al controlador

---

### **2. database/ - Base de Datos** ⭐⭐⭐

```
database/
├── migrations/             ← AQUÍ crearás las tablas (SQL en PHP)
│   ├── 2025_01_15_000001_create_categories_table.php
│   ├── 2025_01_15_000002_create_products_table.php
│   └── 2025_01_15_000003_create_orders_table.php
│
├── seeders/                ← AQUÍ crearás datos de prueba
│   ├── DatabaseSeeder.php  ← Seeder principal
│   ├── CategorySeeder.php
│   └── ProductSeeder.php
│
└── factories/              ← Generadores de datos falsos (para testing)
    └── ProductFactory.php
```

**Flujo de trabajo:**
1. **Migración** → Crea la tabla
2. **Seeder** → Llena la tabla con datos
3. **Factory** → Genera datos aleatorios para tests

---

### **3. routes/ - Rutas de la API** ⭐⭐⭐

```
routes/
├── api.php          ← AQUÍ definirás TODAS tus rutas de API
├── web.php          ← Para vistas Blade (NO lo usarás con React)
└── console.php      ← Comandos personalizados de Artisan
```

**Ejemplo de routes/api.php:**
```php
<?php

use App\Http\Controllers\Api\ProductController;

// Todas las rutas aquí tienen prefijo /api
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::post('/products', [ProductController::class, 'store']);
```

**Resultado:** 
- GET http://localhost/api/products
- GET http://localhost/api/products/1
- POST http://localhost/api/products

---

### **4. config/ - Configuraciones** ⭐

```
config/
├── app.php          ← Configuración general (nombre, timezone, locale)
├── database.php     ← Configuración de conexiones a BD
├── auth.php         ← Configuración de autenticación
├── cors.php         ← CORS (importante para React)
└── filesystems.php  ← Almacenamiento de archivos
```

**Raramente los modificas**, ya están bien configurados.

---

### **5. storage/ - Archivos Generados** ⭐

```
storage/
├── app/
│   ├── public/      ← Archivos públicos (imágenes de productos, etc.)
│   └── private/     ← Archivos privados
│
├── framework/       ← Caché de Laravel (NO TOCAR)
│   ├── cache/
│   ├── sessions/
│   └── views/
│
└── logs/            ← IMPORTANTE: logs de errores
    └── laravel.log  ← Revisa aquí cuando algo falla
```

**Cuándo usar:**
- **storage/app/public/** → Guardar imágenes de productos
- **storage/logs/** → Ver errores cuando algo falla

---

### **6. public/ - Punto de Entrada** ⭐

```
public/
├── index.php        ← Punto de entrada (NO MODIFICAR)
├── .htaccess        ← Configuración de Apache
└── images/          ← Puedes poner imágenes estáticas aquí
```

**Para React:**
Aquí es donde subirás el **build/** de React (archivos compilados).

---

## 🎯 **Archivos IMPORTANTES en la raíz**

### **.env - Configuración de Entorno** ⭐⭐⭐

```env
# Datos de la aplicación
APP_NAME="Panificadora Nancy"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Base de datos
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# Redis (caché)
REDIS_HOST=redis
REDIS_PORT=6379
```

**NUNCA subir .env a Git** (tiene contraseñas)

---

### **artisan - CLI de Laravel** ⭐⭐⭐

Es el comando mágico de Laravel:

```bash
# Crear migración
./vendor/bin/sail artisan make:migration create_products_table

# Crear modelo
./vendor/bin/sail artisan make:model Product

# Crear controlador
./vendor/bin/sail artisan make:controller ProductController

# Ejecutar migraciones
./vendor/bin/sail artisan migrate

# Ejecutar seeders
./vendor/bin/sail artisan db:seed
```

---

## 📊 **Flujo de Trabajo Típico**

### **Ejemplo: Crear módulo de Productos**

```bash
# 1. Crear Migración (tabla en BD)
sail artisan make:migration create_products_table
# Editar: database/migrations/xxxx_create_products_table.php

# 2. Crear Modelo
sail artisan make:model Product
# Editar: app/Models/Product.php

# 3. Crear Controlador
sail artisan make:controller Api/ProductController --api
# Editar: app/Http/Controllers/Api/ProductController.php

# 4. Definir Rutas
# Editar: routes/api.php

# 5. Ejecutar migración
sail artisan migrate

# 6. Crear Seeder (datos de prueba)
sail artisan make:seeder ProductSeeder
# Editar: database/seeders/ProductSeeder.php

# 7. Ejecutar seeder
sail artisan db:seed --class=ProductSeeder
```

---

## 🗂️ **Estructura Recomendada para tu Proyecto**

Para **Panificadora Nancy**, organizarás así:

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           ├── AuthController.php
│           ├── ProductController.php
│           ├── CategoryController.php
│           ├── OrderController.php
│           ├── InventoryController.php
│           └── PaymentController.php
│
├── Models/
│   ├── User.php
│   ├── Role.php
│   ├── Category.php
│   ├── Product.php
│   ├── ProductImage.php
│   ├── Order.php
│   ├── OrderItem.php
│   ├── Payment.php
│   ├── Inventory.php
│   └── ShippingAddress.php
│
└── Services/              ← Lógica compleja
    ├── QRSimpleService.php
    ├── InventoryService.php
    └── OrderService.php

database/
├── migrations/
│   ├── 2025_01_15_000001_create_roles_table.php
│   ├── 2025_01_15_000002_create_categories_table.php
│   ├── 2025_01_15_000003_create_products_table.php
│   ├── 2025_01_15_000004_create_product_images_table.php
│   ├── 2025_01_15_000005_create_orders_table.php
│   └── ... (16 migraciones en total)
│
└── seeders/
    ├── RoleSeeder.php
    ├── CategorySeeder.php
    ├── ProductSeeder.php
    └── UserSeeder.php

routes/
└── api.php  ← Todas las rutas aquí
```

---

## 🎓 **Conceptos Clave**

| Término | Qué es | Ejemplo |
|---------|--------|---------|
| **Migración** | Archivo que crea/modifica tablas en BD | `create_products_table.php` |
| **Modelo** | Clase que representa una tabla | `Product.php` |
| **Controlador** | Maneja la lógica de las rutas | `ProductController.php` |
| **Seeder** | Llena la BD con datos de prueba | `ProductSeeder.php` |
| **Factory** | Genera datos falsos | `ProductFactory.php` |
| **Middleware** | Filtra peticiones HTTP | `Authenticate.php` |
| **Service** | Lógica de negocio compleja | `PaymentService.php` |

---

## 🚀 **Comandos Artisan Útiles**

```bash
# Ver todas las rutas
sail artisan route:list

# Ver estado de migraciones
sail artisan migrate:status

# Refrescar BD (CUIDADO: borra todo)
sail artisan migrate:fresh

# Ejecutar migraciones + seeders
sail artisan migrate:fresh --seed

# Crear modelo + migración + controlador + seeder (todo junto)
sail artisan make:model Product -mcrs
# -m = migración
# -c = controlador
# -r = resource (con métodos CRUD)
# -s = seeder

# Limpiar caché
sail artisan cache:clear
sail artisan config:clear
sail artisan route:clear
```

---

## 💡 **Mejores Prácticas**

### ✅ **SÍ hacer:**
- Usar nombres en singular para Modelos: `Product`, `Order`
- Usar nombres en plural para tablas: `products`, `orders`
- Poner controladores de API en `App\Http\Controllers\Api\`
- Usar Request classes para validaciones
- Usar Services para lógica compleja

### ❌ **NO hacer:**
- Modificar archivos en `vendor/`
- Modificar archivos en `bootstrap/`
- Poner lógica de negocio en rutas
- Subir `.env` a Git
- Modificar `public/index.php`

---

## 🎯 **Resumen Visual**

```
Cliente (React) 
    ↓ HTTP Request
routes/api.php (Define la ruta)
    ↓
app/Http/Middleware (Valida permisos)
    ↓
app/Http/Requests (Valida datos)
    ↓
app/Http/Controllers (Procesa la petición)
    ↓
app/Models (Interactúa con la BD)
    ↓
database/ (Datos)
    ↓
app/Http/Controllers (Devuelve respuesta JSON)
    ↓
Cliente (React) recibe datos
```

---

## 📖 **Siguiente Paso**

Ahora que entiendes la estructura, estás listo para crear el **Módulo 1 - Productos**.

**¿Listo para que genere el código?** 🚀
