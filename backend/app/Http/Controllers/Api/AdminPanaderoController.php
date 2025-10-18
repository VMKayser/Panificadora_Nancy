<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Panadero;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

class AdminPanaderoController extends Controller
{
    public function index(Request $request)
    {
    // Include salario_por_kilo and observaciones so the frontend list and edit forms can show them
    $query = Panadero::with('user')->select(['id','user_id','codigo_panadero','direccion','fecha_ingreso','turno','especialidad','salario_base','salario_por_kilo','observaciones','activo','created_at']);
        if ($request->has('activo')) {
            $query->where('activo', (bool) $request->activo);
        }
        // Allow client to request per_page but enforce a max to avoid heavy queries
        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $shouldCache = $request->get('page',1) == 1 && !$request->has('activo');
        if ($shouldCache) {
            $cacheKey = "panaderos.index.page.1.per.{$perPage}";
            $result = Cache::remember($cacheKey, 30, function() use ($query, $perPage) {
                return $query->paginate($perPage);
            });
            return response()->json($result);
        }

        return response()->json($query->paginate($perPage));
    }

    public function show($id)
     {
        $panadero = Panadero::with('user')->findOrFail($id);
        return response()->json($panadero);
    }

    public function store(Request $request)
    {
        // Primero validar campos básicos sin email (lo validamos después)
        // Build validation rules for `ci` depending on schema state
        $ciRule = ['required','string'];
        if (Schema::hasColumn('panaderos', 'ci')) {
            // legacy column exists: validate uniqueness there
            $ciRule[] = Rule::unique('panaderos','ci');
        } elseif (Schema::hasColumn('users', 'ci')) {
            // normalized column on users: validate uniqueness on users
            $ciRule[] = Rule::unique('users','ci');
        } else {
            // no ci column anywhere; keep as optional string
            $ciRule = ['sometimes','string','max:50'];
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email', // No unique: puede existir si estamos convirtiendo un user existente
            'password' => 'sometimes|nullable|string|min:6',
            'mark_verified' => 'sometimes|boolean',
            'telefono' => 'required|string|max:20',
            'ci' => $ciRule,
            'fecha_ingreso' => 'required|date',
            'turno' => ['required', Rule::in(['mañana','tarde','noche','rotativo'])],
            'especialidad' => ['required', Rule::in(['pan','reposteria','ambos'])],
            'salario_base' => 'required|numeric',
            'salario_por_kilo' => 'sometimes|numeric',
            'observaciones' => 'sometimes|nullable|string|max:2000',
        ]);

        // Buscar o crear el User
        $user = User::firstWhere('email', $validated['email']);
        $generatedPassword = null;
        
        if (! $user) {
            // Nuevo usuario: crear sin observers
            $generatedPassword = $validated['password'] ?? Str::random(12);
            $user = User::withoutEvents(function () use ($validated, $generatedPassword) {
                $data = [
                    'name' => $validated['nombre'] . ' ' . $validated['apellido'],
                    'email' => $validated['email'],
                    'password' => Hash::make($generatedPassword),
                    'role' => 'panadero',
                    'is_active' => 1,
                ];
                if (isset($validated['mark_verified']) && $validated['mark_verified']) {
                    $data['email_verified_at'] = now();
                }
                return User::create($data);
            });
            // If telefono/ci present in validated payload, copy them to user
            if (!empty($validated['telefono'])) {
                $user->phone = $validated['telefono'];
            }
            if (!empty($validated['ci'])) {
                // add ci to users table if exists
                if (Schema::hasColumn('users', 'ci')) {
                    $user->ci = $validated['ci'];
                }
            }
            $user->saveQuietly();
        } else {
            // Usuario existente: verificar que no tenga ya un panadero
            if ($user->panadero) {
                return response()->json([
                    'message' => 'Este usuario ya tiene un perfil de panadero asociado',
                    'data' => $user->panadero
                ], 409); // 409 Conflict
            }
            
            // Actualizar rol si es necesario
            if ($user->role !== 'panadero') {
                $user->role = 'panadero';
                $user->saveQuietly(); // Evitar observers
                // Sync pivot
                $roleModel = Role::where('name', 'panadero')->first();
                if ($roleModel) {
                    $user->roles()->sync([$roleModel->id]);
                }
            }
        }

        $panaderoData = $validated;
        // Remove campos que no existen en la tabla panaderos (van en users)
        unset(
            $panaderoData['email'], 
            $panaderoData['password'], 
            $panaderoData['mark_verified'],
            $panaderoData['nombre'],
            $panaderoData['apellido']
        );
        // telefono and ci are stored on users now
        unset($panaderoData['telefono'], $panaderoData['ci']);
        $panaderoData['user_id'] = $user->id;

        $panadero = Panadero::create($panaderoData);
        // Invalidate panaderos list caches
        foreach ([20,50,100] as $pp) {
            Cache::forget("panaderos.index.page.1.per.{$pp}");
        }

        $resp = ['data' => $panadero, 'message' => 'Panadero creado exitosamente'];
        if ($generatedPassword) $resp['generated_password'] = $generatedPassword;
        return response()->json($resp, 201);
    }

    public function update(Request $request, $id)
    {
        $panadero = Panadero::findOrFail($id);

        // prepare email uniqueness rule against users table, ignoring current user if linked
        $userIdForEmail = $panadero->user?->id ?? null;

        $emailRule = ['sometimes','email'];
        if ($userIdForEmail) {
            $emailRule[] = Rule::unique('users','email')->ignore($userIdForEmail);
        } else {
            $emailRule[] = Rule::unique('users','email');
        }

        // Build ci rule for update: unique should ignore current record when appropriate
        $ciUpdateRule = ['sometimes','string'];
        if (Schema::hasColumn('panaderos', 'ci')) {
            $ciUpdateRule[] = Rule::unique('panaderos','ci')->ignore($panadero->id);
        } elseif (Schema::hasColumn('users', 'ci')) {
            // if ci is stored on users table, uniqueness should ignore the current linked user id
            $currentUserId = $panadero->user?->id ?? null;
            if ($currentUserId) {
                $ciUpdateRule[] = Rule::unique('users','ci')->ignore($currentUserId);
            } else {
                $ciUpdateRule[] = Rule::unique('users','ci');
            }
        } else {
            $ciUpdateRule = ['sometimes','string','max:50'];
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'email' => $emailRule,
            'telefono' => 'sometimes|string|max:20',
            'ci' => $ciUpdateRule,
            'fecha_ingreso' => 'sometimes|date',
            'turno' => [Rule::in(['mañana','tarde','noche','rotativo'])],
            'especialidad' => [Rule::in(['pan','reposteria','ambos'])],
            'salario_base' => 'sometimes|numeric',
            'salario_por_kilo' => 'sometimes|numeric',
            'observaciones' => 'sometimes|nullable|string|max:2000',
        ]);

        // If email provided, sync to linked User (or create one) and remove from panaderos update payload
        if (isset($validated['email'])) {
            if ($panadero->user) {
                $panadero->user->email = $validated['email'];
                // Also sync phone/ci if provided
                if (isset($validated['telefono'])) {
                    $panadero->user->phone = $validated['telefono'];
                }
                if (isset($validated['ci']) && Schema::hasColumn('users', 'ci')) {
                    $panadero->user->ci = $validated['ci'];
                }
                $panadero->user->save();
            } else {
                $user = User::firstWhere('email', $validated['email']);
                if (! $user) {
                        $user = User::withoutEvents(function () use ($validated, $panadero) {
                            return User::create([
                                'name' => ($validated['nombre'] ?? $panadero->nombre) . ' ' . ($validated['apellido'] ?? $panadero->apellido),
                                'email' => $validated['email'],
                                'password' => Hash::make(Str::random(12)),
                                'role' => 'panadero',
                                'is_active' => 1,
                            ]);
                        });
                }
                // Ensure phone/ci are also moved to user
                if (isset($validated['telefono'])) {
                    $user->phone = $validated['telefono'];
                }
                if (isset($validated['ci']) && Schema::hasColumn('users', 'ci')) {
                    $user->ci = $validated['ci'];
                }
                $user->saveQuietly();
                $validated['user_id'] = $user->id;
            }
            unset($validated['email']);
        }

        // If telefono/ci sent for update (even without email), sync to linked user
        if (isset($validated['telefono']) || isset($validated['ci'])) {
            if ($panadero->user) {
                if (isset($validated['telefono'])) $panadero->user->phone = $validated['telefono'];
                if (isset($validated['ci']) && Schema::hasColumn('users', 'ci')) $panadero->user->ci = $validated['ci'];
                $panadero->user->saveQuietly();
            }
            // remove from $validated so panaderos table won't receive these fields
            unset($validated['telefono'], $validated['ci']);
        }

        $panadero->update($validated);
        // Invalidate caches
        foreach ([20,50,100] as $pp) {
            Cache::forget("panaderos.index.page.1.per.{$pp}");
        }

        return response()->json(['data' => $panadero]);
    }

    public function destroy($id)
    {
        $panadero = Panadero::findOrFail($id);
        $panadero->delete();
        foreach ([20,50,100] as $pp) {
            Cache::forget("panaderos.index.page.1.per.{$pp}");
        }
        return response()->json(['message' => 'Panadero eliminado']);
    }
}
