<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Cliente;
use App\Mail\BienvenidaUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
            ],
            'phone' => 'nullable|string|max:20',
        ], [
            'password.regex' => 'La contraseña debe contener al menos una mayúscula, una minúscula y un número.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        try {
            DB::beginTransaction();

            // Crear usuario
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
            ]);

            // Asignar rol de cliente por defecto
            $clienteRole = Role::where('name', 'cliente')->first();
            if ($clienteRole) {
                $user->roles()->attach($clienteRole->id);
            }

            // Crear registro en tabla clientes (solo si no existe)
            // Separar nombre y apellido (simple: primera palabra = nombre, resto = apellido)
            $clienteExistente = Cliente::where('email', $validated['email'])->first();
            
            if (!$clienteExistente) {
                $nombreCompleto = explode(' ', $validated['name'], 2);
                $nombre = $nombreCompleto[0];
                $apellido = $nombreCompleto[1] ?? '';

                Cliente::create([
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'email' => $validated['email'],
                    'telefono' => $validated['phone'] ?? null,
                    'tipo_cliente' => 'regular', // Por defecto
                    'activo' => true,
                ]);
            }

            // Enviar email de bienvenida
            try {
                Mail::to($user->email)->send(new BienvenidaUsuario($user));
            } catch (\Exception $e) {
                // No detener el registro si falla el envío en desarrollo
                Log::error('Error enviando correo de bienvenida: ' . $e->getMessage());
            }

            DB::commit();

            return response()->json([
                'message' => 'Usuario registrado exitosamente. Revisa tu correo para verificar la cuenta.',
                'user' => $user->load('roles'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta está inactiva. Contacta al administrador.'],
            ]);
        }

        // Requerir verificación de email
        if (method_exists($user, 'hasVerifiedEmail') && !$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Por favor verifica tu correo antes de iniciar sesión.'],
            ]);
        }

        // Eliminar tokens anteriores
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'user' => $user->load('roles'),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout exitoso'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('roles')
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'current_password' => 'sometimes|required_with:new_password',
            'new_password' => 'sometimes|string|min:6|confirmed',
        ]);

        // Si se quiere cambiar la contraseña
        if (isset($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['La contraseña actual es incorrecta.'],
                ]);
            }
            $user->password = Hash::make($validated['new_password']);
        }

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
        }

        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'user' => $user->load('roles')
        ]);
    }
}
