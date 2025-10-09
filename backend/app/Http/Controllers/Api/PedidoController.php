<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use App\Mail\PedidoConfirmado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{

    public function store(Request $request)
    {

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

            DB::commit();

            // Cargar relaciones necesarias para el correo
            $pedido->load(['detalles.producto', 'metodoPago']);

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
