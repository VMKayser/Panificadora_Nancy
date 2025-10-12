# 📘 Documentación Técnica - Sistema Panificadora Nancy

## 📋 Tabla de Contenidos

1. [Visión General](#visión-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Tecnologías Utilizadas](#tecnologías-utilizadas)
4. [Base de Datos](#base-de-datos)
5. [Backend - API REST](#backend---api-rest)
6. [Frontend - Aplicación Web](#frontend---aplicación-web)
7. [Seguridad](#seguridad)
8. [Escalabilidad](#escalabilidad)
9. [Funcionalidades](#funcionalidades)
10. [Despliegue](#despliegue)
11. [Mantenimiento](#mantenimiento)

---

## 🎯 Visión General

**Sistema de Gestión y E-commerce para Panificadora Nancy**

Sistema web completo para la gestión de inventario, producción y ventas en línea de una panificadora. Permite a los clientes ver productos, realizar pedidos, y al personal administrativo gestionar el inventario y la capacidad de producción.

### Características Principales
- ✅ Sistema de autenticación seguro con tokens
- ✅ Catálogo de productos con imágenes
- ✅ Carrito de compras en tiempo real
- ✅ Gestión de inventario y producción
- ✅ Panel administrativo completo
- ✅ Interfaz responsive y moderna
- ✅ Optimizado para SEO

---

## 🏗️ Arquitectura del Sistema

### Arquitectura General

```
┌─────────────────────────────────────────────────────────────┐
│                        NAVEGADOR                             │
│  (Chrome, Firefox, Safari, Edge)                            │
└────────────────────┬────────────────────────────────────────┘
                     │ HTTPS
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND (React SPA)                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Páginas    │  │  Componentes │  │   Contextos  │      │
│  │  - Home      │  │  - Header    │  │  - Auth      │      │
│  │  - Productos │  │  - Footer    │  │  - Cart      │      │
│  │  - Carrito   │  │  - Modal     │  │  - SEO       │      │
│  │  - Admin     │  │  - Cards     │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│                                                              │
│  Vite Build → Servido desde /app/                          │
└────────────────────┬────────────────────────────────────────┘
                     │ REST API (JSON)
                     │ /api/*
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                   BACKEND (Laravel 12)                       │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              API REST Controllers                     │   │
│  │  - AuthController (login, register, logout)         │   │
│  │  - ProductoController (CRUD productos)               │   │
│  │  - PedidoController (gestión de pedidos)            │   │
│  │  - InventarioController (stock, producción)         │   │
│  └──────────────────────────────────────────────────────┘   │
│                            │                                 │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              Middleware (Seguridad)                   │   │
│  │  - Sanctum (autenticación por tokens)               │   │
│  │  - CORS (políticas de origen cruzado)               │   │
│  │  - Rate Limiting (límite de peticiones)             │   │
│  └──────────────────────────────────────────────────────┘   │
│                            │                                 │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              Eloquent ORM Models                      │   │
│  │  - User, Producto, Pedido, Categoria                │   │
│  │  - DetallePedido, ImagenProducto                     │   │
│  │  - CapacidadProduccion, MetodoPago                   │   │
│  └──────────────────────────────────────────────────────┘   │
└────────────────────┬────────────────────────────────────────┘
                     │ Eloquent Query Builder
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                   BASE DE DATOS (MySQL 8.0)                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   usuarios   │  │  productos   │  │  categorias  │      │
│  │   pedidos    │  │  imagenes    │  │   detalles   │      │
│  │   metodos    │  │  capacidad   │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│                                                              │
│  Motor: InnoDB (transacciones ACID)                         │
│  Charset: utf8mb4 (soporte Unicode completo)                │
└─────────────────────────────────────────────────────────────┘
```

### Flujo de Datos

```
Usuario → Frontend → API REST → Backend Controller → Model → DB
  ↓                                                              ↓
  ←────────────────← JSON Response ←──────────────────────────←
```

---

## 🔧 Tecnologías Utilizadas

### Backend Stack

| Componente | Tecnología | Versión | Propósito |
|------------|------------|---------|-----------|
| **Framework** | Laravel | 12.x | Framework PHP robusto y seguro |
| **Lenguaje** | PHP | 8.2+ | Lenguaje de programación del backend |
| **Base de Datos** | MySQL | 8.0 | Sistema de gestión de base de datos relacional |
| **Autenticación** | Laravel Sanctum | 4.x | Sistema de autenticación SPA con tokens |
| **ORM** | Eloquent | 12.x | Mapeo objeto-relacional |
| **Servidor Web** | Nginx/Apache | - | Servidor HTTP |
| **Contenedor** | Docker (Sail) | - | Entorno de desarrollo aislado |
| **Gestor de Paquetes** | Composer | 2.x | Gestión de dependencias PHP |

### Frontend Stack

| Componente | Tecnología | Versión | Propósito |
|------------|------------|---------|-----------|
| **Framework** | React | 18.x | Biblioteca para construir interfaces de usuario |
| **Bundler** | Vite | 4.5.14 | Herramienta de construcción ultra-rápida |
| **Enrutamiento** | React Router | 6.x | Navegación SPA |
| **Estilos** | Bootstrap + CSS | 5.x | Framework CSS responsive |
| **Animaciones** | Framer Motion | 11.x | Animaciones fluidas y modernas |
| **Iconos** | Lucide React | - | Iconos SVG optimizados |
| **HTTP Client** | Axios | 1.x | Cliente HTTP para peticiones API |
| **SEO** | React Helmet Async | - | Gestión de metadatos |
| **Notificaciones** | React Toastify | - | Alertas y mensajes al usuario |
| **Gestor de Paquetes** | npm | 10.x | Gestión de dependencias JavaScript |

### Infraestructura

| Componente | Tecnología | Propósito |
|------------|------------|-----------|
| **Contenedores** | Docker Compose | Orquestación de servicios |
| **Servidor DB** | MySQL Container | Base de datos en contenedor |
| **Servidor PHP** | PHP-FPM | Procesamiento PHP optimizado |
| **Caché** | Redis (opcional) | Caché de sesiones y datos |

---

## 🗄️ Base de Datos

### Diagrama Entidad-Relación

```
┌─────────────────┐         ┌─────────────────┐
│     USERS       │         │   CATEGORIAS    │
├─────────────────┤         ├─────────────────┤
│ id (PK)         │         │ id (PK)         │
│ name            │         │ nombre          │
│ email (unique)  │         │ descripcion     │
│ password        │         │ orden           │
│ role            │         │ activo          │
│ created_at      │         │ created_at      │
│ updated_at      │         │ updated_at      │
└────────┬────────┘         └────────┬────────┘
         │                           │
         │                           │ 1:N
         │                           ▼
         │                  ┌─────────────────┐
         │                  │   PRODUCTOS     │
         │                  ├─────────────────┤
         │                  │ id (PK)         │
         │                  │ categoria_id(FK)│
         │                  │ nombre          │
         │                  │ descripcion     │
         │                  │ precio          │
         │                  │ stock           │
         │                  │ tiene_limite    │
         │                  │ activo          │
         │                  │ created_at      │
         │                  │ updated_at      │
         │                  └────────┬────────┘
         │                           │
         │                           │ 1:N
         │                           ▼
         │                  ┌─────────────────┐
         │                  │ IMAGENES_PROD   │
         │                  ├─────────────────┤
         │                  │ id (PK)         │
         │                  │ producto_id(FK) │
         │                  │ ruta            │
         │                  │ es_principal    │
         │                  │ orden           │
         │                  └─────────────────┘
         │
         │ 1:N
         ▼
┌─────────────────┐
│    PEDIDOS      │
├─────────────────┤         ┌─────────────────┐
│ id (PK)         │         │ METODOS_PAGO    │
│ user_id (FK)    │         ├─────────────────┤
│ metodo_pago(FK) │────────▶│ id (PK)         │
│ total           │         │ nombre          │
│ estado          │         │ activo          │
│ fecha_entrega   │         └─────────────────┘
│ direccion       │
│ notas           │
│ created_at      │
│ updated_at      │
└────────┬────────┘
         │
         │ 1:N
         ▼
┌─────────────────┐
│ DETALLE_PEDIDO  │
├─────────────────┤
│ id (PK)         │
│ pedido_id (FK)  │
│ producto_id(FK) │────────▶ PRODUCTOS
│ cantidad        │
│ precio_unitario │
│ subtotal        │
└─────────────────┘

┌─────────────────┐
│ CAPACIDAD_PROD  │
├─────────────────┤
│ id (PK)         │
│ producto_id(FK) │────────▶ PRODUCTOS
│ fecha           │
│ cantidad_max    │
│ cantidad_res    │
│ activo          │
└─────────────────┘
```

### Descripción de Tablas

#### **USERS** (Usuarios del sistema)
- Almacena información de usuarios (clientes y administradores)
- Autenticación mediante email y contraseña (hash bcrypt)
- Campo `role` para diferenciar permisos (admin, cliente)

#### **CATEGORIAS** (Categorías de productos)
- Clasificación de productos (Pan, Pasteles, Galletas, etc.)
- Campo `orden` para controlar la visualización
- Campo `activo` para habilitar/deshabilitar

#### **PRODUCTOS** (Catálogo de productos)
- Información completa del producto
- Relación con categoría
- Control de stock
- Campo `tiene_limite_produccion` para productos con capacidad limitada

#### **IMAGENES_PRODUCTO** (Imágenes de productos)
- Múltiples imágenes por producto
- Campo `es_principal` marca la imagen de portada
- Campo `orden` controla la secuencia

#### **PEDIDOS** (Órdenes de compra)
- Registro de pedidos de clientes
- Estados: pendiente, confirmado, en_proceso, completado, cancelado
- Fecha de entrega programada

#### **DETALLE_PEDIDO** (Items del pedido)
- Productos incluidos en cada pedido
- Captura precio al momento del pedido (histórico)
- Cálculo de subtotales

#### **METODOS_PAGO** (Formas de pago)
- Efectivo, Transferencia, Tarjeta, etc.
- Configurables y habilitables

#### **CAPACIDAD_PRODUCCION** (Límites diarios)
- Control de producción diaria por producto
- Reservas automáticas al realizar pedidos

### Características de la Base de Datos

#### **Integridad Referencial**
```sql
-- Ejemplo de foreign key con restricciones
ALTER TABLE productos 
ADD CONSTRAINT fk_productos_categoria 
FOREIGN KEY (categoria_id) 
REFERENCES categorias(id) 
ON DELETE RESTRICT 
ON UPDATE CASCADE;
```

#### **Índices para Optimización**
```sql
-- Índices definidos en las migraciones
INDEX idx_productos_categoria (categoria_id)
INDEX idx_productos_activo (activo)
INDEX idx_pedidos_usuario (user_id)
INDEX idx_pedidos_estado (estado)
UNIQUE INDEX idx_users_email (email)
```

#### **Transacciones ACID**
- Motor InnoDB garantiza:
  - **Atomicidad**: Todas las operaciones o ninguna
  - **Consistencia**: Estado válido siempre
  - **Aislamiento**: Transacciones independientes
  - **Durabilidad**: Cambios permanentes

---

## 🔌 Backend - API REST

### Estructura del Backend

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Controladores de la API
│   │   │   ├── AuthController.php
│   │   │   ├── ProductoController.php
│   │   │   ├── PedidoController.php
│   │   │   └── InventarioController.php
│   │   └── Middleware/         # Middlewares personalizados
│   │       ├── Authenticate.php
│   │       └── Cors.php
│   ├── Models/                 # Modelos Eloquent
│   │   ├── User.php
│   │   ├── Producto.php
│   │   ├── Pedido.php
│   │   ├── DetallePedido.php
│   │   ├── Categoria.php
│   │   ├── ImagenProducto.php
│   │   ├── MetodoPago.php
│   │   └── CapacidadProduccion.php
│   └── Providers/
│       └── AppServiceProvider.php
├── config/                     # Configuraciones
│   ├── database.php           # Configuración de DB
│   ├── sanctum.php            # Configuración de auth
│   └── cors.php               # Políticas CORS
├── database/
│   ├── migrations/            # Migraciones de BD
│   └── seeders/              # Datos iniciales
├── routes/
│   ├── api.php               # Rutas de la API
│   └── web.php               # Rutas web
└── public/
    ├── index.php             # Punto de entrada
    └── app/                  # Build del frontend (React)
```

### Endpoints de la API

#### **Autenticación** (`/api/auth`)

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| POST | `/api/login` | Iniciar sesión | No |
| POST | `/api/register` | Registrar usuario | No |
| POST | `/api/logout` | Cerrar sesión | Sí |
| GET | `/api/user` | Datos del usuario autenticado | Sí |

**Ejemplo Request - Login:**
```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@panificadoranancy.com",
  "password": "admin123"
}
```

**Ejemplo Response:**
```json
{
  "access_token": "1|xyz123abc456def789...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Administrador",
    "email": "admin@panificadoranancy.com",
    "role": "admin"
  }
}
```

#### **Productos** (`/api/inventario`)

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/inventario/productos-finales` | Listar productos activos | No |
| GET | `/api/inventario/productos/{id}` | Detalle de producto | No |
| POST | `/api/inventario/productos` | Crear producto | Admin |
| PUT | `/api/inventario/productos/{id}` | Actualizar producto | Admin |
| DELETE | `/api/inventario/productos/{id}` | Eliminar producto | Admin |
| GET | `/api/inventario/categorias` | Listar categorías | No |

**Ejemplo Response - Lista de Productos:**
```json
[
  {
    "id": 1,
    "nombre": "Pan Francés",
    "descripcion": "Pan tradicional recién horneado",
    "precio": "2.50",
    "stock": 50,
    "categoria": {
      "id": 1,
      "nombre": "Panes"
    },
    "imagenes": [
      {
        "id": 1,
        "ruta": "/storage/productos/pan-frances.jpg",
        "es_principal": true
      }
    ],
    "tiene_limite_produccion": true,
    "capacidad_disponible": 30
  }
]
```

#### **Pedidos** (`/api/pedidos`)

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/pedidos` | Mis pedidos | Usuario |
| POST | `/api/pedidos` | Crear pedido | Usuario |
| GET | `/api/pedidos/{id}` | Detalle de pedido | Usuario |
| PUT | `/api/pedidos/{id}/estado` | Cambiar estado | Admin |

**Ejemplo Request - Crear Pedido:**
```http
POST /api/pedidos
Authorization: Bearer {token}
Content-Type: application/json

{
  "productos": [
    {
      "producto_id": 1,
      "cantidad": 5
    },
    {
      "producto_id": 3,
      "cantidad": 2
    }
  ],
  "metodo_pago_id": 1,
  "fecha_entrega": "2025-10-15",
  "direccion": "Av. Martín Cardenas, Quillacollo",
  "notas": "Entregar en la mañana"
}
```

### Seguridad en el Backend

#### **Laravel Sanctum**
```php
// Protección de rutas con middleware
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/pedidos', [PedidoController::class, 'store']);
});
```

#### **Validación de Datos**
```php
// Ejemplo de validación en controlador
public function store(Request $request) {
    $validated = $request->validate([
        'nombre' => 'required|string|max:255',
        'precio' => 'required|numeric|min:0',
        'stock' => 'required|integer|min:0',
        'categoria_id' => 'required|exists:categorias,id'
    ]);
    
    return Producto::create($validated);
}
```

#### **CORS (Cross-Origin Resource Sharing)**
```php
// config/cors.php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:5174', 'https://panificadoranancy.com'],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
'allowed_headers' => ['Content-Type', 'Authorization'],
```

#### **Rate Limiting**
```php
// Límite de peticiones por minuto
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});
```

---

## 🎨 Frontend - Aplicación Web

### Estructura del Frontend

```
frontend/
├── public/
│   └── images/              # Imágenes estáticas
│       ├── logo.jpg
│       ├── cabecera.jpg
│       └── productos/
├── src/
│   ├── components/          # Componentes reutilizables
│   │   ├── Header.jsx
│   │   ├── Footer.jsx
│   │   ├── ProductCard.jsx
│   │   ├── ProductModal.jsx
│   │   └── PrivateRoute.jsx
│   ├── context/            # Context API
│   │   ├── AuthContext.jsx    # Estado de autenticación
│   │   ├── CartContext.jsx    # Estado del carrito
│   │   └── SEOContext.jsx     # SEO y metadatos
│   ├── pages/              # Páginas/Vistas
│   │   ├── Home.jsx
│   │   ├── Productos.jsx
│   │   ├── Carrito.jsx
│   │   ├── Login.jsx
│   │   ├── Perfil.jsx
│   │   ├── Contacto.jsx
│   │   ├── Nosotros.jsx
│   │   └── Admin/
│   │       ├── Dashboard.jsx
│   │       └── GestionProductos.jsx
│   ├── services/           # Servicios de API
│   │   └── api.js
│   ├── styles/            # Estilos CSS
│   │   ├── Footer.css
│   │   └── Contacto.css
│   ├── estilos.css       # Estilos globales
│   ├── App.jsx           # Componente raíz
│   └── main.jsx          # Punto de entrada
├── index.html
├── package.json
└── vite.config.js
```

### Arquitectura de Componentes

```
App
├── AuthProvider (Context)
│   └── CartProvider (Context)
│       └── SEOProvider (Context)
│           └── BrowserRouter
│               ├── Header
│               ├── Routes
│               │   ├── Home
│               │   │   ├── Hero Section
│               │   │   ├── ProductCard[]
│               │   │   └── Footer
│               │   ├── Productos
│               │   │   ├── Filtros
│               │   │   ├── ProductCard[]
│               │   │   └── ProductModal
│               │   ├── Carrito
│               │   │   ├── CartItem[]
│               │   │   └── Checkout
│               │   ├── Login/Register
│               │   ├── Perfil (Protected)
│               │   ├── Admin (Protected)
│               │   ├── Contacto
│               │   └── Nosotros
│               └── Footer
```

### Gestión de Estado

#### **AuthContext** - Autenticación
```jsx
const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Verificar token al cargar
    const token = localStorage.getItem('token');
    if (token) {
      fetchUser(token);
    }
  }, []);

  const login = async (credentials) => {
    const { access_token, user } = await api.post('/login', credentials);
    localStorage.setItem('token', access_token);
    setUser(user);
  };

  return (
    <AuthContext.Provider value={{ user, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};
```

#### **CartContext** - Carrito de Compras
```jsx
export const CartProvider = ({ children }) => {
  const [cart, setCart] = useState([]);

  const addToCart = (producto, cantidad) => {
    setCart(prev => {
      const existing = prev.find(item => item.id === producto.id);
      if (existing) {
        return prev.map(item =>
          item.id === producto.id
            ? { ...item, cantidad: item.cantidad + cantidad }
            : item
        );
      }
      return [...prev, { ...producto, cantidad }];
    });
  };

  const total = cart.reduce(
    (sum, item) => sum + (item.precio * item.cantidad), 
    0
  );

  return (
    <CartContext.Provider value={{ cart, addToCart, removeFromCart, total }}>
      {children}
    </CartContext.Provider>
  );
};
```

### Comunicación con la API

#### **Servicio API** (`services/api.js`)
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Interceptor para agregar token
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor para manejar errores
api.interceptors.response.use(
  response => response.data,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      // Redirigir a login
    }
    return Promise.reject(error);
  }
);

export default api;
```

### Optimizaciones del Frontend

#### **Code Splitting**
```jsx
// Carga perezosa de rutas
const Admin = lazy(() => import('./pages/Admin/Dashboard'));

<Route path="/admin" element={
  <Suspense fallback={<Loading />}>
    <Admin />
  </Suspense>
} />
```

#### **Imágenes Optimizadas**
```jsx
// Lazy loading de imágenes
<img 
  src={producto.imagen} 
  alt={producto.nombre}
  loading="lazy"
  decoding="async"
/>
```

#### **SEO Optimization**
```jsx
import { Helmet } from 'react-helmet-async';

<Helmet>
  <title>Pan Francés - Panificadora Nancy</title>
  <meta name="description" content="Pan francés artesanal..." />
  <meta property="og:image" content="/images/productos/pan.jpg" />
</Helmet>
```

---

## 🔒 Seguridad

### Medidas de Seguridad Implementadas

#### **1. Autenticación y Autorización**

**Laravel Sanctum - Token Based Auth**
- ✅ Tokens SPA seguros almacenados en localStorage
- ✅ Tokens expirables configurables
- ✅ Revocación de tokens al logout
- ✅ Protección contra CSRF en cookies

```php
// Configuración en sanctum.php
'expiration' => 60 * 24, // 24 horas
'token_prefix' => 'panificadora_',
```

**Roles y Permisos**
```php
// Middleware personalizado
if ($user->role !== 'admin') {
    return response()->json(['error' => 'Unauthorized'], 403);
}
```

#### **2. Protección de Datos**

**Encriptación de Contraseñas**
```php
// Hash con bcrypt (cost factor 12)
use Illuminate\Support\Facades\Hash;

$user->password = Hash::make($password);
```

**Sanitización de Inputs**
```php
// Validación estricta
'email' => 'required|email|max:255',
'nombre' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
'precio' => 'required|numeric|min:0|max:999999.99',
```

**Prevención de SQL Injection**
```php
// Eloquent usa prepared statements automáticamente
Producto::where('categoria_id', $request->categoria_id)->get();

// Parámetros parametrizados
DB::table('productos')
  ->where('precio', '>', $minPrecio)
  ->get();
```

#### **3. Protección contra Ataques**

**XSS (Cross-Site Scripting)**
```jsx
// React escapa automáticamente el contenido
<div>{producto.descripcion}</div>

// Sanitización adicional si se usa dangerouslySetInnerHTML
import DOMPurify from 'dompurify';
<div dangerouslySetInnerHTML={{ 
  __html: DOMPurify.sanitize(html) 
}} />
```

**CSRF (Cross-Site Request Forgery)**
```php
// Laravel incluye protección CSRF
// Frontend debe incluir token en headers
axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
```

**Rate Limiting**
```php
// Throttle en rutas sensibles
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login');  // 5 intentos por minuto
    Route::post('/register');
});
```

**CORS Restrictivo**
```php
'allowed_origins' => [
    'https://panificadoranancy.com',
    'http://localhost:5174' // Solo dev
],
'supports_credentials' => true,
```

#### **4. Seguridad de Archivos**

**Validación de Uploads**
```php
$request->validate([
    'imagen' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
]);

// Nombres aleatorios para evitar sobrescritura
$filename = Str::random(40) . '.' . $file->extension();
```

**Almacenamiento Seguro**
```php
// Fuera del directorio público
Storage::disk('private')->put('productos/' . $filename, $file);

// Servir mediante controlador con autorización
Route::get('/storage/private/{file}', [FileController::class, 'serve'])
    ->middleware('auth:sanctum');
```

#### **5. Headers de Seguridad**

```apache
# .htaccess o configuración de servidor
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header set Content-Security-Policy "default-src 'self'"
```

### Auditoría y Logging

```php
// Log de acciones críticas
Log::info('Usuario autenticado', [
    'user_id' => $user->id,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent()
]);

Log::warning('Intento de acceso no autorizado', [
    'user_id' => $user->id,
    'recurso' => $request->path()
]);
```

---

## 📈 Escalabilidad

### Diseño Escalable

El sistema está diseñado para crecer según las necesidades del negocio:

#### **1. Escalabilidad Horizontal (Más Servidores)**

**Arquitectura Stateless**
```
            Load Balancer (Nginx)
                    │
        ┌───────────┼───────────┐
        ▼           ▼           ▼
    Server 1    Server 2    Server 3
        │           │           │
        └───────────┴───────────┘
                    │
             Shared Database
                    │
             Redis Cache
```

**Características que lo permiten:**
- ✅ Tokens en lugar de sesiones de servidor
- ✅ Base de datos centralizada
- ✅ Archivos en storage compartido (S3, etc.)
- ✅ Sin estado en los servidores de aplicación

#### **2. Escalabilidad Vertical (Más Recursos)**

**Optimización de Consultas**
```php
// Eager Loading para evitar N+1 queries
$productos = Producto::with(['categoria', 'imagenes'])
    ->where('activo', true)
    ->get();

// En vez de:
foreach ($productos as $producto) {
    $producto->categoria; // Query adicional cada vez
}
```

**Paginación**
```php
// API paginada
$productos = Producto::paginate(20);

return response()->json([
    'data' => $productos->items(),
    'total' => $productos->total(),
    'per_page' => $productos->perPage(),
    'current_page' => $productos->currentPage()
]);
```

#### **3. Caché de Datos**

**Redis Cache**
```php
use Illuminate\Support\Facades\Cache;

// Cachear productos por 1 hora
$productos = Cache::remember('productos.activos', 3600, function () {
    return Producto::where('activo', true)->get();
});

// Invalidar caché al actualizar
Cache::forget('productos.activos');
```

**Frontend Caching**
```jsx
// React Query para caché en frontend
import { useQuery } from '@tanstack/react-query';

const { data: productos } = useQuery({
  queryKey: ['productos'],
  queryFn: () => api.get('/productos'),
  staleTime: 5 * 60 * 1000, // 5 minutos
  cacheTime: 10 * 60 * 1000 // 10 minutos
});
```

#### **4. CDN para Recursos Estáticos**

```
Usuario → CDN (Cloudflare/AWS CloudFront) → Imágenes, CSS, JS
         ↓ (Cache Miss)
         Backend Server
```

**Configuración**
```javascript
// vite.config.js
export default {
  build: {
    assetsDir: 'static',
    rollupOptions: {
      output: {
        assetFileNames: 'static/[name].[hash][extname]'
      }
    }
  }
};
```

#### **5. Queue System (Sistema de Colas)**

Para tareas pesadas o asíncronas:

```php
// Laravel Queues
use App\Jobs\ProcessOrder;

// Encolar tarea
ProcessOrder::dispatch($pedido);

// Worker procesa en background
php artisan queue:work
```

**Casos de uso:**
- 📧 Envío de emails de confirmación
- 🖼️ Procesamiento de imágenes (resize, optimize)
- 📊 Generación de reportes
- 🔔 Notificaciones push

#### **6. Database Optimization**

**Read Replicas**
```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => ['192.168.1.2', '192.168.1.3'],
    ],
    'write' => [
        'host' => ['192.168.1.1'],
    ],
],
```

**Índices Estratégicos**
```sql
-- Índices compuestos para consultas frecuentes
CREATE INDEX idx_productos_categoria_activo 
ON productos(categoria_id, activo);

CREATE INDEX idx_pedidos_usuario_fecha 
ON pedidos(user_id, created_at DESC);
```

### Métricas de Rendimiento Actual

| Métrica | Valor | Objetivo |
|---------|-------|----------|
| Tiempo de carga inicial | ~1.5s | < 2s |
| Bundle JS (gzip) | 181 KB | < 200 KB |
| Bundle CSS (gzip) | 40 KB | < 50 KB |
| API Response Time | ~50ms | < 100ms |
| Database Queries | ~3-5 por página | < 10 |

---

## ⚙️ Funcionalidades

### Módulo Público (Clientes)

#### **1. Catálogo de Productos**
- ✅ Navegación por categorías
- ✅ Búsqueda de productos
- ✅ Filtros (precio, categoría, disponibilidad)
- ✅ Vista de detalle con imágenes
- ✅ Información nutricional y descripción

#### **2. Carrito de Compras**
- ✅ Agregar/quitar productos
- ✅ Actualizar cantidades
- ✅ Cálculo automático de totales
- ✅ Persistencia en localStorage
- ✅ Verificación de stock en tiempo real

#### **3. Sistema de Pedidos**
- ✅ Selección de método de pago
- ✅ Programación de fecha de entrega
- ✅ Dirección de envío/retiro
- ✅ Notas especiales
- ✅ Confirmación por email

#### **4. Gestión de Cuenta**
- ✅ Registro de usuarios
- ✅ Login/Logout seguro
- ✅ Perfil editable
- ✅ Historial de pedidos
- ✅ Seguimiento de estado

#### **5. Información Institucional**
- ✅ Página "Nosotros" con historia
- ✅ Página de contacto con formulario
- ✅ Integración con redes sociales
- ✅ Ubicación en Google Maps
- ✅ Información de contacto

### Módulo Administrativo

#### **1. Dashboard**
- ✅ Resumen de ventas del día
- ✅ Pedidos pendientes
- ✅ Productos con bajo stock
- ✅ Estadísticas de clientes

#### **2. Gestión de Productos**
- ✅ CRUD completo de productos
- ✅ Múltiples imágenes por producto
- ✅ Control de stock
- ✅ Activar/desactivar productos
- ✅ Precios y descuentos

#### **3. Gestión de Pedidos**
- ✅ Lista de todos los pedidos
- ✅ Cambio de estados
- ✅ Detalles completos
- ✅ Filtros por fecha, estado, cliente
- ✅ Impresión de comprobantes

#### **4. Gestión de Inventario**
- ✅ Control de stock en tiempo real
- ✅ Alertas de stock mínimo
- ✅ Historial de movimientos
- ✅ Capacidad de producción diaria

#### **5. Gestión de Categorías**
- ✅ CRUD de categorías
- ✅ Ordenamiento personalizado
- ✅ Activación/desactivación

#### **6. Reportes**
- ✅ Ventas por período
- ✅ Productos más vendidos
- ✅ Clientes frecuentes
- ✅ Exportación a Excel/PDF

### Características Técnicas Destacadas

#### **Responsive Design**
```css
/* Mobile First */
.product-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

/* Tablet */
@media (min-width: 768px) {
  .product-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Desktop */
@media (min-width: 1024px) {
  .product-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}
```

#### **Animaciones Suaves**
```jsx
// Framer Motion
<motion.div
  initial={{ opacity: 0, y: 20 }}
  animate={{ opacity: 1, y: 0 }}
  transition={{ duration: 0.3 }}
  whileHover={{ scale: 1.05 }}
  whileTap={{ scale: 0.95 }}
>
  <ProductCard />
</motion.div>
```

#### **SEO Optimizado**
```jsx
<Helmet>
  <title>Panificadora Nancy - Pan Artesanal en Cochabamba</title>
  <meta name="description" content="Más de 30 años elaborando..." />
  <meta name="keywords" content="pan, pastelería, Cochabamba" />
  
  {/* Open Graph */}
  <meta property="og:title" content="Panificadora Nancy" />
  <meta property="og:image" content="/images/logo.jpg" />
  
  {/* Schema.org */}
  <script type="application/ld+json">
    {JSON.stringify({
      "@context": "https://schema.org",
      "@type": "Bakery",
      "name": "Panificadora Nancy",
      "telephone": "+591-764-90687"
    })}
  </script>
</Helmet>
```

---

## 🚀 Despliegue

### Entorno de Desarrollo

#### **Requisitos**
- Docker Desktop (Windows/Mac) o Docker Engine (Linux)
- Git
- Node.js 18+ (para desarrollo frontend sin Docker)

#### **Instalación Rápida**

```bash
# 1. Clonar repositorio
git clone https://github.com/VMKayser/Panificadora_Nancy.git
cd Panificadora_Nancy

# 2. Backend - Instalar dependencias
cd backend
composer install
cp .env.example .env

# 3. Configurar .env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# 4. Levantar contenedores Docker
./vendor/bin/sail up -d

# 5. Ejecutar migraciones
./vendor/bin/sail artisan migrate

# 6. Generar datos de prueba (opcional)
./vendor/bin/sail artisan db:seed

# 7. Frontend - Instalar dependencias
cd ../frontend
npm install

# 8. Desarrollo con Hot Reload
npm run dev

# O construir para producción
npm run build
cp -r dist ../backend/public/app
```

#### **Acceso**
- Frontend Dev: http://localhost:5174/app
- Frontend Prod: http://localhost/app
- API: http://localhost/api
- Base de Datos: localhost:3306

### Entorno de Producción

#### **Opción 1: VPS (DigitalOcean, Linode, AWS EC2)**

**1. Preparar Servidor**
```bash
# Ubuntu 22.04 LTS
sudo apt update && sudo apt upgrade -y

# Instalar requisitos
sudo apt install -y nginx mysql-server php8.2-fpm php8.2-mysql \
  php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip \
  git composer nodejs npm certbot python3-certbot-nginx
```

**2. Configurar Nginx**
```nginx
# /etc/nginx/sites-available/panificadora
server {
    listen 80;
    server_name panificadoranancy.com www.panificadoranancy.com;
    root /var/www/panificadora/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    # Frontend SPA
    location /app {
        try_files $uri $uri/ /app/index.html;
    }

    # API Backend
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**3. SSL con Let's Encrypt**
```bash
sudo certbot --nginx -d panificadoranancy.com -d www.panificadoranancy.com
```

**4. Deploy Script**
```bash
#!/bin/bash
# deploy.sh

cd /var/www/panificadora

# Backend
cd backend
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

# Frontend
cd ../frontend
npm install
npm run build
rm -rf ../backend/public/app
cp -r dist ../backend/public/app

# Permisos
sudo chown -R www-data:www-data /var/www/panificadora
sudo chmod -R 755 /var/www/panificadora

# Reiniciar servicios
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx

echo "✅ Despliegue completado"
```

#### **Opción 2: Plataforma como Servicio (Heroku, Railway, Render)**

**Railway.app (Recomendado para Laravel)**

```yaml
# railway.json
{
  "build": {
    "builder": "NIXPACKS",
    "buildCommand": "cd backend && composer install --no-dev && cd ../frontend && npm install && npm run build && cp -r dist ../backend/public/app"
  },
  "deploy": {
    "startCommand": "cd backend && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT",
    "restartPolicyType": "ON_FAILURE"
  }
}
```

#### **Opción 3: Contenedores (Docker Swarm, Kubernetes)**

**docker-compose.prod.yml**
```yaml
version: '3.8'

services:
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./backend/public:/var/www/public
    depends_on:
      - php

  php:
    build:
      context: ./backend
      dockerfile: Dockerfile.prod
    volumes:
      - ./backend:/var/www
    environment:
      DB_HOST: mysql
      DB_DATABASE: ${DB_DATABASE}
      DB_USERNAME: ${DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD}

  mysql:
    image: mysql:8.0
    volumes:
      - mysql_data:/var/lib/mysql
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}

  redis:
    image: redis:alpine

volumes:
  mysql_data:
```

### Monitoreo y Logs

#### **Laravel Logging**
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

#### **Monitoreo de Aplicación**
- **Sentry**: Tracking de errores en tiempo real
- **New Relic**: APM (Application Performance Monitoring)
- **Laravel Telescope**: Debug tool (solo desarrollo)

```bash
# Instalar Sentry
composer require sentry/sentry-laravel

# Configurar
SENTRY_LARAVEL_DSN=https://xxxxx@sentry.io/xxxxx
```

---

## 🔧 Mantenimiento

### Tareas Regulares

#### **Diarias**
- ✅ Revisar logs de errores
- ✅ Verificar disponibilidad del sitio
- ✅ Backup automático de base de datos

```bash
# Cron job para backup diario
0 2 * * * /usr/bin/mysqldump -u root laravel | gzip > /backups/db_$(date +\%Y\%m\%d).sql.gz
```

#### **Semanales**
- ✅ Revisar métricas de rendimiento
- ✅ Actualizar dependencias de seguridad
- ✅ Limpiar logs antiguos

```bash
# Limpiar logs de más de 30 días
find /var/www/panificadora/backend/storage/logs -name "*.log" -mtime +30 -delete
```

#### **Mensuales**
- ✅ Actualizar framework y librerías
- ✅ Optimizar base de datos
- ✅ Revisar y optimizar consultas lentas

```bash
# Laravel updates
composer update

# Optimizar BD
php artisan optimize
php artisan queue:restart
```

### Troubleshooting Común

#### **Problema: 500 Internal Server Error**
```bash
# Verificar logs
tail -f backend/storage/logs/laravel.log

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

#### **Problema: CORS Errors**
```php
// Verificar config/cors.php
'allowed_origins' => ['https://tu-dominio.com'],
'supports_credentials' => true,
```

#### **Problema: Database Connection Error**
```bash
# Verificar credenciales en .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306

# Test de conexión
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## 📊 Información de Contacto del Proyecto

**Negocio:** Panificadora Nancy  
**Ubicación:** HPW9+J94, Av. Martín Cardenas, Quillacollo, Cochabamba, Bolivia  
**Teléfono:** +591 764 90687  
**WhatsApp:** https://wa.me/59176490687  
**Facebook:** https://www.facebook.com/profile.php?id=61557646906876  
**Instagram:** https://www.instagram.com/panificadora_nancy01  

**Desarrollador:** VMKayser  
**Repositorio:** https://github.com/VMKayser/Panificadora_Nancy  
**Versión:** 1.0.0  
**Última Actualización:** Octubre 2025  

---

## 📝 Licencia y Uso

Este sistema ha sido desarrollado específicamente para **Panificadora Nancy**. 

### Estructura de Archivos del Proyecto

```
Panificadora_Nancy/
├── backend/                    # API Laravel
├── frontend/                   # React SPA
├── Instrucciones contenedor/   # Guías de setup
├── DOCUMENTACION_TECNICA.md   # Este archivo
└── README.md                   # Guía de inicio rápido
```

---

**Fin de la Documentación Técnica**

*Para consultas técnicas o soporte, contactar al desarrollador.*
