# 📊 Sistema de Gestión de Empleados y Configuraciones

## Documentación del Módulo de Administración de Personal

---

## 🎯 Funcionalidades Implementadas

### 1. Gestión de Panaderos ✅

Sistema completo CRUD para administrar el personal de producción.

#### **Endpoints API - Panaderos**

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/admin/empleados/panaderos` | Listar todos los panaderos con filtros | Admin |
| POST | `/api/admin/empleados/panaderos` | Crear nuevo panadero | Admin |
| GET | `/api/admin/empleados/panaderos/{id}` | Ver detalle de un panadero | Admin |
| PUT | `/api/admin/empleados/panaderos/{id}` | Actualizar panadero | Admin |
| DELETE | `/api/admin/empleados/panaderos/{id}` | Eliminar panadero (soft delete) | Admin |
| POST | `/api/admin/empleados/panaderos/{id}/toggle-activo` | Activar/desactivar panadero | Admin |
| GET | `/api/admin/empleados/panaderos/estadisticas` | Estadísticas globales | Admin |

#### **Campos de Panadero**

```json
{
  "id": 1,
  "nombre": "Juan",
  "apellido": "Pérez",
  "email": "juan.perez@panificadoranancy.com",
  "telefono": "+591 12345678",
  "ci": "1234567",
  "direccion": "Calle Ejemplo #123",
  "fecha_ingreso": "2025-01-15",
  "turno": "mañana",  // mañana, tarde, noche, rotativo
  "especialidad": "pan",  // pan, reposteria, ambos
  "salario_base": 2500.00,
  "total_kilos_producidos": 1250,
  "total_unidades_producidas": 5000,
  "ultima_produccion": "2025-10-11",
  "activo": true,
  "observaciones": "Especialista en pan francés"
}
```

#### **Filtros Disponibles**

```
GET /api/admin/empleados/panaderos?activo=1&turno=mañana&especialidad=pan&buscar=juan&sort_by=nombre&sort_order=asc&per_page=15
```

- `activo`: Filtrar por estado (0 o 1)
- `turno`: Filtrar por turno (mañana, tarde, noche, rotativo)
- `especialidad`: Filtrar por especialidad (pan, reposteria, ambos)
- `buscar`: Buscar por nombre, apellido, CI o email
- `sort_by`: Ordenar por campo
- `sort_order`: Orden (asc o desc)
- `per_page`: Resultados por página

#### **Estadísticas de Panaderos**

```json
{
  "total_panaderos": 15,
  "panaderos_activos": 12,
  "panaderos_inactivos": 3,
  "por_turno": {
    "mañana": 5,
    "tarde": 4,
    "noche": 2,
    "rotativo": 1
  },
  "por_especialidad": {
    "pan": 6,
    "reposteria": 3,
    "ambos": 3
  },
  "total_kilos_producidos": 15750,
  "salario_total_mensual": 30000.00
}
```

---

### 2. Gestión de Vendedores ✅

Sistema completo CRUD para administrar el personal de ventas con comisiones.

#### **Endpoints API - Vendedores**

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/admin/empleados/vendedores` | Listar todos los vendedores | Admin |
| POST | `/api/admin/empleados/vendedores` | Crear nuevo vendedor | Admin |
| GET | `/api/admin/empleados/vendedores/{id}` | Ver detalle de un vendedor | Admin |
| PUT | `/api/admin/empleados/vendedores/{id}` | Actualizar vendedor | Admin |
| DELETE | `/api/admin/empleados/vendedores/{id}` | Eliminar vendedor (soft delete) | Admin |
| POST | `/api/admin/empleados/vendedores/{id}/cambiar-estado` | Cambiar estado del vendedor | Admin |
| GET | `/api/admin/empleados/vendedores/estadisticas` | Estadísticas globales | Admin |
| GET | `/api/admin/empleados/vendedores/{id}/reporte-ventas` | Reporte de ventas del vendedor | Admin |

#### **Campos de Vendedor**

```json
{
  "id": 1,
  "user_id": 5,
  "codigo_vendedor": "VEN-2025-0001",
  "comision_porcentaje": 3.00,
  "descuento_maximo_bs": 50.00,
  "puede_dar_descuentos": true,
  "puede_cancelar_ventas": false,
  "turno": "mañana",
  "fecha_ingreso": "2025-01-15",
  "estado": "activo",  // activo, inactivo, suspendido
  "observaciones": "Vendedor con 5 años de experiencia",
  "ventas_realizadas": 150,
  "total_vendido": 45000.00,
  "descuentos_otorgados": 1250.00,
  "user": {
    "id": 5,
    "name": "María García",
    "email": "maria.garcia@panificadoranancy.com"
  }
}
```

#### **Crear Vendedor**

```http
POST /api/admin/empleados/vendedores
Authorization: Bearer {token}
Content-Type: application/json

{
  "user_id": 5,
  "comision_porcentaje": 3.50,
  "descuento_maximo_bs": 100.00,
  "puede_dar_descuentos": true,
  "puede_cancelar_ventas": false,
  "turno": "tarde",
  "fecha_ingreso": "2025-10-01"
}
```

**Nota**: El `codigo_vendedor` se genera automáticamente si no se proporciona (formato: VEN-2025-0001)

#### **Reporte de Ventas de Vendedor**

```http
GET /api/admin/empleados/vendedores/1/reporte-ventas?fecha_inicio=2025-10-01&fecha_fin=2025-10-31
```

**Response:**
```json
{
  "vendedor": {
    "id": 1,
    "codigo_vendedor": "VEN-2025-0001",
    "user": {
      "name": "María García"
    }
  },
  "periodo": {
    "inicio": "2025-10-01",
    "fin": "2025-10-31"
  },
  "totales": {
    "pedidos": 45,
    "total_vendido": 15750.00,
    "descuentos": 450.00,
    "comisiones": 472.50
  },
  "pedidos": [...]
}
```

---

### 3. Sistema de Configuraciones ✅

Panel de configuración centralizado para gestionar parámetros del negocio.

#### **Endpoints API - Configuraciones**

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/admin/configuraciones` | Listar todas las configuraciones | Admin |
| GET | `/api/admin/configuraciones/{clave}` | Obtener una configuración específica | Admin |
| POST | `/api/admin/configuraciones` | Crear o actualizar configuración | Admin |
| PUT | `/api/admin/configuraciones/actualizar-multiples` | Actualizar múltiples configuraciones | Admin |
| DELETE | `/api/admin/configuraciones/{clave}` | Eliminar configuración | Admin |
| POST | `/api/admin/configuraciones/inicializar-defecto` | Inicializar configuraciones por defecto | Admin |
| GET | `/api/admin/configuraciones/{clave}/valor` | Obtener solo el valor de una configuración | Admin |

#### **Configuraciones Por Defecto**

##### **Grupo: Producción**

| Clave | Valor | Tipo | Descripción |
|-------|-------|------|-------------|
| `precio_kilo_produccion` | 15.00 | numero | Precio pagado por kilo producido |
| `meta_produccion_diaria` | 500 | numero | Meta de kilos diarios |

##### **Grupo: Ventas**

| Clave | Valor | Tipo | Descripción |
|-------|-------|------|-------------|
| `comision_vendedor_defecto` | 3.00 | numero | % de comisión por defecto |
| `descuento_maximo_defecto` | 50.00 | numero | Descuento máximo en Bs |
| `descuento_mayorista_porcentaje` | 10.00 | numero | % descuento mayorista |

##### **Grupo: Inventario**

| Clave | Valor | Tipo | Descripción |
|-------|-------|------|-------------|
| `stock_minimo_alerta` | 10 | numero | Stock mínimo para alerta |
| `dias_anticipacion_pedidos` | 1 | numero | Días de anticipación |

##### **Grupo: Sistema**

| Clave | Valor | Tipo | Descripción |
|-------|-------|------|-------------|
| `nombre_empresa` | Panificadora Nancy | texto | Nombre del negocio |
| `telefono_contacto` | +591 764 90687 | texto | Teléfono principal |
| `direccion` | Av. Martín Cardenas... | texto | Dirección |
| `horario_apertura` | 07:00 | texto | Hora de apertura |
| `horario_cierre` | 20:00 | texto | Hora de cierre |

#### **Crear/Actualizar Configuración**

```http
POST /api/admin/configuraciones
Authorization: Bearer {token}
Content-Type: application/json

{
  "clave": "precio_kilo_produccion",
  "valor": "18.50",
  "tipo": "numero",
  "descripcion": "Nuevo precio por kilo de producción",
  "grupo": "produccion"
}
```

#### **Actualizar Múltiples Configuraciones**

```http
PUT /api/admin/configuraciones/actualizar-multiples
Authorization: Bearer {token}
Content-Type: application/json

{
  "configuraciones": [
    {
      "clave": "precio_kilo_produccion",
      "valor": "18.50",
      "tipo": "numero"
    },
    {
      "clave": "comision_vendedor_defecto",
      "valor": "3.50",
      "tipo": "numero"
    }
  ]
}
```

#### **Uso en Código**

```php
// Obtener valor de configuración
$precioKilo = ConfiguracionSistema::get('precio_kilo_produccion', 15.00);

// Establecer valor de configuración
ConfiguracionSistema::set(
    'precio_kilo_produccion', 
    18.50, 
    'numero', 
    'Precio por kilo',
    'produccion'
);
```

---

## 🗄️ Estructura de Base de Datos

### Tabla: `panaderos`

```sql
CREATE TABLE `panaderos` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `telefono` VARCHAR(20) NOT NULL,
  `ci` VARCHAR(20) UNIQUE NOT NULL,
  `direccion` TEXT NULL,
  `fecha_ingreso` DATE NOT NULL,
  `turno` ENUM('mañana','tarde','noche','rotativo') DEFAULT 'mañana',
  `especialidad` ENUM('pan','reposteria','ambos') DEFAULT 'ambos',
  `salario_base` DECIMAL(10,2) NOT NULL,
  `total_kilos_producidos` INT DEFAULT 0,
  `total_unidades_producidas` INT DEFAULT 0,
  `ultima_produccion` DATE NULL,
  `activo` BOOLEAN DEFAULT TRUE,
  `observaciones` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  INDEX `idx_activo` (`activo`),
  INDEX `idx_turno` (`turno`),
  INDEX `idx_especialidad` (`especialidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Tabla: `vendedores`

```sql
CREATE TABLE `vendedores` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `codigo_vendedor` VARCHAR(255) UNIQUE NULL,
  `comision_porcentaje` DECIMAL(5,2) DEFAULT 0.00,
  `descuento_maximo_bs` DECIMAL(10,2) DEFAULT 0.00,
  `puede_dar_descuentos` BOOLEAN DEFAULT TRUE,
  `puede_cancelar_ventas` BOOLEAN DEFAULT FALSE,
  `turno` VARCHAR(255) NULL,
  `fecha_ingreso` DATE NULL,
  `estado` ENUM('activo','inactivo','suspendido') DEFAULT 'activo',
  `observaciones` TEXT NULL,
  `ventas_realizadas` INT DEFAULT 0,
  `total_vendido` DECIMAL(12,2) DEFAULT 0.00,
  `descuentos_otorgados` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_codigo_vendedor` (`codigo_vendedor`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_turno` (`turno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Tabla: `configuracion_sistema`

```sql
CREATE TABLE `configuracion_sistema` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `clave` VARCHAR(100) UNIQUE NOT NULL COMMENT 'Clave única de configuración',
  `valor` TEXT NOT NULL COMMENT 'Valor de la configuración',
  `tipo` ENUM('texto','numero','boolean','json') DEFAULT 'texto',
  `descripcion` TEXT NULL,
  `grupo` VARCHAR(50) NULL COMMENT 'Grupo (produccion, ventas, sistema, etc.)',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_clave` (`clave`),
  INDEX `idx_grupo` (`grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Modificación en tabla `pedidos`

```sql
ALTER TABLE `pedidos`
ADD COLUMN `vendedor_id` BIGINT UNSIGNED NULL AFTER `user_id`,
ADD COLUMN `descuento_bs` DECIMAL(10,2) DEFAULT 0.00 AFTER `total`,
ADD COLUMN `motivo_descuento` TEXT NULL AFTER `descuento_bs`,
ADD FOREIGN KEY (`vendedor_id`) REFERENCES `vendedores`(`id`) ON DELETE SET NULL;
```

---

## 📝 Modelos de Laravel

### PanaderoController.php
- Ubicación: `app/Http/Controllers/Admin/PanaderoController.php`
- **Métodos**: index, show, store, update, destroy, toggleActivo, estadisticas

### VendedorController.php
- Ubicación: `app/Http/Controllers/Admin/VendedorController.php`
- **Métodos**: index, show, store, update, destroy, cambiarEstado, estadisticas, reporteVentas

### ConfiguracionController.php
- Ubicación: `app/Http/Controllers/Admin/ConfiguracionController.php`
- **Métodos**: index, show, store, actualizarMultiples, destroy, inicializarDefecto, getValor

### Modelos Eloquent

- `App\Models\Panadero`
- `App\Models\Vendedor`
- `App\Models\ConfiguracionSistema`

---

## 🔐 Seguridad y Permisos

### Middleware Aplicado

```php
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin,vendedor'])->group(function () {
    // Todas las rutas de gestión de empleados requieren autenticación
    // y rol de admin o vendedor (según corresponda)
});
```

### Validaciones Implementadas

#### Panaderos
- Email único en la tabla panaderos
- CI único
- Salario base debe ser mayor a 0
- Turno válido: mañana, tarde, noche, rotativo
- Especialidad válida: pan, reposteria, ambos

#### Vendedores
- user_id debe existir en la tabla users
- user_id único (un usuario solo puede ser vendedor una vez)
- Comisión entre 0 y 100%
- Descuento máximo mayor o igual a 0
- Estado válido: activo, inactivo, suspendido

---

## 📊 Casos de Uso

### 1. Registrar nuevo panadero

```javascript
const response = await fetch('/api/admin/empleados/panaderos', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    nombre: 'Carlos',
    apellido: 'Mamani',
    email: 'carlos.mamani@panificadoranancy.com',
    telefono: '+591 78901234',
    ci: '9876543',
    direccion: 'Zona Norte #456',
    fecha_ingreso: '2025-10-15',
    turno: 'mañana',
    especialidad: 'pan',
    salario_base: 2800.00
  })
});
```

### 2. Calcular comisión de vendedor

```php
// En el backend, al procesar un pedido
$vendedor = Vendedor::find($pedidoData['vendedor_id']);
$totalVenta = $pedido->total;
$comision = ($totalVenta * $vendedor->comision_porcentaje) / 100;

// Actualizar estadísticas del vendedor
$vendedor->increment('ventas_realizadas');
$vendedor->increment('total_vendido', $totalVenta);
```

### 3. Configurar precio de producción

```javascript
// Actualizar precio por kilo
await fetch('/api/admin/configuraciones', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    clave: 'precio_kilo_produccion',
    valor: '20.00',
    tipo: 'numero',
    descripcion: 'Nuevo precio por kilo de producción',
    grupo: 'produccion'
  })
});
```

---

## ✅ Próximos Pasos - Frontend React

Para completar el sistema, se debe implementar en el frontend:

### Componentes a Crear

1. **Panel de Panaderos**
   - `PanaderosList.jsx` - Lista con tabla y filtros
   - `PanaderoForm.jsx` - Formulario crear/editar
   - `PanaderoDetail.jsx` - Vista detallada con estadísticas

2. **Panel de Vendedores**
   - `VendedoresList.jsx` - Lista con tabla y filtros
   - `VendedorForm.jsx` - Formulario crear/editar
   - `VendedorDetail.jsx` - Vista detallada con reporte de ventas

3. **Panel de Configuraciones**
   - `ConfiguracionPanel.jsx` - Vista agrupada por categorías
   - `ConfiguracionForm.jsx` - Edición en línea

### Rutas del Frontend

```jsx
// En App.jsx
<Route path="/admin/empleados/panaderos" element={<PanaderosList />} />
<Route path="/admin/empleados/panaderos/nuevo" element={<PanaderoForm />} />
<Route path="/admin/empleados/panaderos/:id" element={<PanaderoDetail />} />

<Route path="/admin/empleados/vendedores" element={<VendedoresList />} />
<Route path="/admin/empleados/vendedores/nuevo" element={<VendedorForm />} />
<Route path="/admin/empleados/vendedores/:id" element={<VendedorDetail />} />

<Route path="/admin/configuraciones" element={<ConfiguracionPanel />} />
```

---

## 🎉 Resumen

✅ **Backend completamente implementado** con:
- 3 controladores con CRUDs completos
- 3 modelos Eloquent
- 1 migración nueva ejecutada
- 20+ endpoints de API
- Validaciones y seguridad
- Estadísticas y reportes

✅ **Base de datos lista** con:
- Tabla de panaderos
- Tabla de vendedores  
- Tabla de configuraciones
- Modificaciones en tabla pedidos

🔄 **Pendiente** (Frontend React):
- Componentes de interfaz
- Formularios
- Tablas con filtros
- Gráficos de estadísticas

---

**Fecha de implementación:** 11 de octubre de 2025  
**Versión del sistema:** 1.1.0  
**Desarrollado por:** VMKayser
