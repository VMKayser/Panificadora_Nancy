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
});
