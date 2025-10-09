<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    /**
     * Listar todas las categorías activas
     */
    public function index()
    {
        $categorias = Categoria::where('esta_activo', true)
            ->orderBy('order')
            ->get();

        return response()->json($categorias);
    }

    /**
     * Mostrar una categoría específica
     */
    public function show($id)
    {
        $categoria = Categoria::with('productos')->findOrFail($id);
        return response()->json($categoria);
    }
}
