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
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;
use App\Support\PasswordPolicy;
use App\Support\SecurityLog;

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
            'password' => array_merge(['required', 'confirmed'], PasswordPolicy::rules()),
            'phone' => 'nullable|string|max:20',
            'nit_ci' => 'nullable|string|max:20',
        ], PasswordPolicy::messages());

        try {
            $user = DB::transaction(function () use ($validated) {
                // Crear usuario
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'phone' => $validated['phone'] ?? null,
                    'nit_ci' => $validated['nit_ci'] ?? null,
                    'is_active' => true,
                ]);

                // Asignar rol de cliente por defecto
                $clienteRole = Role::where('name', 'cliente')->first();
                if ($clienteRole) {
                    $user->roles()->attach($clienteRole->id);
                }

                // Crear registro en tabla clientes (solo si no existe)
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
                        'nit_ci' => $validated['nit_ci'] ?? null,
                        'tipo_cliente' => 'regular', // Por defecto
                        'activo' => true,
                    ]);
                }

                return $user;
            });

            // Disparar notificación de verificación de email (Registered event)
            try {
                event(new Registered($user));
            } catch (\Throwable $e) {
                // Registrar el error pero no abortar el registro - el usuario ya fue creado
                Log::warning('Error al disparar evento Registered: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Usuario registrado exitosamente. Se ha enviado un correo de verificación si el servidor de mail está configurado.',
                'user' => $user->load('roles'),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al registrar usuario: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Error al registrar usuario',
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
            SecurityLog::loginFallido($request, (string) $request->email, $user ? 'password_incorrecto' : 'usuario_inexistente');
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (!$user->is_active) {
            SecurityLog::loginFallido($request, (string) $request->email, 'cuenta_inactiva');
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta está inactiva. Contacta al administrador.'],
            ]);
        }

        // Requerir verificación de email solo para clientes (otros roles pueden acceder aunque el correo no esté verificado)
        if (method_exists($user, 'hasVerifiedEmail') && !$user->hasVerifiedEmail()) {
            $isPrivilegedRole = method_exists($user, 'hasAnyRole')
                ? $user->hasAnyRole(['admin', 'panadero', 'vendedor'])
                : false;

            if (!$isPrivilegedRole) {
                throw ValidationException::withMessages([
                    'email' => ['Por favor verifica tu correo antes de iniciar sesión.'],
                ]);
            }
        }

        // Eliminar tokens anteriores
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;
        SecurityLog::loginExitoso($request, $user);

        return response()->json([
            'message' => 'Login exitoso',
            'user' => $user->load(['roles', 'cliente', 'panadero', 'vendedor']),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        // Safe-delete current access token. In some test contexts currentAccessToken()
        // may return null (no token in the request). Guard against that to avoid
        // "Call to a member function delete() on null" errors.
        $user = $request->user();
        if ($user) {
            if (method_exists($user, 'currentAccessToken')) {
                $token = $user->currentAccessToken();
                if ($token) {
                    $token->delete();
                } else {
                    // Fallback: delete all tokens for the user if current token not available
                    if (method_exists($user, 'tokens')) {
                        $user->tokens()->delete();
                    }
                }
            } else {
                // As a last resort, try tokens() if available
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            }
        }

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
            'user' => $request->user()->load(['roles', 'cliente', 'panadero', 'vendedor'])
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
            'nit_ci' => 'sometimes|nullable|string|max:20',
            'current_password' => 'sometimes|required_with:new_password',
            'new_password' => array_merge(['sometimes', 'confirmed'], PasswordPolicy::rules()),
        ], PasswordPolicy::messages('new_password'));

        // Si se quiere cambiar la contraseña
        if (isset($validated['new_password'])) {
            if (!Hash::check($validated['current_password'] ?? '', $user->password)) {
                SecurityLog::accesoDenegado($request, 'cambio_password', ['motivo' => 'password_actual_incorrecto']);
                throw ValidationException::withMessages([
                    'current_password' => ['La contraseña actual es incorrecta.'],
                ]);
            }
            $user->password = Hash::make($validated['new_password']);
            $passwordCambiada = true;
        }

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
        }

        if (isset($validated['nit_ci'])) {
            $user->nit_ci = $validated['nit_ci'];
            // También actualizar el cliente asociado si existe
            if ($user->cliente) {
                $user->cliente->nit_ci = $validated['nit_ci'];
                $user->cliente->save();
            }
        }

        $user->save();

        if (!empty($passwordCambiada)) {
            // Cerrar las demás sesiones: un token robado deja de servir al cambiar la clave.
            $actual = $user->currentAccessToken();
            $user->tokens()->when($actual && isset($actual->id), fn ($q) => $q->where('id', '!=', $actual->id))->delete();
            SecurityLog::accionSensible($request, 'password_cambiada', ['user_id' => $user->id]);
        }

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Solicitar enlace de restablecimiento. Responde lo mismo exista o no la cuenta.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255']);

        $estado = Password::sendResetLink(['email' => $request->email]);
        SecurityLog::accionSensible($request, 'password_reset_solicitado', [
            'email_hash' => hash('sha256', mb_strtolower(trim($request->email))),
            'resultado' => $estado,
        ]);

        return response()->json([
            'message' => 'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña.',
        ]);
    }

    /**
     * Restablecer la contraseña con el token de un solo uso; cierra todas las sesiones.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|max:255',
            'password' => array_merge(['required', 'confirmed'], PasswordPolicy::rules()),
        ], PasswordPolicy::messages());

        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            SecurityLog::accesoDenegado($request, 'password_reset', ['motivo' => $estado]);
            return response()->json(['message' => 'El enlace no es válido o ya expiró. Solicita uno nuevo.'], 422);
        }

        SecurityLog::accionSensible($request, 'password_restablecida');
        return response()->json(['message' => 'Contraseña actualizada. Ya puedes iniciar sesión.']);
    }
}
