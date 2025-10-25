<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfiguracionController extends Controller
{
    /**
     * Obtener todas las configuraciones
     */
    public function index()
    {
        $configuraciones = ConfiguracionSistema::all()->keyBy('clave');
        
        return response()->json([
            'configuraciones' => $configuraciones,
            'grupos' => $this->agruparConfiguraciones($configuraciones)
        ]);
    }

    /**
     * Obtener una configuración específica
     */
    public function show($clave)
    {
        $config = ConfiguracionSistema::where('clave', $clave)->first();
        
        if (!$config) {
            return response()->json([
                'message' => 'Configuración no encontrada'
            ], 404);
        }

        return response()->json($config);
    }

    /**
     * Actualizar o crear una configuración
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clave' => 'required|string|max:100',
            'valor' => 'required',
            'tipo' => 'required|in:texto,numero,boolean,json',
            'descripcion' => 'nullable|string',
            'grupo' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Use query builder updateOrInsert to avoid firing model events / creating savepoints
        ConfiguracionSistema::query()->updateOrInsert(
            ['clave' => $request->clave],
            [
                'valor' => $request->valor,
                'tipo' => $request->tipo,
                'descripcion' => $request->descripcion,
                'grupo' => $request->grupo
            ]
        );

        // Fetch the model instance to return
        $config = ConfiguracionSistema::where('clave', $request->clave)->first();

        return response()->json([
            'message' => 'Configuración guardada exitosamente',
            'configuracion' => $config
        ]);
    }

    /**
     * Actualizar múltiples configuraciones a la vez
     */
    public function actualizarMultiples(Request $request)
    {
        $configuraciones = $request->configuraciones;

        if (!is_array($configuraciones)) {
            return response()->json([
                'message' => 'El formato de datos es inválido'
            ], 422);
        }

        foreach ($configuraciones as $config) {
            ConfiguracionSistema::updateOrCreate(
                ['clave' => $config['clave']],
                [
                    'valor' => $config['valor'],
                    'tipo' => $config['tipo'] ?? 'texto',
                    'descripcion' => $config['descripcion'] ?? null,
                    'grupo' => $config['grupo'] ?? null
                ]
            );
        }

        return response()->json([
            'message' => 'Configuraciones actualizadas exitosamente'
        ]);
    }

    /**
     * Eliminar una configuración
     */
    public function destroy($clave)
    {
        $config = ConfiguracionSistema::where('clave', $clave)->first();

        if (!$config) {
            return response()->json([
                'message' => 'Configuración no encontrada'
            ], 404);
        }

        $config->delete();

        return response()->json([
            'message' => 'Configuración eliminada exitosamente'
        ]);
    }

    /**
     * Inicializar configuraciones por defecto
     */
    public function inicializarDefecto()
    {
        $configuracionesDefecto = [
            // Producción
            [
                'clave' => 'precio_kilo_produccion',
                'valor' => '15.00',
                'tipo' => 'numero',
                'descripcion' => 'Precio pagado por kilo de producción a panaderos',
                'grupo' => 'produccion'
            ],
            [
                'clave' => 'meta_produccion_diaria',
                'valor' => '500',
                'tipo' => 'numero',
                'descripcion' => 'Meta de kilos a producir diariamente',
                'grupo' => 'produccion'
            ],
            
            // Ventas
            [
                'clave' => 'comision_vendedor_defecto',
                'valor' => '3.00',
                'tipo' => 'numero',
                'descripcion' => 'Porcentaje de comisión por defecto para vendedores',
                'grupo' => 'ventas'
            ],
            [
                'clave' => 'descuento_maximo_defecto',
                'valor' => '50.00',
                'tipo' => 'numero',
                'descripcion' => 'Descuento máximo en Bs que puede dar un vendedor',
                'grupo' => 'ventas'
            ],
            [
                'clave' => 'descuento_mayorista_porcentaje',
                'valor' => '10.00',
                'tipo' => 'numero',
                'descripcion' => 'Porcentaje de descuento para clientes mayoristas',
                'grupo' => 'ventas'
            ],
            
            // Inventario
            [
                'clave' => 'stock_minimo_alerta',
                'valor' => '10',
                'tipo' => 'numero',
                'descripcion' => 'Cantidad mínima de stock para generar alerta',
                'grupo' => 'inventario'
            ],
            [
                'clave' => 'dias_anticipacion_pedidos',
                'valor' => '1',
                'tipo' => 'numero',
                'descripcion' => 'Días de anticipación requeridos para pedidos especiales',
                'grupo' => 'inventario'
            ],
            
            // Sistema
            [
                'clave' => 'nombre_empresa',
                'valor' => 'Panificadora Nancy',
                'tipo' => 'texto',
                'descripcion' => 'Nombre de la empresa',
                'grupo' => 'sistema'
            ],
            [
                'clave' => 'telefono_contacto',
                'valor' => '+591 764 90687',
                'tipo' => 'texto',
                'descripcion' => 'Teléfono de contacto principal',
                'grupo' => 'sistema'
            ],
            [
                'clave' => 'whatsapp_empresa',
                'valor' => '+59176490687',
                'tipo' => 'texto',
                'descripcion' => 'Número de WhatsApp para envío de comprobantes',
                'grupo' => 'sistema'
            ],
            [
                'clave' => 'qr_mensaje_plantilla',
                'valor' => 'Pago por pedido en {empresa} — Total: Bs {total}. Envía el comprobante a {whatsapp} con tu número de pedido {numero_pedido}.',
                'tipo' => 'texto',
                'descripcion' => 'Plantilla del mensaje que se copia al cliente cuando usa QR. Soporta {empresa},{total},{whatsapp},{numero_pedido}',
                'grupo' => 'sistema'
            ],
            [
                'clave' => 'direccion',
                'valor' => 'Av. Martín Cardenas, Quillacollo, Cochabamba',
                'tipo' => 'texto',
                'descripcion' => 'Dirección del negocio',
                'grupo' => 'sistema'
            ],
            [
                'clave' => 'horario_apertura',
                'valor' => '07:00',
                'tipo' => 'texto',
                'descripcion' => 'Hora de apertura',
                'grupo' => 'sistema'
            ],
            [
                'clave' => 'horario_cierre',
                'valor' => '20:00',
                'tipo' => 'texto',
                'descripcion' => 'Hora de cierre',
                'grupo' => 'sistema'
            ]
        ];

        foreach ($configuracionesDefecto as $config) {
            // Use updateOrInsert for idempotent seeding without model events
            ConfiguracionSistema::query()->updateOrInsert(
                ['clave' => $config['clave']],
                $config
            );
        }

        return response()->json([
            'message' => 'Configuraciones inicializadas exitosamente',
            'total' => count($configuracionesDefecto)
        ]);
    }

    /**
     * Agrupar configuraciones por grupo
     */
    private function agruparConfiguraciones($configuraciones)
    {
        $grupos = [];
        
        foreach ($configuraciones as $config) {
            $grupo = $config->grupo ?? 'general';
            
            if (!isset($grupos[$grupo])) {
                $grupos[$grupo] = [];
            }
            
            $grupos[$grupo][] = $config;
        }

        return $grupos;
    }

    /**
     * Obtener valor de una configuración específica
     */
    public function getValor($clave)
    {
        $config = ConfiguracionSistema::where('clave', $clave)->first();

        if (!$config) {
            return response()->json([
                'message' => 'Configuración no encontrada'
            ], 404);
        }

        // Convertir el valor según el tipo
        $valor = $config->valor;
        
        switch ($config->tipo) {
            case 'numero':
                $valor = (float) $valor;
                break;
            case 'boolean':
                $valor = filter_var($valor, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'json':
                $valor = json_decode($valor, true);
                break;
        }

        return response()->json([
            'clave' => $config->clave,
            'valor' => $valor,
            'tipo' => $config->tipo
        ]);
    }
}
