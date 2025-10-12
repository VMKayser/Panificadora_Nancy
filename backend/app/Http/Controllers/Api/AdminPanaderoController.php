<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Panadero;
use Illuminate\Validation\Rule;

class AdminPanaderoController extends Controller
{
    public function index(Request $request)
    {
        $query = Panadero::query();
        if ($request->has('activo')) {
            $query->where('activo', (bool) $request->activo);
        }
        return response()->json($query->paginate(20));
    }

    public function show($id)
    {
        $panadero = Panadero::findOrFail($id);
        return response()->json($panadero);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|unique:panaderos,email',
            'telefono' => 'required|string|max:20',
            'ci' => 'required|string|unique:panaderos,ci',
            'fecha_ingreso' => 'required|date',
            'turno' => ['required', Rule::in(['mañana','tarde','noche','rotativo'])],
            'especialidad' => ['required', Rule::in(['pan','reposteria','ambos'])],
            'salario_base' => 'required|numeric',
        ]);

        $panadero = Panadero::create($validated);
        return response()->json(['data' => $panadero], 201);
    }

    public function update(Request $request, $id)
    {
        $panadero = Panadero::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'email' => ['sometimes','email', Rule::unique('panaderos','email')->ignore($panadero->id)],
            'telefono' => 'sometimes|string|max:20',
            'ci' => ['sometimes','string', Rule::unique('panaderos','ci')->ignore($panadero->id)],
            'fecha_ingreso' => 'sometimes|date',
            'turno' => [Rule::in(['mañana','tarde','noche','rotativo'])],
            'especialidad' => [Rule::in(['pan','reposteria','ambos'])],
            'salario_base' => 'sometimes|numeric',
        ]);

        $panadero->update($validated);
        return response()->json(['data' => $panadero]);
    }

    public function destroy($id)
    {
        $panadero = Panadero::findOrFail($id);
        $panadero->delete();
        return response()->json(['message' => 'Panadero eliminado']);
    }
}
