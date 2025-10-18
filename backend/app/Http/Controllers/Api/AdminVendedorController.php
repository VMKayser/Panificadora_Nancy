<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\Role;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;

class AdminVendedorController extends Controller
{
    public function index(Request $request)
    {
        // Avoid selecting explicit columns to prevent 'unknown column' issues
        // across environments with diverging schemas. Selecting all columns
        // (Eloquent default) is more robust here; individual admin list UIs
        // can still pick needed fields on the client side.
        $query = Vendedor::with('user');
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $shouldCache = $request->get('page',1) == 1 && !$request->has('search');
        // Execute with a runtime safeguard: if something goes wrong at the DB
        // level, catch it and either retry with a safer query or rethrow.
        try {
            if ($shouldCache) {
                $cacheKey = "vendedores.index.page.1.per.{$perPage}";
                $result = Cache::remember($cacheKey, 30, function() use ($query, $perPage) {
                    return $query->paginate($perPage);
                });
                return response()->json($result);
            }

            return response()->json($query->paginate($perPage));
        } catch (QueryException $ex) {
            $msg = $ex->getMessage();
            // As a fallback, retry with a plain query (no selects) which is
            // more likely to succeed if some columns are missing.
            try {
                $query = Vendedor::with('user');
                if ($shouldCache) {
                    $cacheKey = "vendedores.index.page.1.per.{$perPage}";
                    $result = Cache::remember($cacheKey, 30, function() use ($query, $perPage) {
                        return $query->paginate($perPage);
                    });
                    return response()->json($result);
                }
                return response()->json($query->paginate($perPage));
            } catch (QueryException $ex2) {
                // If it still fails, log and rethrow the original exception for
                // visibility; the admin UI will surface an error.
                throw $ex;
            }
        }
    }

    public function show($id)
    {
        $vendedor = Vendedor::with('user')->findOrFail($id);
        return response()->json($vendedor);
    }

    public function store(Request $request)
    {
        // Build ci rule depending on schema
        $ciRule = ['sometimes','nullable','string','max:20'];
        if (Schema::hasColumn('vendedores', 'ci')) {
            $ciRule = ['sometimes','nullable','string','max:20', Rule::unique('vendedores','ci')];
        } elseif (Schema::hasColumn('users', 'ci')) {
            $ciRule = ['sometimes','nullable','string','max:50', Rule::unique('users','ci')];
        }

        // Validar campos básicos
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email',
            'password' => 'sometimes|nullable|string|min:6',
            'mark_verified' => 'sometimes|boolean',
            'telefono' => 'sometimes|nullable|string|max:20',
            'direccion' => 'sometimes|nullable|string',
            'fecha_ingreso' => 'required|date',
            'turno' => ['required', Rule::in(['mañana','tarde','noche','rotativo'])],
            'comision_porcentaje' => 'required|numeric|min:0|max:100',
            'descuento_maximo_bs' => 'sometimes|numeric|min:0',
            'salario_base' => 'sometimes|numeric|min:0',
            'observaciones' => 'sometimes|nullable|string',
            'ci' => $ciRule,
        ]);

        // Buscar o crear el User
        $user = User::firstWhere('email', $validated['email']);
        $generatedPassword = null;
        
        if (!$user) {
            // Nuevo usuario: crear sin observers
            $generatedPassword = $validated['password'] ?? Str::random(12);
            $user = User::withoutEvents(function () use ($validated, $generatedPassword) {
                $data = [
                    'name' => $validated['nombre'] . ' ' . $validated['apellido'],
                    'email' => $validated['email'],
                    'password' => Hash::make($generatedPassword),
                    'role' => 'vendedor',
                    'is_active' => 1,
                ];
                if (isset($validated['mark_verified']) && $validated['mark_verified']) {
                    $data['email_verified_at'] = now();
                }
                return User::create($data);
            });
            // copy telefono/ci into users if provided
            if (!empty($validated['telefono'])) {
                $user->phone = $validated['telefono'];
            }
            if (!empty($validated['ci']) && Schema::hasColumn('users', 'ci')) {
                $user->ci = $validated['ci'];
            }
            $user->saveQuietly();
        } else {
            // Usuario existente: verificar que no tenga ya un vendedor
            if ($user->vendedor) {
                return response()->json([
                    'message' => 'Este usuario ya tiene un perfil de vendedor asociado',
                    'data' => $user->vendedor
                ], 409); // 409 Conflict
            }
            
            // Actualizar rol si es necesario
            if ($user->role !== 'vendedor') {
                $user->role = 'vendedor';
                $user->saveQuietly(); // Evitar observers
                // Sync pivot
                $roleModel = Role::where('name', 'vendedor')->first();
                if ($roleModel) {
                    $user->roles()->sync([$roleModel->id]);
                }
            }
        }

        $vendedorData = $validated;
        // Remove campos que no existen en la tabla vendedores (van en users)
        unset(
            $vendedorData['email'], 
            $vendedorData['password'], 
            $vendedorData['mark_verified'],
            $vendedorData['nombre'],
            $vendedorData['apellido'],
            $vendedorData['telefono']
        );
        // If the vendedores table does not have salario_base, don't attempt to write it
        if (! Schema::hasColumn('vendedores', 'salario_base')) {
            unset($vendedorData['salario_base']);
        }
        $vendedorData['user_id'] = $user->id;
        
        // Generar código único de vendedor si no se proporciona
        if (!isset($vendedorData['codigo_vendedor']) || empty($vendedorData['codigo_vendedor'])) {
            $vendedorData['codigo_vendedor'] = 'VEN-' . date('Y') . '-' . str_pad(Vendedor::count() + 1, 4, '0', STR_PAD_LEFT);
        }

        $vendedor = Vendedor::create($vendedorData);
        foreach ([20,50,100] as $pp) {
            Cache::forget("vendedores.index.page.1.per.{$pp}");
        }
        $resp = ['data' => $vendedor, 'message' => 'Vendedor creado exitosamente'];
        if ($generatedPassword) $resp['generated_password'] = $generatedPassword;
        return response()->json($resp, 201);
    }

    public function update(Request $request, $id)
    {
        $vendedor = Vendedor::findOrFail($id);
        // Build ci update rule
        $ciUpdateRule = ['sometimes','nullable','string','max:20'];
        if (Schema::hasColumn('vendedores', 'ci')) {
            $ciUpdateRule = ['sometimes','nullable','string','max:20', Rule::unique('vendedores','ci')->ignore($vendedor->id)];
        } elseif (Schema::hasColumn('users', 'ci')) {
            $ciUpdateRule = ['sometimes','nullable','string','max:50', Rule::unique('users','ci')];
        }

        $validated = $request->validate([
            'comision_porcentaje' => 'sometimes|numeric|min:0|max:100',
            'descuento_maximo_bs' => 'sometimes|numeric|min:0',
            'puede_dar_descuentos' => 'sometimes|boolean',
            'puede_cancelar_ventas' => 'sometimes|boolean',
            'turno' => ['sometimes', Rule::in(['mañana','tarde','noche','rotativo'])],
            'fecha_ingreso' => 'sometimes|date',
            'estado' => ['sometimes', Rule::in(['activo','inactivo','suspendido'])],
            'observaciones' => 'sometimes|nullable|string',
            'ci' => $ciUpdateRule,
            // avoid validating salario_base for update if the column is missing
            // (validation will allow it but we will not write it below)
        ]);

        $vendedor->update($validated);

        foreach ([20,50,100] as $pp) {
            Cache::forget("vendedores.index.page.1.per.{$pp}");
        }

        return response()->json([
            'message' => 'Vendedor actualizado exitosamente',
            'data' => $vendedor->fresh()
        ]);
    }

    public function destroy($id)
    {
        $vendedor = Vendedor::findOrFail($id);
        $vendedor->delete();

        foreach ([20,50,100] as $pp) {
            Cache::forget("vendedores.index.page.1.per.{$pp}");
        }

        return response()->json([
            'message' => 'Vendedor eliminado exitosamente'
        ]);
    }
}
