<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

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

        // Filtro por rol: preferir el pivot `roles`, pero mantener fallback a la columna `users.role`
        if ($request->has('role') && $request->role) {
            $role = $request->role;
            $query->where(function ($q) use ($role) {
                $q->whereHas('roles', function ($r) use ($role) {
                    $r->where('name', $role);
                })->orWhere('role', $role);
            });
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
            // password optional: admin may provide or let system generate
            'password' => 'sometimes|nullable|string|min:6',
            'role' => 'required|in:admin,vendedor,panadero,cliente',
            // allow admin to mark email as verified on creation
            'mark_verified' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Choose password: provided by admin or generate securely
        $rawPassword = $request->filled('password') ? $request->password : Str::random(12);

        $usuarioData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($rawPassword),
            'role' => $request->role,
        ];

        if ($request->boolean('mark_verified', false)) {
            $usuarioData['email_verified_at'] = now();
        }

        // Create the user; observers are allowed here because this is the central user-management endpoint
        $usuario = User::create($usuarioData);

        // Ensure pivot role is set to match the requested role
        if ($request->filled('role')) {
            $roleModel = Role::where('name', $request->role)->first();
            if ($roleModel) {
                $usuario->roles()->sync([$roleModel->id]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => $usuario,
            // Return generated password only when admin did not provide one (for admin display/copy).
            // Note: in a real production system you would send this securely (email) or force password reset.
            'generated_password' => !$request->filled('password') ? $rawPassword : null
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
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:6',
            // Make role optional on partial updates: frontend may call update without sending role
            'role' => 'sometimes|in:admin,vendedor,panadero,cliente'
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
            // Sync pivot table so both sources-of-truth stay consistent
            $roleModel = Role::where('name', $request->role)->first();
            if ($roleModel) {
                $usuario->roles()->sync([$roleModel->id]);
            }
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
    if ($usuario->getKey() === Auth::id()) {
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
                        // Count using pivot when available, fallback to users.role column
                        'admins' => User::where(function ($q) {
                                $q->whereHas('roles', function ($r) { $r->where('name', 'admin'); })
                                    ->orWhere('role', 'admin');
                        })->count(),
                        'vendedores' => User::where(function ($q) {
                                $q->whereHas('roles', function ($r) { $r->where('name', 'vendedor'); })
                                    ->orWhere('role', 'vendedor');
                        })->count(),
                        'panaderos' => User::where(function ($q) {
                                $q->whereHas('roles', function ($r) { $r->where('name', 'panadero'); })
                                    ->orWhere('role', 'panadero');
                        })->count(),
                        'clientes' => User::where(function ($q) {
                                $q->whereHas('roles', function ($r) { $r->where('name', 'cliente'); })
                                    ->orWhere('role', 'cliente');
                        })->count(),
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
        // Listar usuarios que aún no tienen asignado un registro en la tabla `vendedores`
        // (disponibles para ser creados como vendedores). No filtramos por columna `role` aquí;
        // la relación `vendedor` determina la disponibilidad.
        $usuarios = User::whereDoesntHave('vendedor')->get();

        return response()->json([
            'success' => true,
            'data' => $usuarios
        ]);
    }
}
