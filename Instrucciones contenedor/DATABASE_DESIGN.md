# 📊 Diseño de Base de Datos - Panificadora Nancy

## 🎯 Objetivo
Esta base de datos soporta un sistema completo de e-commerce con gestión de inventario, pedidos, pagos y roles de usuario.

---

## 📋 Lista de Tablas (16 tablas)

### **Módulo de Usuarios y Autenticación**
1. `users` - Usuarios del sistema (clientes, vendedores, admins)
2. `roles` - Roles del sistema
3. `role_user` - Relación usuarios-roles (tabla pivote)

### **Módulo de Productos**
4. `categories` - Categorías de productos
5. `products` - Productos disponibles
6. `product_images` - Imágenes de productos

### **Módulo de Inventario**
7. `inventory_products` - Stock de productos terminados
8. `inventory_materials` - Stock de materia prima (opcional para v2)

### **Módulo de Capacidad de Producción**
9. `production_capacity` - Límites de producción semanal
10. `production_bookings` - Reservas de cupos por pedido

### **Módulo de Pedidos y Ventas**
11. `orders` - Pedidos/órdenes de compra
12. `order_items` - Productos dentro de cada pedido
13. `shipping_addresses` - Direcciones de envío de clientes

### **Módulo de Pagos**
14. `payments` - Registro de pagos realizados
15. `payment_methods` - Métodos de pago disponibles

### **Módulo de Configuración**
16. `settings` - Configuraciones globales del sistema

---

## 🗂️ Descripción Detallada de Tablas

### 1. **users** (Usuarios)
```sql
Almacena todos los usuarios del sistema: clientes, vendedores y administradores.

Campos:
- id (PK)
- name (VARCHAR) - Nombre completo
- email (VARCHAR UNIQUE) - Email para login
- password (VARCHAR) - Contraseña encriptada
- phone (VARCHAR NULLABLE) - Teléfono de contacto
- is_active (BOOLEAN) - Si la cuenta está activa
- email_verified_at (TIMESTAMP NULLABLE)
- created_at, updated_at

Relaciones:
- Tiene muchos: orders, shipping_addresses
- Pertenece a muchos: roles (tabla pivote role_user)
```

---

### 2. **roles** (Roles)
```sql
Define los 3 roles del sistema.

Campos:
- id (PK)
- name (VARCHAR UNIQUE) - 'admin', 'vendedor', 'cliente'
- description (TEXT NULLABLE)
- created_at, updated_at

Datos iniciales (Seeder):
1. admin - Administrador total
2. vendedor - Personal de tienda
3. cliente - Usuario comprador

Relaciones:
- Pertenece a muchos: users
```

---

### 3. **role_user** (Tabla Pivote)
```sql
Relaciona usuarios con sus roles (un usuario puede tener varios roles).

Campos:
- id (PK)
- user_id (FK → users.id)
- role_id (FK → roles.id)
- created_at, updated_at
```

---

### 4. **categories** (Categorías)
```sql
Categorías de productos (Panes, Empanadas, Temporada, etc.).

Campos:
- id (PK)
- name (VARCHAR) - Nombre de la categoría
- slug (VARCHAR UNIQUE) - URL amigable (ej: 'panes')
- description (TEXT NULLABLE)
- image (VARCHAR NULLABLE) - Imagen de la categoría
- is_active (BOOLEAN) - Si está visible
- order (INTEGER) - Orden de visualización
- created_at, updated_at

Relaciones:
- Tiene muchos: products
```

---

### 5. **products** (Productos)
```sql
Todos los productos de la panadería.

Campos:
- id (PK)
- category_id (FK → categories.id)
- name (VARCHAR) - Nombre del producto
- slug (VARCHAR UNIQUE) - URL amigable
- description (TEXT) - Descripción completa
- short_description (VARCHAR NULLABLE) - Descripción corta para cards
- price_retail (DECIMAL 8,2) - Precio minorista en Bs.
- price_wholesale (DECIMAL 8,2 NULLABLE) - Precio mayorista en Bs.
- min_wholesale_qty (INTEGER NULLABLE) - Cantidad mínima para precio mayorista
- is_seasonal (BOOLEAN) - Si es producto de temporada
- is_active (BOOLEAN) - Si está disponible para venta
- requires_advance_order (BOOLEAN) - Si requiere pedido anticipado
- advance_hours (INTEGER NULLABLE) - Horas de anticipación requeridas
- has_capacity_limit (BOOLEAN) - Si tiene límite de producción semanal
- created_at, updated_at

Relaciones:
- Pertenece a: category
- Tiene muchos: product_images, inventory_products, order_items, production_capacity
```

---

### 6. **product_images** (Imágenes de Productos)
```sql
Múltiples imágenes por producto.

Campos:
- id (PK)
- product_id (FK → products.id)
- image_url (VARCHAR) - Ruta de la imagen
- is_primary (BOOLEAN) - Si es la imagen principal
- order (INTEGER) - Orden de visualización
- created_at, updated_at

Relaciones:
- Pertenece a: product
```

---

### 7. **inventory_products** (Inventario de Productos)
```sql
Control de stock de productos terminados.

Campos:
- id (PK)
- product_id (FK → products.id UNIQUE)
- quantity (INTEGER) - Cantidad disponible
- min_stock (INTEGER) - Stock mínimo (para alertas)
- last_restock_date (TIMESTAMP NULLABLE)
- created_at, updated_at

Relaciones:
- Pertenece a: product
```

---

### 8. **inventory_materials** (Inventario de Materia Prima)
```sql
Control de insumos (OPCIONAL - puede implementarse en v2).

Campos:
- id (PK)
- name (VARCHAR) - Nombre del insumo (ej: "Harina")
- unit (VARCHAR) - Unidad de medida (kg, L, unidades)
- quantity (DECIMAL 10,2) - Cantidad disponible
- min_stock (DECIMAL 10,2) - Stock mínimo
- cost_per_unit (DECIMAL 8,2) - Costo por unidad
- created_at, updated_at
```

---

### 9. **production_capacity** (Capacidad de Producción)
```sql
Define límites de producción semanal para productos específicos.

Campos:
- id (PK)
- product_id (FK → products.id)
- max_per_week (INTEGER) - Máximo de unidades producibles por semana
- current_week_bookings (INTEGER) - Cupos reservados esta semana
- week_start_date (DATE) - Inicio de la semana actual
- created_at, updated_at

Relaciones:
- Pertenece a: product
- Tiene muchos: production_bookings

Lógica:
- Se resetea automáticamente cada lunes
- No permite pedidos si current_week_bookings >= max_per_week
```

---

### 10. **production_bookings** (Reservas de Cupos)
```sql
Registra qué pedidos reservaron cupos de producción.

Campos:
- id (PK)
- production_capacity_id (FK → production_capacity.id)
- order_id (FK → orders.id)
- quantity_reserved (INTEGER) - Cuántas unidades reservó
- week_date (DATE) - Semana para la cual reservó
- created_at, updated_at

Relaciones:
- Pertenece a: production_capacity, order
```

---

### 11. **orders** (Pedidos)
```sql
Todos los pedidos del sistema (online y tienda física).

Campos:
- id (PK)
- user_id (FK → users.id NULLABLE) - NULL si es venta sin registro
- order_number (VARCHAR UNIQUE) - Número de pedido (ej: "PAN-2025-0001")
- status (ENUM) - 'pendiente', 'confirmado', 'en_preparacion', 'listo', 'enviado', 'entregado', 'cancelado'
- subtotal (DECIMAL 10,2) - Suma de productos
- discount (DECIMAL 10,2 DEFAULT 0) - Descuentos aplicados
- shipping_cost (DECIMAL 8,2 DEFAULT 0) - Costo de envío (si aplica)
- total (DECIMAL 10,2) - Total final
- payment_status (ENUM) - 'pendiente', 'pagado', 'fallido', 'reembolsado'
- delivery_method (ENUM) - 'pickup' (recojo), 'delivery' (envío)
- delivery_date (DATE NULLABLE) - Fecha estimada de entrega/recojo
- shipping_address_id (FK → shipping_addresses.id NULLABLE)
- notes (TEXT NULLABLE) - Notas del cliente o vendedor
- source (ENUM) - 'online', 'pos' (punto de venta)
- requires_invoice (BOOLEAN DEFAULT FALSE) - Si solicita factura
- created_at, updated_at

Relaciones:
- Pertenece a: user, shipping_address
- Tiene muchos: order_items, payments, production_bookings
```

---

### 12. **order_items** (Ítems del Pedido)
```sql
Productos individuales dentro de cada pedido.

Campos:
- id (PK)
- order_id (FK → orders.id)
- product_id (FK → products.id)
- product_name (VARCHAR) - Guardamos nombre por si se edita el producto después
- quantity (INTEGER) - Cantidad comprada
- unit_price (DECIMAL 8,2) - Precio unitario al momento de la compra
- subtotal (DECIMAL 10,2) - quantity * unit_price
- created_at, updated_at

Relaciones:
- Pertenece a: order, product
```

---

### 13. **shipping_addresses** (Direcciones de Envío)
```sql
Direcciones guardadas de los clientes.

Campos:
- id (PK)
- user_id (FK → users.id)
- full_name (VARCHAR) - Nombre completo del receptor
- phone (VARCHAR) - Teléfono de contacto
- department (VARCHAR) - Departamento (La Paz, Cochabamba, etc.)
- city (VARCHAR) - Ciudad
- address_line (TEXT) - Dirección completa
- reference (TEXT NULLABLE) - Referencias adicionales
- is_default (BOOLEAN) - Si es la dirección predeterminada
- created_at, updated_at

Relaciones:
- Pertenece a: user
- Tiene muchos: orders
```

---

### 14. **payments** (Pagos)
```sql
Registro de todos los pagos recibidos.

Campos:
- id (PK)
- order_id (FK → orders.id)
- payment_method_id (FK → payment_methods.id)
- transaction_id (VARCHAR NULLABLE) - ID de la transacción externa (QR, tarjeta)
- amount (DECIMAL 10,2) - Monto pagado
- status (ENUM) - 'pending', 'completed', 'failed', 'refunded'
- payment_date (TIMESTAMP NULLABLE) - Cuándo se completó el pago
- qr_image_url (VARCHAR NULLABLE) - URL del QR generado (si aplica)
- metadata (JSON NULLABLE) - Datos adicionales de la pasarela
- created_at, updated_at

Relaciones:
- Pertenece a: order, payment_method
```

---

### 15. **payment_methods** (Métodos de Pago)
```sql
Métodos de pago disponibles.

Campos:
- id (PK)
- name (VARCHAR) - Nombre del método (ej: "QR Simple BNB")
- code (VARCHAR UNIQUE) - Código interno ('qr_simple', 'tarjeta', 'efectivo')
- is_active (BOOLEAN) - Si está habilitado
- requires_validation (BOOLEAN) - Si requiere validación automática
- created_at, updated_at

Datos iniciales:
1. QR Simple (BNB)
2. Tarjeta Crédito/Débito
3. Efectivo (para POS)
4. Transferencia Bancaria

Relaciones:
- Tiene muchos: payments
```

---

### 16. **settings** (Configuraciones)
```sql
Configuraciones globales del sistema (clave-valor).

Campos:
- id (PK)
- key (VARCHAR UNIQUE) - Nombre de la configuración
- value (TEXT) - Valor (puede ser JSON)
- type (ENUM) - 'boolean', 'string', 'number', 'json'
- description (TEXT NULLABLE)
- created_at, updated_at

Ejemplos de configuraciones:
- invoice_enabled (boolean) - Si se solicitan facturas
- shipping_national_enabled (boolean) - Si está habilitado envío nacional
- min_order_amount (number) - Monto mínimo de pedido
- business_phone (string) - Teléfono de contacto
- business_email (string) - Email de contacto
```

---

## 🔗 Diagrama de Relaciones (Resumen)

```
users (1) ──→ (N) orders
users (N) ──→ (N) roles [role_user]
users (1) ──→ (N) shipping_addresses

categories (1) ──→ (N) products

products (1) ──→ (N) product_images
products (1) ──→ (1) inventory_products
products (1) ──→ (1) production_capacity
products (1) ──→ (N) order_items

orders (1) ──→ (N) order_items
orders (1) ──→ (N) payments
orders (1) ──→ (N) production_bookings
orders (N) ──→ (1) shipping_addresses

production_capacity (1) ──→ (N) production_bookings

payment_methods (1) ──→ (N) payments
```

---

## 📚 Próximos Pasos

1. ✅ **Revisar y entender** cada tabla
2. 📝 **Crear migraciones en Laravel** (siguiente paso)
3. 🏗️ **Crear modelos** con sus relaciones
4. 🌱 **Crear seeders** con datos de prueba
5. 🔌 **Crear API** para consumir estos datos

---

## 💡 Conceptos que Aprenderás

- **Primary Key (PK)**: ID único de cada registro
- **Foreign Key (FK)**: Referencia a otra tabla
- **UNIQUE**: Valor que no se puede repetir
- **NULLABLE**: Campo opcional
- **ENUM**: Campo con valores predefinidos
- **Relaciones**:
  - 1:N (Uno a Muchos): Una categoría tiene muchos productos
  - N:N (Muchos a Muchos): Usuarios y roles (con tabla pivote)
  - 1:1 (Uno a Uno): Producto y su inventario

---

## 🎯 ¿Dudas sobre alguna tabla?

Puedes preguntarme:
- ¿Por qué se necesita esta tabla?
- ¿Cómo se relaciona con las demás?
- ¿Qué significa cada campo?

**¡Estás listo para el siguiente paso: Crear esto en Laravel!** 🚀
