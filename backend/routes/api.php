<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\AdminProductoController;
use App\Http\Controllers\Api\AdminPedidoController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\MateriaPrimaController;
use App\Http\Controllers\RecetaController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\Api\EmpleadoPagoController;

// ============================================
// RUTAS DE AUTENTICACIÓN (Públicas con Rate Limiting)
// ============================================
// Throttle: máximo 5 intentos por minuto para evitar ataques de fuerza bruta
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Rutas protegidas de autenticación (throttle: 60 requests por minuto)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Rutas públicas
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/productos/{id}', [ProductoController::class, 'show']);


// Rutas de pedidos
Route::post('/pedidos', [PedidoController::class, 'store']);
Route::get('/metodos-pago', [PedidoController::class, 'metodosPago']);

// Rutas protegidas de pedidos del cliente
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/mis-pedidos', [PedidoController::class, 'misPedidos']);
    Route::get('/mis-pedidos/{id}', [PedidoController::class, 'miPedidoDetalle']);
});

// Rutas de categorías
Route::get('/categorias', [CategoriaController::class, 'index']);

// ============================================
// RUTAS DE ADMINISTRACIÓN (Protegidas)
// ============================================
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin,vendedor'])->group(function () {
    // CRUD de productos
    Route::get('/productos', [AdminProductoController::class, 'index']);
    Route::post('/productos', [AdminProductoController::class, 'store']);
    Route::get('/productos/{id}', [AdminProductoController::class, 'show']);
    Route::put('/productos/{id}', [AdminProductoController::class, 'update']);
    Route::delete('/productos/{id}', [AdminProductoController::class, 'destroy']);
    
    // Acciones adicionales de productos
    Route::post('/productos/{id}/restore', [AdminProductoController::class, 'restore']);
    Route::post('/productos/{id}/toggle-active', [AdminProductoController::class, 'toggleActive']);
    Route::post('/productos/upload-image', [AdminProductoController::class, 'uploadImage']);
    
    // Estadísticas de productos
    Route::get('/stats', [AdminProductoController::class, 'stats']);
    
    // Gestión de pedidos
    Route::get('/pedidos', [AdminPedidoController::class, 'index']);
    Route::get('/pedidos/hoy', [AdminPedidoController::class, 'hoy']);
    Route::get('/pedidos/pendientes', [AdminPedidoController::class, 'pendientes']);
    Route::get('/pedidos/para-hoy', [AdminPedidoController::class, 'paraHoy']);
    Route::get('/pedidos/stats', [AdminPedidoController::class, 'stats']);
    Route::get('/pedidos/{id}', [AdminPedidoController::class, 'show']);
    Route::put('/pedidos/{id}/estado', [AdminPedidoController::class, 'updateEstado']);
    Route::put('/pedidos/{id}/fecha-entrega', [AdminPedidoController::class, 'updateFechaEntrega']);
    Route::post('/pedidos/{id}/notas', [AdminPedidoController::class, 'addNotas']);
    Route::post('/pedidos/{id}/cancelar', [AdminPedidoController::class, 'cancel']);

    // Gestión de clientes
    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::post('/clientes', [ClienteController::class, 'store']);
    Route::get('/clientes/estadisticas', [ClienteController::class, 'estadisticas']);
    Route::post('/clientes/buscar-email', [ClienteController::class, 'findByEmail']);
    Route::get('/clientes/{id}', [ClienteController::class, 'show']);
    Route::put('/clientes/{id}', [ClienteController::class, 'update']);
    Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);
    Route::post('/clientes/{id}/toggle-active', [ClienteController::class, 'toggleActive']);

    // Gestión de panaderos
    Route::get('/panaderos', [\App\Http\Controllers\Api\AdminPanaderoController::class, 'index']);
    Route::post('/panaderos', [\App\Http\Controllers\Api\AdminPanaderoController::class, 'store']);
    Route::get('/panaderos/{id}', [\App\Http\Controllers\Api\AdminPanaderoController::class, 'show']);
    Route::put('/panaderos/{id}', [\App\Http\Controllers\Api\AdminPanaderoController::class, 'update']);
    Route::delete('/panaderos/{id}', [\App\Http\Controllers\Api\AdminPanaderoController::class, 'destroy']);

    // ============================================
    // GESTIÓN DE EMPLEADOS (NUEVO)
    // ============================================
    
    // Panaderos - CRUD Completo
    Route::prefix('empleados/panaderos')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PanaderoController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\PanaderoController::class, 'store']);
        Route::get('/estadisticas', [\App\Http\Controllers\Admin\PanaderoController::class, 'estadisticas']);
        Route::get('/{id}', [\App\Http\Controllers\Admin\PanaderoController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Admin\PanaderoController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Admin\PanaderoController::class, 'destroy']);
        Route::post('/{id}/toggle-activo', [\App\Http\Controllers\Admin\PanaderoController::class, 'toggleActivo']);
    });

    // Vendedores - CRUD Completo
    Route::prefix('empleados/vendedores')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\VendedorController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\VendedorController::class, 'store']);
        Route::get('/estadisticas', [\App\Http\Controllers\Admin\VendedorController::class, 'estadisticas']);
        Route::get('/{id}', [\App\Http\Controllers\Admin\VendedorController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Admin\VendedorController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Admin\VendedorController::class, 'destroy']);
        Route::post('/{id}/cambiar-estado', [\App\Http\Controllers\Admin\VendedorController::class, 'cambiarEstado']);
        Route::get('/{id}/reporte-ventas', [\App\Http\Controllers\Admin\VendedorController::class, 'reporteVentas']);
    });

    // Configuraciones del Sistema
    Route::prefix('configuraciones')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'index']);
        Route::get('/{clave}', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'store']);
        Route::put('/actualizar-multiples', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'actualizarMultiples']);
        Route::delete('/{clave}', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'destroy']);
        Route::post('/inicializar-defecto', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'inicializarDefecto']);
        Route::get('/{clave}/valor', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'getValor']);
    });
        // empleado payments
        Route::get('empleado-pagos', [EmpleadoPagoController::class, 'index']);
        Route::post('empleado-pagos', [EmpleadoPagoController::class, 'store']);

    // Gestión de categorías
    Route::get('/categorias', [\App\Http\Controllers\Api\AdminCategoriaController::class, 'index']);
    Route::post('/categorias', [\App\Http\Controllers\Api\AdminCategoriaController::class, 'store']);
    Route::get('/categorias/{id}', [\App\Http\Controllers\Api\AdminCategoriaController::class, 'show']);
    Route::put('/categorias/{id}', [\App\Http\Controllers\Api\AdminCategoriaController::class, 'update']);
    Route::delete('/categorias/{id}', [\App\Http\Controllers\Api\AdminCategoriaController::class, 'destroy']);
    Route::post('/categorias/{id}/toggle-active', [\App\Http\Controllers\Api\AdminCategoriaController::class, 'toggleActive']);
    Route::post('/categorias/reorder', [\App\Http\Controllers\Api\AdminCategoriaController::class, 'reorder']);
});

// ============================================
// RUTAS DE SISTEMA DE INVENTARIO
// ============================================
Route::prefix('inventario')->middleware(['auth:sanctum'])->group(function () {
    
    // ===== MATERIAS PRIMAS =====
    Route::prefix('materias-primas')->group(function () {
        Route::get('/', [MateriaPrimaController::class, 'index']); // Listar todas
        Route::post('/', [MateriaPrimaController::class, 'store']); // Crear nueva
        Route::get('/stock-bajo', [MateriaPrimaController::class, 'stockBajo']); // Alertas de stock bajo
        Route::get('/{id}', [MateriaPrimaController::class, 'show']); // Ver una específica
        Route::put('/{id}', [MateriaPrimaController::class, 'update']); // Actualizar
        Route::delete('/{id}', [MateriaPrimaController::class, 'destroy']); // Eliminar
        Route::post('/{id}/compra', [MateriaPrimaController::class, 'registrarCompra']); // Registrar compra
        Route::post('/{id}/ajuste', [MateriaPrimaController::class, 'ajustarStock']); // Ajuste manual
        Route::get('/{id}/movimientos', [MateriaPrimaController::class, 'movimientos']); // Historial
    });

    // ===== RECETAS =====
    Route::prefix('recetas')->group(function () {
        Route::get('/', [RecetaController::class, 'index']); // Listar todas
        Route::post('/', [RecetaController::class, 'store']); // Crear nueva
        Route::get('/{id}', [RecetaController::class, 'show']); // Ver una específica
        Route::put('/{id}', [RecetaController::class, 'update']); // Actualizar
        Route::delete('/{id}', [RecetaController::class, 'destroy']); // Eliminar
        Route::get('/{id}/verificar-stock', [RecetaController::class, 'verificarStock']); // Verificar disponibilidad
        Route::post('/{id}/recalcular-costos', [RecetaController::class, 'recalcularCostos']); // Recalcular costos
        Route::post('/{id}/duplicar', [RecetaController::class, 'duplicar']); // Duplicar receta
    });

    // ===== PRODUCCIÓN =====
    Route::prefix('producciones')->group(function () {
        Route::get('/', [ProduccionController::class, 'index']); // Listar todas
        Route::post('/', [ProduccionController::class, 'store']); // Registrar producción
        Route::get('/reporte-diario', [ProduccionController::class, 'reporteDiario']); // Reporte del día
        Route::get('/reporte-periodo', [ProduccionController::class, 'reportePeriodo']); // Reporte por período
        Route::get('/analisis-diferencias', [ProduccionController::class, 'analisisDiferencias']); // Análisis mermas
        Route::get('/{id}', [ProduccionController::class, 'show']); // Ver producción
        Route::put('/{id}', [ProduccionController::class, 'update']); // Actualizar
        Route::post('/{id}/cancelar', [ProduccionController::class, 'cancelar']); // Cancelar producción
    });

    // ===== INVENTARIO PRODUCTOS FINALES =====
    Route::get('/dashboard', [InventarioController::class, 'dashboard']); // Dashboard general
    Route::get('/productos-finales', [InventarioController::class, 'productosFinales']); // Stock productos
    Route::get('/movimientos-productos', [InventarioController::class, 'movimientosProductos']); // Movimientos
    Route::post('/productos/{productoId}/ajustar', [InventarioController::class, 'ajustarInventarioProducto']); // Ajuste
    Route::get('/reporte-rotacion', [InventarioController::class, 'reporteRotacion']); // Rotación
    Route::get('/reporte-mermas', [InventarioController::class, 'reporteMermas']); // Mermas
    Route::get('/kardex/{productoId}', [InventarioController::class, 'kardex']); // Kardex
    Route::post('/productos/{productoId}/stock-minimo', [InventarioController::class, 'configurarStockMinimo']); // Config
});

// ============================================
// RUTAS DE GESTIÓN DE EMPLEADOS Y USUARIOS (Admin)
// ============================================
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    
    // ===== GESTIÓN DE USUARIOS =====
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UserController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\UserController::class, 'store']);
        Route::get('/estadisticas', [\App\Http\Controllers\Admin\UserController::class, 'estadisticas']);
        Route::get('/disponibles-vendedor', [\App\Http\Controllers\Admin\UserController::class, 'usuariosDisponiblesVendedor']);
        Route::get('/{id}', [\App\Http\Controllers\Admin\UserController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Admin\UserController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Admin\UserController::class, 'destroy']);
        Route::post('/{id}/cambiar-rol', [\App\Http\Controllers\Admin\UserController::class, 'cambiarRol']);
    });

    // ===== GESTIÓN DE PANADEROS =====
    Route::prefix('empleados/panaderos')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PanaderoController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\PanaderoController::class, 'store']);
        Route::get('/estadisticas', [\App\Http\Controllers\Admin\PanaderoController::class, 'estadisticas']);
        Route::get('/{id}', [\App\Http\Controllers\Admin\PanaderoController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Admin\PanaderoController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Admin\PanaderoController::class, 'destroy']);
        Route::post('/{id}/toggle-activo', [\App\Http\Controllers\Admin\PanaderoController::class, 'toggleActivo']);
    });

    // ===== GESTIÓN DE VENDEDORES =====
    Route::prefix('empleados/vendedores')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminVendedorController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\AdminVendedorController::class, 'store']);
        Route::get('/estadisticas', [\App\Http\Controllers\Admin\VendedorController::class, 'estadisticas']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminVendedorController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminVendedorController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminVendedorController::class, 'destroy']);
        Route::post('/{id}/cambiar-estado', [\App\Http\Controllers\Admin\VendedorController::class, 'cambiarEstado']);
        Route::get('/{id}/reporte-ventas', [\App\Http\Controllers\Admin\VendedorController::class, 'reporteVentas']);
    });

    // ===== CONFIGURACIÓN DEL SISTEMA =====
    Route::prefix('configuraciones')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'store']);
        Route::post('/actualizar-multiples', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'actualizarMultiples']);
        Route::post('/inicializar-defecto', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'inicializarDefecto']);
        Route::get('/{clave}', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'show']);
        Route::delete('/{clave}', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'destroy']);
        Route::get('/{clave}/valor', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'getValor']);
    });
});


