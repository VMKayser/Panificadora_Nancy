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

// Email verification routes (signed URL + resend)
use Illuminate\Http\Request as HttpRequest;

// Public verification endpoint that works without the user being authenticated (API clients may open
// the verification link in a browser where the user isn't logged in). The framework's
// EmailVerificationRequest expects an authenticated user, so we perform manual verification here:
use Illuminate\Auth\Events\Verified;

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    // Validate signature (signed middleware already runs, but double-checking here gives clearer JSON errors)
    if (!$request->hasValidSignature()) {
        return response()->json(['message' => 'Enlace inválido o expirado'], 403);
    }

    $user = \App\Models\User::find($id);
    if (!$user) {
        return response()->json(['message' => 'Usuario no encontrado'], 404);
    }

    // Ensure the hash matches the user's email (same check Laravel does internally)
    if (!hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
        return response()->json(['message' => 'Hash de verificación inválido'], 403);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Correo ya verificado'], 200);
    }

    $user->markEmailAsVerified();
    event(new Verified($user));

    // If the request expects JSON (API client), return JSON. Otherwise redirect to the
    // frontend application so the user sees a friendly UI. We include the email so the
    // frontend can show it if desired (e.g. "Tu correo x@... ha sido verificado").
    if ($request->wantsJson() || str_contains($request->header('accept', ''), 'application/json')) {
        return response()->json(['message' => 'Email verificado'], 200);
    }

    $appUrl = rtrim(config('app.url') ?? env('APP_URL', ''), '/');
    $redirectTo = $appUrl . '/login?verified=1&email=' . urlencode($user->email);
    return redirect()->away($redirectTo);
})->middleware('signed')->name('verification.verify');

Route::post('/email/resend', function (HttpRequest $request) {
    // Allow either authenticated user or public request with email in body.
    $email = $request->input('email');
    $user = $request->user();
    if (!$user && !$email) {
        return response()->json(['message' => 'Se requiere autenticación o email'], 400);
    }

    if (!$user) {
        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) {
            // Do not reveal existence of emails in production; reply generically
            return response()->json(['message' => 'Si el correo existe, se ha reenviado la verificación'], 202);
        }
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Ya verificado'], 200);
    }

    // Send verification (queued if queue is configured)
    $user->sendEmailVerificationNotification();
    return response()->json(['message' => 'Si el correo existe, se ha reenviado la verificación'], 202);
})->middleware('throttle:6,1');

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

// Rutas públicas para configuraciones no sensibles (logo, QR, whatsapp, nombre)
Route::get('/configuraciones/public/{clave}/valor', [\App\Http\Controllers\ConfiguracionPublicController::class, 'getValor']);

// Healthcheck endpoint (public) - simple DB + cache sanity check
Route::get('/health', [\App\Http\Controllers\SystemHealthController::class, 'index']);


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
    // Dashboard snapshot endpoint (cached) - admin only
    Route::get('/dashboard-stats', [\App\Http\Controllers\Api\AdminStatsController::class, 'index'])
        ->middleware('role:admin');
    
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
    // Admin utility: clear dashboard cache
    Route::post('/cache/dashboard/clear', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'clearCache']);
    // WhatsApp admin endpoints: listar y reintentar mensajes
    Route::prefix('whatsapp')->group(function () {
        Route::get('/messages', [\App\Http\Controllers\Admin\WhatsAppController::class, 'index']);
        Route::post('/messages/{id}/retry', [\App\Http\Controllers\Admin\WhatsAppController::class, 'retry']);
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
    // Dashboard general - devuelve métricas usadas por el frontend (pedidos_hoy, ingresos_hoy, etc.)
    Route::get('/dashboard', [\App\Http\Controllers\Api\InventarioDashboardController::class, 'index']); // Dashboard general
    Route::get('/productos-finales', [InventarioController::class, 'productosFinales']); // Stock productos
    Route::get('/movimientos-productos', [InventarioController::class, 'movimientosProductos']); // Movimientos
    Route::post('/productos/{productoId}/ajustar', [InventarioController::class, 'ajustarInventarioProducto']); // Ajuste
    Route::get('/reporte-rotacion', [InventarioController::class, 'reporteRotacion']); // Rotación
    Route::get('/reporte-mermas', [InventarioController::class, 'reporteMermas']); // Mermas
    Route::get('/kardex/{productoId}', [InventarioController::class, 'kardex']); // Kardex
    Route::post('/productos/{productoId}/stock-minimo', [InventarioController::class, 'configurarStockMinimo']); // Config
    // Movimientos de caja (ingresos / salidas)
    Route::get('/movimientos-caja', [\App\Http\Controllers\Api\MovimientoCajaController::class, 'index']);
    Route::post('/movimientos-caja', [\App\Http\Controllers\Api\MovimientoCajaController::class, 'store']);
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


