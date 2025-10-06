# 📘 Guía Completa de Laravel - Explicación desde CERO

## 🎯 ¿Qué es Laravel?

**Laravel** es un **framework de PHP** (conjunto de herramientas) que te ayuda a crear aplicaciones web de forma rápida y organizada, sin tener que escribir todo desde cero.

**Analogía:**
```
PHP puro = Construir una casa ladrillo por ladrillo
Laravel = Usar bloques prefabricados con planos listos
```

---

## 🏗️ ¿Qué incluye Laravel SIEMPRE por defecto?

Cuando creas un proyecto Laravel (como hicimos con `laravel.build`), SIEMPRE obtienes:

### **1. Sistema de Base de Datos Básico** ✅

#### **Tablas que se crean AUTOMÁTICAMENTE:**

```sql
📦 Base de Datos Mínima de Laravel:

1. users (Usuarios)
   ├─ Para: Guardar usuarios del sistema
   └─ Campos: id, name, email, password

2. password_reset_tokens
   ├─ Para: Recuperación de contraseñas
   └─ Campos: email, token, created_at

3. sessions
   ├─ Para: Rastrear usuarios conectados
   └─ Campos: id, user_id, ip_address

4. cache & cache_locks
   ├─ Para: Mejorar velocidad guardando datos temporales
   └─ Ejemplo: Guardar lista de productos por 10 min

5. jobs & failed_jobs
   ├─ Para: Tareas en segundo plano
   └─ Ejemplo: Enviar emails, procesar pagos

6. migrations
   ├─ Para: Control de versiones de la BD
   └─ Rastrea qué migraciones se ejecutaron
```

**¿Por qué estas tablas?**
- Laravel asume que TODA app necesita usuarios, sesiones y caché
- Son el **mínimo funcional** para que la app arranque
- Tú agregas TUS tablas después (products, orders, etc.)

---

### **2. Estructura de Carpetas Completa** 📁

```
backend/
├── app/                    ← TU CÓDIGO (70% del tiempo aquí)
├── bootstrap/              ← Inicio de Laravel (NO TOCAR)
├── config/                 ← Configuraciones (raro modificar)
├── database/               ← Migraciones, Seeders (TÚ trabajas aquí)
├── public/                 ← Punto de entrada web
├── resources/              ← Vistas (no usamos con React)
├── routes/                 ← Rutas de la API (TÚ trabajas aquí)
├── storage/                ← Archivos generados, logs
├── tests/                  ← Tests automatizados
├── vendor/                 ← Librerías (NO TOCAR)
├── .env                    ← Configuración de entorno
├── artisan                 ← CLI mágica de Laravel
└── composer.json           ← Dependencias PHP
```

---

## 📂 Explicación DETALLADA de cada carpeta

### **1. app/ - Tu Código Principal** ⭐⭐⭐

**¿Qué contiene?**
```
app/
├── Http/
│   ├── Controllers/     ← Lógica de las rutas (ProductController)
│   ├── Middleware/      ← Filtros (autenticación, CORS)
│   └── Requests/        ← Validaciones de formularios
│
├── Models/              ← Representan tablas de BD (Product, Order)
├── Providers/           ← Configuración avanzada (raro tocar)
└── Exceptions/          ← Manejo de errores
```

**¿Para qué sirve?**
- **Models:** Interactúan con la base de datos
  ```php
  $producto = Product::find(1); // Busca producto con id 1
  ```

- **Controllers:** Procesan peticiones HTTP
  ```php
  public function index() {
      return Product::all(); // Devuelve todos los productos
  }
  ```

- **Middleware:** Filtran peticiones
  ```php
  // Solo usuarios logueados pueden acceder
  Route::middleware('auth')->get('/admin', ...);
  ```

**¿Cuándo trabajas aquí?**
- Creando modelos
- Creando controladores
- Creando validaciones

---

### **2. database/ - Base de Datos** ⭐⭐⭐

**¿Qué contiene?**
```
database/
├── migrations/          ← Archivos que CREAN/MODIFICAN tablas
│   ├── 2025_01_01_000000_create_users_table.php
│   └── 2025_01_15_000001_create_products_table.php
│
├── seeders/             ← Archivos que LLENAN tablas con datos
│   ├── DatabaseSeeder.php
│   └── ProductSeeder.php
│
└── factories/           ← Generan datos falsos para testing
    └── ProductFactory.php
```

**¿Para qué sirve?**

**Migraciones:**
```php
// Archivo: create_products_table.php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->decimal('price', 8, 2);
});
```
**Resultado:** Se crea la tabla `products` en MySQL

**Seeders:**
```php
// Archivo: ProductSeeder.php
DB::table('products')->insert([
    'name' => 'TantaWawas',
    'price' => 15.00
]);
```
**Resultado:** Se inserta un producto en la tabla

**¿Cuándo trabajas aquí?**
- Creando/modificando estructura de tablas
- Llenando tablas con datos de prueba

---

### **3. routes/ - Rutas de la API** ⭐⭐⭐

**¿Qué contiene?**
```
routes/
├── api.php          ← Rutas de la API REST (TÚ trabajas aquí)
├── web.php          ← Rutas web (no usas con React)
└── console.php      ← Comandos personalizados
```

**¿Para qué sirve?**
```php
// Archivo: routes/api.php
Route::get('/products', [ProductController::class, 'index']);
// Resultado: GET http://localhost/api/products
```

**Define qué URLs existen y qué hacen:**
```
GET  /api/products          → Listar todos los productos
GET  /api/products/{id}     → Ver un producto específico
POST /api/products          → Crear un producto
PUT  /api/products/{id}     → Actualizar un producto
DELETE /api/products/{id}   → Eliminar un producto
```

**¿Cuándo trabajas aquí?**
- Cada vez que agregas un nuevo endpoint de API

---

### **4. config/ - Configuraciones** ⭐

**¿Qué contiene?**
```
config/
├── app.php          ← Configuración general (nombre, timezone)
├── database.php     ← Conexiones a BD
├── auth.php         ← Autenticación
└── cors.php         ← CORS (importante para React)
```

**¿Para qué sirve?**
Centraliza configuraciones del sistema.

**Ejemplo: config/database.php**
```php
'mysql' => [
    'host' => env('DB_HOST', '127.0.0.1'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
]
```

**¿Cuándo trabajas aquí?**
- Casi nunca (ya está bien configurado)
- Solo si necesitas cambios avanzados

---

### **5. public/ - Punto de Entrada Web** ⭐

**¿Qué contiene?**
```
public/
├── index.php        ← Punto de entrada (NO MODIFICAR)
├── .htaccess        ← Configuración Apache
└── storage/         ← Link a storage/app/public
```

**¿Para qué sirve?**
- Es la **ÚNICA carpeta accesible desde el navegador**
- Aquí subirás el **build de React** (archivos compilados)

**¿Cuándo trabajas aquí?**
- Al final, cuando despliegues React compilado

---

### **6. storage/ - Archivos Generados** ⭐⭐

**¿Qué contiene?**
```
storage/
├── app/
│   ├── public/      ← Imágenes de productos (accesibles públicamente)
│   └── private/     ← Archivos privados
│
├── framework/       ← Caché de Laravel (NO TOCAR)
│   ├── cache/
│   ├── sessions/
│   └── views/
│
└── logs/            ← LOGS DE ERRORES (MUY IMPORTANTE)
    └── laravel.log  ← Revisa aquí cuando algo falla
```

**¿Para qué sirve?**

**storage/app/public:**
```php
// Guardar imagen de producto
$request->file('image')->store('products', 'public');
// Resultado: storage/app/public/products/imagen.jpg
```

**storage/logs/laravel.log:**
```
Cuando tu código falla, aquí verás:
- Qué error ocurrió
- En qué línea de código
- El mensaje de error completo
```

**¿Cuándo trabajas aquí?**
- Guardando archivos (imágenes, PDFs)
- Revisando errores en `logs/`

---

### **7. vendor/ - Librerías de PHP** ❌ NO TOCAR

**¿Qué contiene?**
- Todas las librerías que Laravel necesita
- Instaladas por Composer

**¿Para qué sirve?**
- Laravel y sus dependencias viven aquí

**¿Cuándo trabajas aquí?**
- **NUNCA**. Se genera automáticamente con `composer install`

---

### **8. tests/ - Tests Automatizados** ⭐

**¿Qué contiene?**
```
tests/
├── Feature/         ← Tests de funcionalidades completas
└── Unit/            ← Tests de funciones individuales
```

**¿Para qué sirve?**
Verificar que tu código funciona correctamente.

**Ejemplo:**
```php
// Verifica que la API de productos funciona
public function test_can_get_products() {
    $response = $this->get('/api/products');
    $response->assertStatus(200);
}
```

**¿Cuándo trabajas aquí?**
- Cuando quieras asegurar calidad (opcional al inicio)

---

## 📄 Archivos IMPORTANTES en la raíz

### **1. .env - Configuración de Entorno** ⭐⭐⭐

**¿Qué contiene?**
```env
APP_NAME="Panificadora Nancy"
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

**¿Para qué sirve?**
- Guarda configuraciones sensibles
- Cambia según el entorno (desarrollo vs producción)

**IMPORTANTE:**
- ❌ NUNCA subir a Git (tiene contraseñas)
- ✅ Cada desarrollador tiene su propio `.env`

---

### **2. artisan - CLI de Laravel** ⭐⭐⭐

**¿Qué es?**
Es un **programa de línea de comandos** que hace tareas automáticas.

**Comandos más usados:**
```bash
# CREAR cosas
sail artisan make:model Product        # Crea modelo
sail artisan make:migration create_products  # Crea migración
sail artisan make:controller ProductController  # Crea controlador
sail artisan make:seeder ProductSeeder  # Crea seeder

# EJECUTAR migraciones
sail artisan migrate                   # Ejecuta migraciones pendientes
sail artisan migrate:fresh             # Borra todo y recrea
sail artisan migrate:rollback          # Revierte última migración

# EJECUTAR seeders
sail artisan db:seed                   # Ejecuta todos los seeders
sail artisan db:seed --class=ProductSeeder  # Ejecuta uno específico

# VER información
sail artisan route:list                # Lista todas las rutas
sail artisan migrate:status            # Estado de migraciones

# LIMPIAR caché
sail artisan cache:clear
sail artisan config:clear
sail artisan route:clear
```

---

### **3. composer.json - Dependencias PHP** ⭐

**¿Qué contiene?**
```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0"
    }
}
```

**¿Para qué sirve?**
Define qué librerías PHP necesita el proyecto.

**Comandos:**
```bash
composer install         # Instala todas las dependencias
composer require nombre  # Agrega nueva dependencia
```

---

## 🔄 ¿Por qué SIEMPRE se crean las mismas tablas base?

### **Razón 1: Autenticación**
Laravel asume que **toda app necesita usuarios**.
```
users → Para login, registro, perfiles
password_reset_tokens → Para "olvidé mi contraseña"
sessions → Para saber quién está conectado
```

### **Razón 2: Rendimiento**
```
cache → Guardar datos temporales (más rápido que consultar BD)
jobs → Procesar tareas pesadas en segundo plano
```

### **Razón 3: Control de Versiones**
```
migrations → Rastrea qué cambios se hicieron en la BD
```

---

## 🎯 Flujo de Trabajo Típico

### **Crear un módulo nuevo (Ejemplo: Productos)**

```bash
# 1. Crear migración (tabla en BD)
sail artisan make:migration create_products_table
# Editas: database/migrations/xxxx_create_products_table.php

# 2. Ejecutar migración (crea la tabla)
sail artisan migrate

# 3. Crear modelo (representa la tabla)
sail artisan make:model Product
# Editas: app/Models/Product.php

# 4. Crear controlador (lógica de la API)
sail artisan make:controller Api/ProductController
# Editas: app/Http/Controllers/Api/ProductController.php

# 5. Definir rutas (endpoints de la API)
# Editas: routes/api.php

# 6. Crear seeder (datos de prueba)
sail artisan make:seeder ProductSeeder
# Editas: database/seeders/ProductSeeder.php

# 7. Ejecutar seeder (llena la tabla)
sail artisan db:seed --class=ProductSeeder
```

---

## 📊 Resumen Visual: ¿Qué incluye Laravel?

```
┌─────────────────────────────────────────────┐
│  PROYECTO LARAVEL (Siempre incluye)        │
├─────────────────────────────────────────────┤
│                                             │
│  ✅ Sistema de Usuarios (users, sessions)  │
│  ✅ Sistema de Caché (cache)               │
│  ✅ Sistema de Colas (jobs)                │
│  ✅ Estructura MVC completa                │
│  ✅ CLI (artisan)                          │
│  ✅ ORM (Eloquent) para BD                 │
│  ✅ Sistema de Rutas                       │
│  ✅ Sistema de Migraciones                 │
│  ✅ Sistema de Validaciones                │
│  ✅ Sistema de Autenticación               │
│                                             │
│  🚀 TÚ AGREGAS:                            │
│  - Tus modelos (Product, Order, etc.)      │
│  - Tus controladores (API)                 │
│  - Tus migraciones (tablas personalizadas) │
│  - Tus seeders (datos de prueba)           │
│                                             │
└─────────────────────────────────────────────┘
```

---

## 💡 Conceptos Clave

| Término | Qué es | Para qué sirve |
|---------|--------|----------------|
| **Migración** | Archivo PHP que crea/modifica tablas | Versionar la estructura de BD |
| **Modelo** | Clase PHP que representa una tabla | Interactuar con la BD fácilmente |
| **Controlador** | Clase que procesa peticiones HTTP | Lógica de los endpoints API |
| **Seeder** | Archivo que llena tablas con datos | Datos de prueba |
| **Artisan** | CLI de Laravel | Automatizar tareas |
| **Eloquent** | ORM de Laravel | Consultar BD sin escribir SQL |
| **Middleware** | Filtro de peticiones HTTP | Autenticación, CORS, etc. |
| **.env** | Archivo de configuración | Guardar contraseñas, URLs, etc. |

---

## 🚀 Próximos Pasos

Ahora que entiendes:
- ✅ Qué incluye Laravel siempre
- ✅ Para qué sirve cada carpeta
- ✅ Por qué existen las tablas base
- ✅ Cómo es el flujo de trabajo

**Estás listo para crear el Módulo 1 (Productos)** 🎉

---

## 🆘 Preguntas Frecuentes

### **¿Puedo borrar las tablas base de Laravel?**
❌ No. Laravel las necesita para funcionar. Déjalas ahí.

### **¿Debo modificar los archivos en vendor/?**
❌ Nunca. Se generan automáticamente.

### **¿Dónde veo los errores cuando algo falla?**
✅ En `storage/logs/laravel.log`

### **¿Cómo agrego una nueva tabla?**
✅ Creas una migración con `artisan make:migration`

### **¿Cómo veo las rutas de mi API?**
✅ `sail artisan route:list`

---

¡Guía completa! 🎓 Ahora sabes TODO sobre la estructura de Laravel.
