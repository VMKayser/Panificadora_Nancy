<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios con filtros
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filtro por búsqueda (nombre o email)
        if ($request->has('buscar') && $request->buscar) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        // Filtro por rol
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Ordenar por fecha de creación (más recientes primero)
        $query->orderBy('created_at', 'desc');

        $usuarios = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $usuarios
        ]);
    }

    /**
     * Obtener un usuario específico
     */
    public function show($id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $usuario
        ]);
    }

    /**
     * Crear un nuevo usuario
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,vendedor,panadero,cliente'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $usuario = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => $usuario
        ], 201);
    }

    /**
     * Actualizar un usuario
     */
    public function update(Request $request, $id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:6',
            'role' => 'sometimes|required|in:admin,vendedor,panadero,cliente'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Actualizar campos
        if ($request->has('name')) {
            $usuario->name = $request->name;
        }

        if ($request->has('email')) {
            $usuario->email = $request->email;
        }

        if ($request->has('password') && $request->password) {
            $usuario->password = Hash::make($request->password);
        }

        if ($request->has('role')) {
            $usuario->role = $request->role;
        }

        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente',
            'data' => $usuario
        ]);
    }

    /**
     * Cambiar rol de un usuario
     */
    public function cambiarRol(Request $request, $id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,vendedor,panadero,cliente'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $usuario->role = $request->role;
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado exitosamente',
            'data' => $usuario
        ]);
    }

    /**
     * Eliminar un usuario
     */
    public function destroy($id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // No permitir eliminar al usuario autenticado
        if ($usuario->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propio usuario'
            ], 403);
        }

        $usuario->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente'
        ]);
    }

    /**
     * Obtener estadísticas de usuarios
     */
    public function estadisticas()
    {
        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'vendedores' => User::where('role', 'vendedor')->count(),
            'panaderos' => User::where('role', 'panadero')->count(),
            'clientes' => User::where('role', 'cliente')->count(),
            'recientes' => User::orderBy('created_at', 'desc')->take(5)->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Listar usuarios disponibles para ser vendedores (sin vendedor asignado)
     */
    public function usuariosDisponiblesVendedor()
    {
        $usuarios = User::where('role', 'vendedor')
            ->whereDoesntHave('vendedor')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $usuarios
        ]);
    }
}
