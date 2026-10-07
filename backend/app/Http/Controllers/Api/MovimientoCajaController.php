<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MovimientoCaja;
use Illuminate\Support\Facades\Auth;

class MovimientoCajaController extends Controller
{
    public function index()
    {
        $movs = MovimientoCaja::orderBy('created_at', 'desc')->paginate(25);
        return response()->json($movs);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo' => 'required|in:ingreso,salida',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string',
        ]);

        $data['user_id'] = Auth::id();

        $mov = MovimientoCaja::create($data);

        return response()->json($mov, 201);
    }
}
