<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use App\Models\Cliente;
use App\Mail\PedidoConfirmado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Support\SafeTransaction;
use Carbon\Carbon;
use App\Services\InventarioService;

class PedidoController extends Controller
{

    public function store(Request $request)
    {
        // Log incoming payload to help debug 500s during order creation
        try {
            Log::info('PedidoController::store - payload', $request->all());
        } catch (\Exception $e) {
            // ensure logging failure doesn't block request processing
            Log::warning('PedidoController::store - failed to log payload: ' . $e->getMessage());
        }
        // Verificar si es venta de mostrador (simplificada)
        $esVentaMostrador = $request->input('es_venta_mostrador', false);

        // Compat: combinar fecha_entrega + hora_entrega en entrega_datetime si no viene el campo
        if (!$request->has('entrega_datetime') && $request->filled('fecha_entrega') && $request->filled('hora_entrega')) {
            try {
                $combined = Carbon::createFromFormat('Y-m-d H:i', $request->input('fecha_entrega') . ' ' . $request->input('hora_entrega'));
                $request->merge(['entrega_datetime' => $combined->format('Y-m-d H:i:s')]);
            } catch (\Exception $e) {
                // ignore invalid formats; validation will catch it later
            }
        }

        if ($esVentaMostrador) {
            // Validación simplificada para ventas de mostrador
            $validated = $request->validate([
                'cliente_nombre' => 'required|string|max:255',
                'cliente_email' => 'required|email',
                'cliente_telefono' => 'nullable|string',
                'metodos_pago_id' => 'required|exists:metodos_pago,id',
                'descuento_bs' => 'nullable|numeric|min:0',
                'motivo_descuento' => 'nullable|string|max:255',
                'detalles' => 'required|array|min:1',
                'detalles.*.producto_id' => 'required|exists:productos,id',
                'detalles.*.cantidad' => 'required|integer|min:1',
                'detalles.*.precio_unitario' => 'required|numeric|min:0',
                'detalles.*.subtotal' => 'required|numeric|min:0',
                'subtotal' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'entrega_datetime' => 'nullable|date_format:Y-m-d H:i:s',
            ]);

            try {
                // Validar stock para cada detalle (venta mostrador)
                // Prefetch productos para evitar N+1
                $insufficient = [];
                $detProdIds = collect($validated['detalles'])->pluck('producto_id')->unique()->values()->all();
                $productosMap = Producto::whereIn('id', $detProdIds)->with('inventario')->get()->keyBy('id');

                foreach ($validated['detalles'] as $detalle) {
                    $productoCheck = $productosMap->get($detalle['producto_id']);
                    if ($productoCheck) {
                        $available = $productoCheck->inventario->stock_actual ?? $productoCheck->stock_actual ?? $productoCheck->stock ?? null;
                        if ($available !== null && $available < $detalle['cantidad']) {
                            $insufficient[] = [
                                'id' => $productoCheck->id,
                                'nombre' => $productoCheck->nombre,
                                'disponible' => $available,
                                'solicitado' => $detalle['cantidad']
                            ];
                        }
                    }
                }
                if (!empty($insufficient)) {
                    return response()->json([
                        'message' => 'Stock insuficiente para algunos productos',
                        'insufficient_stock' => $insufficient
                    ], 422);
                }
                // Use DB::transaction to ensure Laravel manages savepoints
                try { Log::info('PedidoController::store - starting mostrador safe transaction', ['vendedor' => Auth::id() ?? null]); } catch (\Throwable $e) {}
                $pedido = SafeTransaction::run(function () use ($validated) {
                    try { Log::info('PedidoController::store - inside mostrador transaction (safe) begin'); } catch (\Throwable $e) {}
                    $numeroPedido = 'VM-' . date('Ymd') . '-' . str_pad(Pedido::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

                    // Obtener vendedor_id del usuario autenticado si existe
                    $vendedorId = null;
                    if (Auth::check() && Auth::user()->vendedor) {
                        $vendedorId = Auth::user()->vendedor->id;
                    }

                    $pedido = Pedido::create([
                        'numero_pedido' => $numeroPedido,
                        'cliente_id' => null,
                        'vendedor_id' => $vendedorId,
                        'cliente_nombre' => $validated['cliente_nombre'],
                        'cliente_apellido' => '',
                        'cliente_email' => $validated['cliente_email'],
                        'cliente_telefono' => $validated['cliente_telefono'] ?? '00000000',
                        'tipo_entrega' => 'recoger',
                        'direccion_entrega' => null,
                        'indicaciones_especiales' => 'Venta en mostrador',
                        'subtotal' => $validated['subtotal'],
                        'descuento' => 0,
                        'descuento_bs' => $validated['descuento_bs'] ?? 0,
                        'motivo_descuento' => $validated['motivo_descuento'] ?? null,
                        'total' => $validated['total'],
                        'metodos_pago_id' => $validated['metodos_pago_id'],
                        'estado' => 'entregado', // Entregado inmediatamente
                        'estado_pago' => 'pagado',
                        'fecha_entrega' => $validated['entrega_datetime'] ?? null,
                    ]);

                    foreach ($validated['detalles'] as $detalle) {
                        $producto = Producto::find($detalle['producto_id']);

                        DetallePedido::create([
                            'pedidos_id' => $pedido->id,
                            'productos_id' => $detalle['producto_id'],
                            'nombre_producto' => $producto->nombre,
                            'precio_unitario' => $detalle['precio_unitario'],
                            'cantidad' => $detalle['cantidad'],
                            'subtotal' => $detalle['subtotal'],
                        ]);
                    }

                    // Actualizar estadísticas del vendedor si existe
                    if (Auth::check() && Auth::user()->vendedor) {
                        $vendedor = Auth::user()->vendedor;
                        $vendedor->registrarVenta(
                            $validated['total'],
                            $validated['descuento_bs'] ?? 0
                        );
                    }

                    return $pedido;
                });
                try { Log::info('PedidoController::store - mostrador safe transaction committed', ['pedido_id' => $pedido->id ?? null]); } catch (\Throwable $e) {}

                // Ejecutar el descuento en una transacción propia para asegurar
                // que se creen los movimientos de inventario de forma determinista.
                try {
                    $inventarioService = new InventarioService();
                    $inventarioService->descontarInventario($pedido, true);
                } catch (\Exception $e) {
                    Log::warning('Error ejecutando descuento de inventario post-commit en venta mostrador: ' . $e->getMessage());
                }

                return response()->json([
                    'message' => 'Venta registrada exitosamente',
                    'pedido' => $pedido->load('detalles'),
                ], 201);

            } catch (\Exception $e) {
                // Log full exception and payload for easier debugging
                Log::error('Error en venta mostrador: ' . $e->getMessage(), [
                    'exception' => $e,
                    'payload' => $request->all()
                ]);
                return response()->json([
                    'message' => 'Error al procesar la venta',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        // Validación completa para pedidos normales (e-commerce)
        $validated = $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            'cliente_apellido' => 'required|string|max:255',
            'cliente_email' => 'required|email',
            'cliente_telefono' => 'required|string',
            'tipo_entrega' => 'required|in:delivery,recoger,envio_nacional',
            'direccion_entrega' => 'nullable|string',
            'direccion_lat' => 'nullable|numeric',
            'direccion_lng' => 'nullable|numeric',
            'indicaciones_especiales' => 'nullable|string',
            'envio_por_pagar' => 'nullable|boolean',
            'empresa_transporte' => 'nullable|string|max:255',
            'metodos_pago_id' => 'required|exists:metodos_pago,id',
            'codigo_promocional' => 'nullable|string',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'entrega_datetime' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        // Perform validation and availability checks outside the DB transaction
        // to avoid throwing inside a nested transaction which can lead to
        // savepoint mismatch errors in MySQL during tests.
        // Buscar o crear cliente (do this before transaction to validate input)
        $cliente = Cliente::where('email', $validated['cliente_email'])->first();
        if (!$cliente) {
            $cliente = Cliente::create([
                'nombre' => $validated['cliente_nombre'],
                'apellido' => $validated['cliente_apellido'],
                'email' => $validated['cliente_email'],
                'telefono' => $validated['cliente_telefono'],
                'direccion' => $validated['direccion_entrega'] ?? null,
                'tipo_cliente' => 'regular',
                'activo' => true,
                'total_pedidos' => 0,
                'total_gastado' => 0,
            ]);
            Log::info("Nuevo cliente creado: {$cliente->id} - {$cliente->email}");
        } else {
            Log::info("Cliente existente encontrado: {$cliente->id} - {$cliente->email}");
        }

        $numeroPedido = 'PED-' . date('Y') . '-' . str_pad(Pedido::count() + 1, 4, '0', STR_PAD_LEFT);

        $subtotal = 0;
        $productosData = [];

        // Si es delivery (local) validar coordenadas dentro de Quillacollo
        if ($validated['tipo_entrega'] === 'delivery') {
            $lat = $validated['direccion_lat'] ?? null;
            $lng = $validated['direccion_lng'] ?? null;
            $direccionText = $validated['direccion_entrega'] ?? '';
            $direccionContainsQuillacollo = preg_match('/quillacollo/i', $direccionText);

            // Bounding box aproximado de Quillacollo
            $minLat = -17.45; $maxLat = -17.22; $minLng = -66.35; $maxLng = -66.10;

            if ($lat && $lng) {
                if (!($lat >= $minLat && $lat <= $maxLat && $lng >= $minLng && $lng <= $maxLng)) {
                    if (!$direccionContainsQuillacollo) {
                        return response()->json(['message' => 'La ubicación está fuera de Quillacollo, no es posible delivery local.'], 422);
                    }
                }
            } else {
                if (!$direccionContainsQuillacollo) {
                    return response()->json(['message' => 'Para delivery local se requieren coordenadas o que la dirección indique Quillacollo'], 422);
                }
            }
        }

        // Recolectar productos y validar disponibilidad para envio nacional
        $blockingProducts = [];
        $insufficientStock = [];

        // Prefetch productos to avoid N+1 queries
        $productIds = collect($validated['productos'])->pluck('id')->unique()->values()->all();
        $productosMap = Producto::whereIn('id', $productIds)->with('inventario')->get()->keyBy('id');

        foreach ($validated['productos'] as $productoData) {
            $producto = $productosMap->get($productoData['id']);
            $cantidad = $productoData['cantidad'];
            $precioUnitario = $producto?->precio_minorista ?? 0;
            $subtotalProducto = $precioUnitario * $cantidad;

            $available = null;
            if ($producto) {
                $available = $producto->inventario->stock_actual ?? $producto->stock_actual ?? $producto->stock ?? null;
            }
            if ($available !== null && $cantidad > $available) {
                $insufficientStock[] = [
                    'id' => $producto->id ?? $productoData['id'],
                    'nombre' => $producto->nombre ?? 'Desconocido',
                    'disponible' => $available,
                    'solicitado' => $cantidad,
                ];
            }

            if ($validated['tipo_entrega'] === 'envio_nacional' && (!$producto?->permite_envio_nacional)) {
                $blockingProducts[] = [
                    'id' => $producto->id ?? $productoData['id'],
                    'nombre' => $producto->nombre ?? 'Desconocido',
                    'cantidad' => $cantidad,
                ];
            }

            $subtotal += $subtotalProducto;

            $productosData[] = [
                'producto' => $producto,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotalProducto,
            ];
        }

        if (!empty($blockingProducts)) {
            return response()->json([
                'message' => 'Algunos productos no permiten envío nacional',
                'blocking_products' => $blockingProducts,
            ], 422);
        }

        if (!empty($insufficientStock)) {
            return response()->json([
                'message' => 'Stock insuficiente para algunos productos',
                'insufficient_stock' => $insufficientStock,
            ], 422);
        }

        try {
            try { Log::info('PedidoController::store - starting safe transaction (normal order)', ['cliente_id' => $cliente->id ?? null]); } catch (\Throwable $e) {}
            $pedido = SafeTransaction::run(function () use ($validated, $cliente, $numeroPedido, $subtotal, $productosData) {
                try { Log::info('PedidoController::store - inside transaction (safe) begin (normal order)'); } catch (\Throwable $e) {}
                // Build payload dynamically to avoid inserting columns that may not exist
                $pedidoData = [
                    'numero_pedido' => $numeroPedido,
                    'cliente_id' => $cliente->id, // Vincular con el cliente
                    'cliente_nombre' => $validated['cliente_nombre'],
                    'cliente_apellido' => $validated['cliente_apellido'],
                    'cliente_email' => $validated['cliente_email'],
                    'cliente_telefono' => $validated['cliente_telefono'],
                    'tipo_entrega' => $validated['tipo_entrega'],
                    'direccion_entrega' => $validated['direccion_entrega'] ?? null,
                    // Guardar fecha/hora de entrega si fue provista (campo datetime)
                    'fecha_entrega' => $validated['entrega_datetime'] ?? null,
                    'indicaciones_especiales' => $validated['indicaciones_especiales'] ?? null,
                    'subtotal' => $subtotal,
                    'descuento' => 0, // Por ahora sin descuentos
                    'total' => $subtotal,
                    'metodos_pago_id' => $validated['metodos_pago_id'],
                    'codigo_promocional' => $validated['codigo_promocional'] ?? null,
                    'estado' => 'pendiente',
                    'estado_pago' => 'pendiente',
                ];

                // Only include optional columns if they exist in the DB to avoid SQL errors
                if (Schema::hasColumn('pedidos', 'envio_por_pagar')) {
                    $pedidoData['envio_por_pagar'] = $validated['envio_por_pagar'] ?? false;
                }
                if (Schema::hasColumn('pedidos', 'empresa_transporte')) {
                    $pedidoData['empresa_transporte'] = $validated['empresa_transporte'] ?? null;
                }

                $pedido = Pedido::create($pedidoData);

                foreach ($productosData as $data) {
                    DetallePedido::create([
                        'pedidos_id' => $pedido->id,
                        'productos_id' => $data['producto']->id,
                        'nombre_producto' => $data['producto']->nombre,
                        'precio_unitario' => $data['precio_unitario'],
                        'cantidad' => $data['cantidad'],
                        'subtotal' => $data['subtotal'],
                        'requiere_anticipacion' => $data['producto']->requiere_tiempo_anticipacion,
                        'tiempo_anticipacion' => $data['producto']->tiempo_anticipacion,
                        'unidad_tiempo' => $data['producto']->unidad_tiempo,
                    ]);
                }

                // Actualizar estadísticas del cliente
                $cliente->actualizarEstadisticas();

                return $pedido;
            });
            try { Log::info('PedidoController::store - safe transaction committed (normal order)', ['pedido_id' => $pedido->id ?? null]); } catch (\Throwable $e) {}

            // El email de confirmación se enviará cuando el admin cambie el estado a "confirmado"

            return response()->json([
                'message' => 'Pedido creado exitosamente',
                'pedido' => $pedido,
            ], 201);

        } catch (\Exception $e) {
            // If we threw a validation-like exception inside the transaction, return 422
            if (str_starts_with($e->getMessage(), 'Stock insuficiente') || str_contains($e->getMessage(), 'envío nacional') ) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            // Log full exception and payload to the log for debugging
            Log::error('Error al crear el pedido: ' . $e->getMessage(), [
                'exception' => $e,
                'payload' => $request->all()
            ]);

            return response()->json([
                'message' => 'Error al crear el pedido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function metodosPago()
    {
        $metodos = \App\Models\MetodoPago::where('esta_activo', true)
            ->orderBy('orden')
            ->get();

        // Append icono_url accessor so frontend can use a reliable absolute URL
        $metodos = $metodos->map(function ($m) {
            $m->icono_url = $m->icono_url; // Accessor
            return $m;
        });

        return response()->json($metodos);
    }

    /**
     * Obtener pedidos del cliente autenticado
     */
    public function misPedidos(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            // Obtener cliente asociado al usuario autenticado
            $cliente = $user->cliente;
            
            if (!$cliente) {
                // Si no tiene registro de cliente, buscar por email
                $cliente = Cliente::where('email', $user->email)->first();
                
                if (!$cliente) {
                    return response()->json([
                        'message' => 'No se encontró información de cliente',
                        'pedidos' => []
                    ], 200);
                }
            }

            // Obtener pedidos del cliente
            $perPage = (int) $request->get('per_page', 20);
            $perPage = $perPage > 0 ? min($perPage, 100) : 20;

            $pedidos = Pedido::where('cliente_id', $cliente->id)
                ->orWhere('cliente_email', $user->email)
                ->with(['detalles.producto.imagenes', 'metodoPago'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'pedidos' => $pedidos,
                'cliente' => [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'email' => $cliente->email,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener pedidos del cliente: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener pedidos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalle de un pedido específico del cliente autenticado
     */
    public function miPedidoDetalle(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            // Obtener cliente asociado al usuario autenticado
            $cliente = $user->cliente;
            
            if (!$cliente) {
                $cliente = Cliente::where('email', $user->email)->first();
            }

            // Obtener pedido con todas las relaciones
            $pedido = Pedido::with([
                'detalles.producto.imagenes',
                'metodoPago',
                'cliente',
                'vendedor.user'
            ])
                ->where('id', $id)
                ->where(function ($query) use ($cliente, $user) {
                    if ($cliente) {
                        $query->where('cliente_id', $cliente->id);
                    }
                    $query->orWhere('cliente_email', $user->email);
                })
                ->first();

            if (!$pedido) {
                return response()->json([
                    'message' => 'Pedido no encontrado o no autorizado'
                ], 404);
            }

            // Convert to array and compute estimated cost & profit per detalle for frontend
            $pedidoArray = $pedido->toArray();

            $totalGanancia = 0.0;
            if (!empty($pedidoArray['detalles'])) {
                foreach ($pedidoArray['detalles'] as $idx => $detalle) {
                    $costoPromedio = 0.0;
                    if (!empty($detalle['producto']) && !empty($detalle['producto']['inventario'])) {
                        $costoPromedio = (float) ($detalle['producto']['inventario']['costo_promedio'] ?? 0);
                    }
                    $cantidad = (float) ($detalle['cantidad'] ?? 0);
                    $subtotal = (float) ($detalle['subtotal'] ?? ($detalle['precio_unitario'] * $cantidad));

                    $costoEstimado = $costoPromedio * $cantidad;
                    $gananciaDetalle = $subtotal - $costoEstimado;

                    $pedidoArray['detalles'][$idx]['costo_estimado'] = round($costoEstimado, 2);
                    $pedidoArray['detalles'][$idx]['ganancia'] = round($gananciaDetalle, 2);

                    $totalGanancia += $gananciaDetalle;
                }
            }

            $pedidoArray['metodo_pago'] = $pedidoArray['metodo_pago'] ?? ($pedidoArray['metodoPago'] ?? null);
            $pedidoArray['ganancia'] = round($totalGanancia, 2);

            return response()->json($pedidoArray);

        } catch (\Exception $e) {
            Log::error('Error al obtener detalle del pedido: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener detalle del pedido',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

