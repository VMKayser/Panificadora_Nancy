<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exceptions\StockInsuficienteException;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use App\Support\HoraNegocio;
use App\Support\SafeTransaction;
use App\Support\SecurityLog;
use Carbon\Carbon;
use App\Services\InventarioService;

class PedidoController extends Controller
{
    /** Margen para no rechazar pedidos por segundos de diferencia entre relojes. */
    private const TOLERANCIA_MINUTOS = 5;

    public function store(Request $request)
    {
        $this->normalizarExtrasLegacy($request);

        // Compat: combinar fecha_entrega + hora_entrega en entrega_datetime si no viene el campo
        if (!$request->has('entrega_datetime') && $request->filled('fecha_entrega') && $request->filled('hora_entrega')) {
            try {
                $combined = Carbon::createFromFormat('Y-m-d H:i', $request->input('fecha_entrega') . ' ' . $request->input('hora_entrega'));
                $request->merge(['entrega_datetime' => $combined->format('Y-m-d H:i:s')]);
            } catch (\Exception $e) {
                // ignore invalid formats; validation will catch it later
            }
        }

        if ($request->boolean('es_venta_mostrador')) {
            // La venta de mostrador marca el pedido como entregado y pagado y
            // descuenta stock: solo la puede registrar personal autorizado.
            $user = $request->user('sanctum');
            if (!$user || !$user->hasAnyRole(['admin', 'vendedor'])) {
                SecurityLog::accesoDenegado($request, 'venta_mostrador');
                return response()->json(['message' => 'No tienes permisos para registrar ventas de mostrador'], $user ? 403 : 401);
            }
            return $this->storeVentaMostrador($request);
        }

        return $this->storePedidoWeb($request);
    }

    /**
     * Venta de mostrador desde el panel del vendedor (ruta protegida por rol).
     */
    public function storeMostrador(Request $request)
    {
        $this->normalizarExtrasLegacy($request);
        return $this->storeVentaMostrador($request);
    }

    /**
     * Venta de mostrador: se entrega y cobra en el momento, así que exige stock
     * y lo descuenta en la misma transacción. Los precios salen de la BD.
     */
    private function storeVentaMostrador(Request $request)
    {
        $validated = $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            'cliente_email' => 'required|email',
            'cliente_telefono' => 'nullable|string',
            'metodos_pago_id' => 'required|exists:metodos_pago,id',
            'descuento_bs' => 'nullable|numeric|min:0',
            'motivo_descuento' => 'nullable|string|max:255',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|integer|exists:productos,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.extra_index' => 'nullable|integer|min:0',
            'entrega_datetime' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        $user = $request->user('sanctum');
        $vendedor = $user?->vendedor;

        $productos = $this->cargarProductos(collect($validated['detalles'])->pluck('producto_id'));
        [$lineas, $subtotal] = $this->armarLineas($validated['detalles'], 'producto_id', $productos, false);

        $descuento = round((float) ($validated['descuento_bs'] ?? 0), 2);
        if ($descuento > 0) {
            if (empty(trim($validated['motivo_descuento'] ?? ''))) {
                throw ValidationException::withMessages(['motivo_descuento' => 'Indica el motivo del descuento.']);
            }
            if ($descuento > $subtotal) {
                throw ValidationException::withMessages(['descuento_bs' => 'El descuento no puede ser mayor al subtotal.']);
            }
            $esAdmin = $user && $user->hasAnyRole(['admin']);
            if (!$esAdmin && !($vendedor && $vendedor->puedeOtorgarDescuento($descuento))) {
                $maximo = $vendedor && $vendedor->puede_dar_descuentos ? " (máximo Bs {$vendedor->descuento_maximo_bs})" : '';
                throw ValidationException::withMessages(['descuento_bs' => "No tienes permiso para dar este descuento{$maximo}."]);
            }
        }
        $total = round($subtotal - $descuento, 2);

        try {
            $pedido = $this->conReintentoDeNumero(fn () => SafeTransaction::run(function () use ($validated, $lineas, $subtotal, $descuento, $total, $vendedor) {
                $prefijo = 'VM-' . now(HoraNegocio::zona())->format('Ymd') . '-';

                $pedido = Pedido::create([
                    'numero_pedido' => Pedido::generarNumero($prefijo),
                    'cliente_id' => null,
                    'vendedor_id' => $vendedor?->id,
                    'cliente_nombre' => $validated['cliente_nombre'],
                    'cliente_apellido' => '',
                    'cliente_email' => $validated['cliente_email'],
                    'cliente_telefono' => $validated['cliente_telefono'] ?? '00000000',
                    'tipo_entrega' => 'recoger',
                    'direccion_entrega' => null,
                    'indicaciones_especiales' => 'Venta en mostrador',
                    'subtotal' => $subtotal,
                    'descuento' => 0,
                    'descuento_bs' => $descuento,
                    'motivo_descuento' => $descuento > 0 ? $validated['motivo_descuento'] : null,
                    'total' => $total,
                    'metodos_pago_id' => $validated['metodos_pago_id'],
                    'estado' => 'entregado', // Entregado inmediatamente
                    'estado_pago' => 'pagado',
                    'fecha_pago' => now(),
                    'fecha_entrega' => $validated['entrega_datetime'] ?? null,
                ]);

                $this->crearDetalles($pedido, $lineas);

                (new InventarioService())->aplicarDescuento($pedido, true);

                if ($vendedor) {
                    $vendedor->registrarVenta($total, $descuento);
                }

                return $pedido;
            }));
        } catch (StockInsuficienteException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'insufficient_stock' => $e->detalle,
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error en venta mostrador: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $user?->id,
                'productos' => collect($validated['detalles'])->pluck('producto_id')->all(),
            ]);
            return response()->json(['message' => 'Error al procesar la venta'], 500);
        }

        return response()->json([
            'message' => 'Venta registrada exitosamente',
            'pedido' => $pedido->load('detalles'),
        ], 201);
    }

    /**
     * Pedido web: no se limita por stock (la panadería hornea a diario y por
     * encargo); el stock se descuenta al marcarlo como entregado.
     */
    private function storePedidoWeb(Request $request)
    {
        $validated = $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            // El checkout solo pide nombre y celular; correo y apellido son opcionales
            'cliente_apellido' => 'nullable|string|max:255',
            'cliente_email' => 'nullable|email|max:150',
            'cliente_telefono' => 'required|string|max:20',
            'nit_ci_factura' => 'nullable|string|max:20',
            'tipo_entrega' => 'required|in:delivery,recoger,envio_nacional',
            'direccion_entrega' => 'required_if:tipo_entrega,delivery,envio_nacional|nullable|string|max:500',
            'direccion_lat' => 'nullable|numeric',
            'direccion_lng' => 'nullable|numeric',
            'indicaciones_especiales' => 'nullable|string',
            'envio_por_pagar' => 'nullable|boolean',
            'empresa_transporte' => 'nullable|string|max:255',
            'metodos_pago_id' => 'required|exists:metodos_pago,id',
            'codigo_promocional' => 'nullable|string',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|integer|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.extra_index' => 'nullable|integer|min:0',
            'productos.*.personalizacion' => 'nullable|string|max:180',
            'entrega_datetime' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        // Delivery local: si el cliente compartió su ubicación, debe caer en
        // Quillacollo. Sin ubicación basta la dirección escrita (la tienda la
        // confirma por WhatsApp); antes se exigía que dijera "Quillacollo".
        if ($validated['tipo_entrega'] === 'delivery') {
            $lat = $validated['direccion_lat'] ?? null;
            $lng = $validated['direccion_lng'] ?? null;
            $direccionContainsQuillacollo = preg_match('/quillacollo/i', $validated['direccion_entrega'] ?? '');

            // Bounding box aproximado de Quillacollo
            $minLat = -17.45; $maxLat = -17.22; $minLng = -66.35; $maxLng = -66.10;

            if ($lat && $lng && !($lat >= $minLat && $lat <= $maxLat && $lng >= $minLng && $lng <= $maxLng) && !$direccionContainsQuillacollo) {
                return response()->json(['message' => 'La ubicación está fuera de Quillacollo, no es posible delivery local.'], 422);
            }
        }

        $productos = $this->cargarProductos(collect($validated['productos'])->pluck('id'), true);
        $this->validarVentaWeb($validated['productos'], $productos);

        if ($validated['tipo_entrega'] === 'envio_nacional') {
            $blockingProducts = collect($validated['productos'])
                ->filter(fn ($item) => !$productos->get($item['id'])->permite_envio_nacional)
                ->map(fn ($item) => [
                    'id' => $item['id'],
                    'nombre' => $productos->get($item['id'])->nombre,
                    'cantidad' => $item['cantidad'],
                ])->values()->all();

            if (!empty($blockingProducts)) {
                return response()->json([
                    'message' => 'Algunos productos no permiten envío nacional',
                    'blocking_products' => $blockingProducts,
                ], 422);
            }
        }

        $this->validarFechaEntrega($validated['entrega_datetime'] ?? null, $productos);

        [$lineas, $subtotal] = $this->armarLineas($validated['productos'], 'id', $productos, true);

        // Buscar o crear cliente. Sin correo se reconoce por el celular, pero
        // solo entre clientes que tampoco tienen correo: así un pedido no
        // termina en la cuenta de otra persona que use ese número.
        $email = $validated['cliente_email'] ?? null;
        $cliente = $email
            ? Cliente::where('email', $email)->first()
            : Cliente::whereNull('email')->where('telefono', $validated['cliente_telefono'])->first();
        $nitCi = $validated['nit_ci_factura'] ?? null;

        if (!$cliente) {
            $cliente = Cliente::create([
                'nombre' => $validated['cliente_nombre'],
                'apellido' => $validated['cliente_apellido'] ?? '',
                'email' => $email,
                'telefono' => $validated['cliente_telefono'],
                'nit_ci' => $nitCi, // Guardar NIT/CI
                'direccion' => $validated['direccion_entrega'] ?? null,
                'tipo_cliente' => 'regular',
                'activo' => true,
                'total_pedidos' => 0,
                'total_gastado' => 0,
            ]);
            Log::info("Nuevo cliente creado: {$cliente->id}");
        } else {
            // Actualizar NIT/CI si viene en el pedido y el cliente no lo tiene o es diferente
            if ($nitCi && $cliente->nit_ci !== $nitCi) {
                $cliente->nit_ci = $nitCi;
                $cliente->save();
            }
            Log::info("Cliente existente encontrado: {$cliente->id}");
        }

        try {
            $pedido = $this->conReintentoDeNumero(fn () => SafeTransaction::run(function () use ($validated, $cliente, $lineas, $subtotal) {
                $entrega = $validated['entrega_datetime'] ?? null;

                $pedidoData = [
                    'numero_pedido' => Pedido::generarNumero('PED-' . now(HoraNegocio::zona())->format('Y') . '-'),
                    'cliente_id' => $cliente->id, // Vincular con el cliente
                    'cliente_nombre' => $validated['cliente_nombre'],
                    'cliente_apellido' => $validated['cliente_apellido'] ?? '',
                    'cliente_email' => $validated['cliente_email'] ?? null,
                    'cliente_telefono' => $validated['cliente_telefono'],
                    'tipo_entrega' => $validated['tipo_entrega'],
                    'direccion_entrega' => $validated['direccion_entrega'] ?? null,
                    // Fecha/hora local de Bolivia tal cual la eligió el cliente
                    'fecha_entrega' => $entrega,
                    'hora_entrega' => $entrega ? substr($entrega, 11, 8) : null,
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
                if (Schema::hasColumn('pedidos', 'nit_ci_factura')) {
                    $pedidoData['nit_ci_factura'] = $validated['nit_ci_factura'] ?? null;
                }
                if (Schema::hasColumn('pedidos', 'envio_por_pagar')) {
                    $pedidoData['envio_por_pagar'] = $validated['envio_por_pagar'] ?? false;
                }
                if (Schema::hasColumn('pedidos', 'empresa_transporte')) {
                    $pedidoData['empresa_transporte'] = $validated['empresa_transporte'] ?? null;
                }

                $pedido = Pedido::create($pedidoData);
                $this->crearDetalles($pedido, $lineas);

                // Actualizar estadísticas del cliente
                $cliente->actualizarEstadisticas();

                return $pedido;
            }));
        } catch (\Throwable $e) {
            Log::error('Error al crear el pedido: ' . $e->getMessage(), [
                'exception' => $e,
                'cliente_id' => $cliente->id,
                'productos' => collect($validated['productos'])->pluck('id')->all(),
            ]);
            return response()->json(['message' => 'Error al crear el pedido'], 500);
        }

        // El email de confirmación se enviará cuando el admin cambie el estado a "confirmado".
        // Las líneas van en la respuesta para la página de confirmación y el mensaje de WhatsApp.
        return response()->json([
            'message' => 'Pedido creado exitosamente',
            'pedido' => $pedido->load('detalles'),
        ], 201);
    }

    /**
     * Frontends anteriores mandan los extras como id "5-extra-0" (producto 5,
     * extra 0). Se traducen al formato nuevo: id del producto + extra_index.
     */
    private function normalizarExtrasLegacy(Request $request): void
    {
        foreach (['productos' => 'id', 'detalles' => 'producto_id'] as $lista => $campo) {
            $items = $request->input($lista);
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $i => $item) {
                $id = is_array($item) ? ($item[$campo] ?? null) : null;
                if (is_string($id) && preg_match('/^(\d+)-extra-(\d+)$/', $id, $m)) {
                    $items[$i][$campo] = (int) $m[1];
                    $items[$i]['extra_index'] = (int) $m[2];
                }
            }
            $request->merge([$lista => $items]);
        }
    }

    private function cargarProductos(Collection $ids, bool $soloActivos = false): Collection
    {
        $productos = Producto::whereIn('id', $ids->unique()->values()->all())->get()->keyBy('id');

        $noDisponibles = $ids->unique()->filter(function ($id) use ($productos, $soloActivos) {
            $producto = $productos->get($id);
            return !$producto || ($soloActivos && !$producto->esta_activo);
        });
        if ($noDisponibles->isNotEmpty()) {
            throw ValidationException::withMessages([
                'productos' => 'Algunos productos ya no están disponibles: ' . $noDisponibles->implode(', '),
            ]);
        }

        return $productos;
    }

    /**
     * Reglas de temporada para la web: productos con el precio por confirmar
     * (se consultan por WhatsApp), pedidos cerrados por fecha y el dato que
     * el producto pide al cliente (ej. nombre del difunto).
     */
    private function validarVentaWeb(array $items, Collection $productos): void
    {
        foreach ($productos as $producto) {
            if ($producto->precio_por_confirmar) {
                throw ValidationException::withMessages([
                    'productos' => "El precio de {$producto->nombre} está por confirmar. Consúltalo por WhatsApp.",
                ]);
            }
            if ($producto->pedidosCerrados()) {
                throw ValidationException::withMessages([
                    'productos' => "Los pedidos de {$producto->nombre} se cerraron el " . $producto->pedidos_hasta->format('d/m/Y') . '.',
                ]);
            }
        }

        foreach ($items as $i => $item) {
            $producto = $productos->get($item['id']);
            $etiqueta = trim((string) $producto->etiqueta_personalizacion);
            $esExtra = ($item['extra_index'] ?? null) !== null;
            if ($etiqueta !== '' && !$esExtra && trim((string) ($item['personalizacion'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    "productos.{$i}.personalizacion" => "Escribe «{$etiqueta}» para {$producto->nombre}.",
                ]);
            }
        }
    }

    /**
     * Arma las líneas del pedido con los precios guardados en la BD (nunca los
     * del navegador). Los extras salen de extras_disponibles del producto.
     *
     * @return array{0: array, 1: float} [líneas, subtotal]
     */
    private function armarLineas(array $items, string $campoId, Collection $productos, bool $conAnticipacion): array
    {
        $lineas = [];
        $subtotal = 0.0;

        foreach ($items as $i => $item) {
            $producto = $productos->get($item[$campoId]);
            $cantidad = (int) $item['cantidad'];
            $extraIndex = $item['extra_index'] ?? null;

            if ($extraIndex !== null) {
                $extra = ($producto->extras_disponibles ?? [])[$extraIndex] ?? null;
                if (!is_array($extra) || empty($extra['nombre'])) {
                    throw ValidationException::withMessages([
                        "{$i}.extra_index" => "El extra seleccionado de {$producto->nombre} ya no está disponible.",
                    ]);
                }
                $precio = round(
                    (float) ($extra['precio_unitario'] ?? $extra['precio'] ?? 0)
                    * (float) ($extra['cantidad_minima'] ?? $extra['cantidad'] ?? 1),
                    2
                );
                $nombre = "{$producto->nombre} - {$extra['nombre']}";
            } else {
                $precio = (float) $producto->precio_minorista;
                $nombre = $producto->nombre;
            }

            // Se guarda con la etiqueta delante para que el panel, el correo y
            // WhatsApp lo muestren tal cual: "Nombre del difunto: Juan Pérez"
            $texto = $extraIndex === null ? trim((string) ($item['personalizacion'] ?? '')) : '';
            $etiqueta = trim((string) $producto->etiqueta_personalizacion);
            $personalizacion = $texto === '' ? null
                : ($etiqueta !== '' ? "{$etiqueta}: {$texto}" : $texto);

            $lineaSubtotal = round($precio * $cantidad, 2);
            $subtotal += $lineaSubtotal;

            $lineas[] = [
                'productos_id' => $producto->id,
                'nombre_producto' => $nombre,
                'personalizacion' => $personalizacion,
                'precio_unitario' => $precio,
                'cantidad' => $cantidad,
                'subtotal' => $lineaSubtotal,
                'es_extra' => $extraIndex !== null,
                'requiere_anticipacion' => $conAnticipacion ? (bool) $producto->requiere_tiempo_anticipacion : false,
                'tiempo_anticipacion' => $conAnticipacion ? $producto->tiempo_anticipacion : null,
                'unidad_tiempo' => $conAnticipacion ? $producto->unidad_tiempo : null,
            ];
        }

        return [$lineas, round($subtotal, 2)];
    }

    private function crearDetalles(Pedido $pedido, array $lineas): void
    {
        foreach ($lineas as $linea) {
            DetallePedido::create(['pedidos_id' => $pedido->id] + $linea);
        }
    }

    /**
     * La entrega no puede ser en el pasado ni antes del tiempo de anticipación
     * del producto que más anticipación pide. Las fechas son hora de Bolivia.
     */
    private function validarFechaEntrega(?string $entregaDatetime, Collection $productos): void
    {
        if (!$entregaDatetime) {
            return;
        }

        $entrega = Carbon::parse($entregaDatetime);
        $ahora = HoraNegocio::ahoraLocal();

        if ($entrega->lt($ahora->copy()->subMinutes(self::TOLERANCIA_MINUTOS))) {
            throw ValidationException::withMessages([
                'entrega_datetime' => 'La fecha y hora de entrega ya pasó. Elige una fecha futura.',
            ]);
        }

        $minutosPorUnidad = ['horas' => 60, 'dias' => 1440, 'semanas' => 10080];
        $masExigente = $productos
            ->filter(fn ($p) => $p->requiere_tiempo_anticipacion && $p->tiempo_anticipacion > 0)
            ->map(fn ($p) => [
                'nombre' => $p->nombre,
                'minutos' => $p->tiempo_anticipacion * ($minutosPorUnidad[$p->unidad_tiempo] ?? 60),
                'texto' => "{$p->tiempo_anticipacion} {$p->unidad_tiempo}",
            ])
            ->sortByDesc('minutos')
            ->first();

        if (!$masExigente) {
            return;
        }

        $minimo = $ahora->copy()->addMinutes($masExigente['minutos']);
        if ($entrega->lt($minimo->copy()->subMinutes(self::TOLERANCIA_MINUTOS))) {
            throw ValidationException::withMessages([
                'entrega_datetime' => "{$masExigente['nombre']} necesita {$masExigente['texto']} de anticipación. "
                    . 'La entrega más temprana posible es el ' . $minimo->format('d/m/Y H:i') . '.',
            ]);
        }
    }

    /**
     * Dos pedidos simultáneos pueden calcular el mismo número; si la BD lo
     * rechaza por el índice único, se reintenta con el siguiente.
     */
    private function conReintentoDeNumero(callable $crear, int $intentos = 3)
    {
        for ($intento = 1; ; $intento++) {
            try {
                return $crear();
            } catch (QueryException $e) {
                $esNumeroDuplicado = ($e->errorInfo[0] ?? null) === '23000'
                    && str_contains($e->getMessage(), 'numero_pedido');
                if (!$esNumeroDuplicado || $intento >= $intentos) {
                    throw $e;
                }
                Log::warning("numero_pedido duplicado, reintentando ({$intento})");
            }
        }
    }


    public function metodosPago()
    {
        try {
            $metodos = \App\Models\MetodoPago::where('esta_activo', true)
                ->orderBy('orden')
                ->get();
        } catch (\Throwable $e) {
            Log::error('PedidoController::metodosPago - failed to load from DB', [
                'error' => $e->getMessage(),
            ]);
            $metodos = collect();
        }

        if ($metodos->isNotEmpty()) {
            $metodos = $metodos->map(function ($m) {
                $m->icono_url = $m->icono_url; // Accessor ensures full URL
                return $m;
            });

            return response()->json($metodos);
        }

        $fallbacks = collect(config('metodos_pago.defaults', []))->map(function ($method, $index) {
            $icon = $method['icono'] ?? null;
            $iconoUrl = $method['icono_url'] ?? $icon;
            if ($iconoUrl && !preg_match('/^https?:\/\//', $iconoUrl)) {
                $iconoUrl = url(ltrim($iconoUrl, '/'));
            }

            return [
                'id' => $method['id'] ?? sprintf('fallback-%s', $method['codigo'] ?? $index),
                'codigo' => $method['codigo'] ?? ('fallback-' . $index),
                'nombre' => $method['nombre'] ?? 'Método de pago',
                'descripcion' => $method['descripcion'] ?? null,
                'esta_activo' => $method['esta_activo'] ?? true,
                'icono' => $icon,
                'icono_url' => $iconoUrl,
                'comision_porcentaje' => $method['comision_porcentaje'] ?? 0,
                'orden' => $method['orden'] ?? (($index + 1) * 10),
            ];
        });

        if ($fallbacks->isEmpty()) {
            Log::warning('PedidoController::metodosPago - no DB rows and no configured defaults.');
        } else {
            Log::info('PedidoController::metodosPago - returning configured fallback methods');
        }

        return response()->json($fallbacks);
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
            ], 500);
        }
    }
}

