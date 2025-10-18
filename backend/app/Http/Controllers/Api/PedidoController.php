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
use Carbon\Carbon;
use App\Services\InventarioService;

class PedidoController extends Controller
{

    public function store(Request $request)
    {
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
                $insufficient = [];
                foreach ($validated['detalles'] as $detalle) {
                    $productoCheck = Producto::find($detalle['producto_id']);
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
                DB::beginTransaction();

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

                // Después de crear los detalles dentro de la transacción, forzar el descuento
                // utilizando el servicio de inventario para evitar que el observer created()
                // se dispare sin encontrar detalles.
                try {
                    $inventarioService = new InventarioService();
                    $inventarioService->descontarInventario($pedido);
                } catch (\Exception $e) {
                    // Loguear y continuar; el observer todavía puede ejecutar en updated()
                    Log::warning('No se pudo ejecutar descuento inmediato de inventario en venta mostrador: ' . $e->getMessage());
                }

                // Actualizar estadísticas del vendedor si existe
                if ($vendedorId) {
                    $vendedor = Auth::user()->vendedor;
                    $vendedor->registrarVenta(
                        $validated['total'],
                        $validated['descuento_bs'] ?? 0
                    );
                }

                DB::commit();

                return response()->json([
                    'message' => 'Venta registrada exitosamente',
                    'pedido' => $pedido->load('detalles'),
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error en venta mostrador: ' . $e->getMessage());
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
            'metodos_pago_id' => 'required|exists:metodos_pago,id',
            'codigo_promocional' => 'nullable|string',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'entrega_datetime' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        try {
            DB::beginTransaction();

            // Buscar o crear cliente
            $cliente = Cliente::where('email', $validated['cliente_email'])->first();
            
            if (!$cliente) {
                // Crear nuevo cliente
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
                    // Si se pasaron coords, comprobar que estén dentro del bounding box
                    if (!($lat >= $minLat && $lat <= $maxLat && $lng >= $minLng && $lng <= $maxLng)) {
                        // Si también la dirección textual no indica Quillacollo, rechazar
                        if (!$direccionContainsQuillacollo) {
                            return response()->json(['message' => 'La ubicación está fuera de Quillacollo, no es posible delivery local.'], 422);
                        }
                        // Si el texto contiene Quillacollo lo aceptamos aunque las coords estén un poco fuera
                    }
                } else {
                    // No se pasaron coords: aceptar delivery solo si la dirección textual indica Quillacollo
                    if (!$direccionContainsQuillacollo) {
                        return response()->json(['message' => 'Para delivery local se requieren coordenadas o que la dirección indique Quillacollo'], 422);
                    }
                }
            }

            // Recolectar productos y validar disponibilidad para envio nacional
            $blockingProducts = [];
            $insufficientStock = [];
            foreach ($validated['productos'] as $productoData) {
                $producto = Producto::find($productoData['id']);
                $cantidad = $productoData['cantidad'];
                $precioUnitario = $producto->precio_minorista;
                $subtotalProducto = $precioUnitario * $cantidad;

                // Validar stock disponible si el producto tiene inventario
                $available = null;
                if ($producto) {
                    $available = $producto->inventario->stock_actual ?? $producto->stock_actual ?? $producto->stock ?? null;
                }
                if ($available !== null && $cantidad > $available) {
                    $insufficientStock[] = [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'disponible' => $available,
                        'solicitado' => $cantidad,
                    ];
                }

                // Si el pedido es Envío Nacional y el producto no permite envio nacional, marcar
                if ($validated['tipo_entrega'] === 'envio_nacional' && !$producto->permite_envio_nacional) {
                    $blockingProducts[] = [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
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
                // Si hay productos que impiden el envío, abortar con 422 y lista
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


            $pedido = Pedido::create([
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
            ]);

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

            DB::commit();

            // El email de confirmación se enviará cuando el admin cambie el estado a "confirmado"

            return response()->json([
                'message' => 'Pedido creado exitosamente',
                'pedido' => $pedido,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
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
            $pedidos = Pedido::where('cliente_id', $cliente->id)
                ->orWhere('cliente_email', $user->email)
                ->with(['detalles.producto.imagenes', 'metodoPago'])
                ->orderBy('created_at', 'desc')
                ->get();

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

