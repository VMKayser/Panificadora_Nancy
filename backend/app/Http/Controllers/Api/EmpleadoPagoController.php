<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmpleadoPago;
use App\Models\Panadero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmpleadoPagoController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'empleado_tipo' => 'required|string|in:panadero,vendedor',
            'empleado_id' => 'required|integer',
            'monto' => 'required|numeric|min:0',
            'kilos_pagados' => 'nullable|numeric|min:0',
            'comision_pagada' => 'nullable|numeric|min:0',
            'tipo_pago' => 'nullable|string|in:sueldo_fijo,comision,pago_produccion,otro',
            'es_sueldo_fijo' => 'nullable|boolean',
            'metodos_pago_id' => 'nullable|integer|exists:metodos_pago,id',
            'notas' => 'nullable|string',
        ]);

        // Map empleado_tipo to concrete model class for polymorphic relation
        $map = [
            'panadero' => Panadero::class,
            'vendedor' => \App\Models\Vendedor::class,
        ];

        $empleadoTipo = $data['empleado_tipo'];
        $empleadoId = $data['empleado_id'];

        $empleadoClass = $map[$empleadoTipo] ?? null;
        if (!$empleadoClass) {
            return response()->json(['message' => 'Tipo de empleado no soportado'], 422);
        }

        // Verify existence and validate limits before creating the payment
        $empleado = $empleadoClass::find($empleadoId);
        if (!$empleado) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        // Validation: do not allow paying more than available
        if ($empleadoTipo === 'vendedor') {
            $disponible = floatval($empleado->comision_acumulada ?? 0);
            $toDeduct = !empty($data['comision_pagada']) ? floatval($data['comision_pagada']) : floatval($data['monto']);
            if ($toDeduct > $disponible) {
                return response()->json(['message' => 'No se puede pagar más que la comisión acumulada'], 422);
            }
        }

        if ($empleadoTipo === 'panadero' && !is_null($data['kilos_pagados'])) {
            $disponibleKilos = floatval($empleado->total_kilos_producidos ?? 0);
            if (floatval($data['kilos_pagados']) > $disponibleKilos) {
                return response()->json(['message' => 'Kilos pagados mayor que kilos producidos'], 422);
            }
        }

        return DB::transaction(function () use ($data, $empleadoClass, $empleadoId, $empleadoTipo, $empleado) {
            $data['created_by'] = Auth::id() ?? null;

            // Populate polymorphic columns for comerce
            $data['empleadoable_type'] = $empleadoClass;
            $data['empleadoable_id'] = $empleadoId;

            // Create payment record
            $pago = EmpleadoPago::create($data);

            // Apply reductions
            if ($empleadoTipo === 'panadero') {
                // subtract kilos_pagados or zero
                if (!is_null($data['kilos_pagados'])) {
                    $empleado->total_kilos_producidos = max(0, ($empleado->total_kilos_producidos ?? 0) - $data['kilos_pagados']);
                } else {
                    $empleado->total_kilos_producidos = 0;
                }
                $empleado->save();
            }

            if ($empleadoTipo === 'vendedor') {
                $reduccion = !empty($data['comision_pagada']) ? floatval($data['comision_pagada']) : floatval($data['monto']);
                $empleado->comision_acumulada = max(0, floatval($empleado->comision_acumulada ?? 0) - $reduccion);
                $empleado->save();
            }

            return response()->json(['success' => true, 'pago' => $pago], 201);
        });
    }

    public function index(Request $request)
    {
        $query = EmpleadoPago::query();
        if ($empleado_tipo = $request->get('empleado_tipo')) {
            $query->where('empleado_tipo', $empleado_tipo);
        }
        if ($empleado_id = $request->get('empleado_id')) {
            $query->where('empleado_id', $empleado_id);
        }
        $result = $query->orderBy('created_at', 'desc')->paginate(25);
        return response()->json($result);
    }
}
