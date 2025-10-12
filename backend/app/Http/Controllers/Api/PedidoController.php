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

class PedidoController extends Controller
{

    public function store(Request $request)
    {
        // Verificar si es venta de mostrador (simplificada)
        $esVentaMostrador = $request->input('es_venta_mostrador', false);

        if ($esVentaMostrador) {
            // Validación simplificada para ventas de mostrador
            $validated = $request->validate([
                'cliente_nombre' => 'required|string|max:255',
                'cliente_email' => 'required|email',
                'cliente_telefono' => 'nullable|string',
                'metodo_pago_id' => 'required|exists:metodos_pago,id',
                'descuento_bs' => 'nullable|numeric|min:0',
                'motivo_descuento' => 'nullable|string|max:255',
                'detalles' => 'required|array|min:1',
                'detalles.*.producto_id' => 'required|exists:productos,id',
                'detalles.*.cantidad' => 'required|integer|min:1',
                'detalles.*.precio_unitario' => 'required|numeric|min:0',
                'detalles.*.subtotal' => 'required|numeric|min:0',
                'subtotal' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
            ]);

            try {
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
                    'tipo_entrega' => 'local',
                    'direccion_entrega' => null,
                    'indicaciones_especiales' => 'Venta en mostrador',
                    'subtotal' => $validated['subtotal'],
                    'descuento' => 0,
                    'descuento_bs' => $validated['descuento_bs'] ?? 0,
                    'motivo_descuento' => $validated['motivo_descuento'] ?? null,
                    'total' => $validated['total'],
                    'metodos_pago_id' => $validated['metodo_pago_id'],
                    'estado' => 'entregado', // Entregado inmediatamente
                    'estado_pago' => 'pagado',
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
            'tipo_entrega' => 'required|in:delivery,recoger',
            'direccion_entrega' => 'nullable|string',
            'indicaciones_especiales' => 'nullable|string',
            'metodos_pago_id' => 'required|exists:metodos_pago,id',
            'codigo_promocional' => 'nullable|string',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
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

            foreach ($validated['productos'] as $productoData) {
                $producto = Producto::find($productoData['id']);
                $cantidad = $productoData['cantidad'];
                $precioUnitario = $producto->precio_minorista;
                $subtotalProducto = $precioUnitario * $cantidad;

                $subtotal += $subtotalProducto;

                $productosData[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $subtotalProducto,
                ];
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

            // Cargar relaciones necesarias para el correo
            $pedido->load(['detalles.producto', 'metodoPago', 'cliente']);

            // Enviar correo de confirmación
            try {
                Mail::to($pedido->cliente_email)->send(new PedidoConfirmado($pedido));
            } catch (\Exception $e) {
                // Loguear el error pero no fallar el pedido
                Log::error('Error enviando correo de confirmación de pedido: ' . $e->getMessage());
            }

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

        return response()->json($metodos);
    }
}
