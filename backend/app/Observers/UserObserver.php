<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Vendedor;
use App\Models\Panadero;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use App\Support\SafeTransaction;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * Auto-crear registros en tablas específicas según el rol
     */
    public function created(User $user): void
    {
        // No crear filas relacionadas automáticamente al crear un usuario.
        // Mantener solo la asignación de rol por defecto si no viene especificado.
        // La creación de perfiles (panadero/vendedor/cliente) debe realizarse explícitamente desde la UI
        // para asegurar que se recolecten todos los datos obligatorios.
        if (empty($user->role)) {
            $user->role = 'cliente';
            $user->saveQuietly();
        }
    }

    /**
     * Handle the User "updated" event.
     * IMPORTANT: No crear automáticamente filas al cambiar rol.
     * El flujo debe ser: admin cambia rol → frontend pide datos extra → frontend crea fila explícitamente.
     * Esto evita inserciones parciales y conflictos de validación.
     */
    public function updated(User $user): void
    {
        // Cuando cambian campos básicos (nombre, email, phone), propagar a tablas relacionadas
        if ($user->wasChanged(['name', 'email', 'phone'])) {
            // Separar nombre y apellido desde name
            $fullName = trim($user->name ?? '');
            $nombre = $fullName;
            $apellido = '';
            if ($fullName !== '') {
                $parts = preg_split('/\s+/', $fullName);
                $nombre = $parts[0] ?? $fullName;
                if (count($parts) > 1) {
                    $apellido = implode(' ', array_slice($parts, 1));
                }
            }

            // Actualizar cliente vinculado si existe
            try {
                if ($user->cliente) {
                    $cliente = $user->cliente;
                    $cliente->nombre = $nombre;
                    $cliente->apellido = $apellido;
                    $cliente->email = $user->email ?? $cliente->email;
                    $cliente->telefono = $user->phone ?? $cliente->telefono;
                    $cliente->saveQuietly();
                }

                // Actualizar panadero si existe
                if ($user->panadero) {
                    $pan = $user->panadero;
                    // Solo actualizar campos existentes para no sobrescribir campos administrativos
                    $pan->nombre = $nombre;
                    $pan->apellido = $apellido;
                    $pan->telefono = $user->phone ?? $pan->telefono;
                    $pan->saveQuietly();
                }

                // Actualizar vendedor si existe
                if ($user->vendedor) {
                    $vend = $user->vendedor;
                    $vend->telefono = $user->phone ?? $vend->telefono;
                    $vend->saveQuietly();
                }
            } catch (\Throwable $e) {
                // Log pero no romper la transacción del observer
                // avoid importing logger here; let framework handle exceptions
            }
        }

        // Si cambió el rol, crear filas faltantes para el nuevo rol y desactivar filas del rol anterior
        if ($user->wasChanged('role')) {
            $oldRole = $user->getOriginal('role');
            $newRole = $user->role;

            // Crear filas necesarias para el nuevo rol
            $this->asignarTablaSegunRol($user);

            // Si el usuario dejó de ser vendedor/panadero/cliente, marcar como inactivo la fila correspondiente
            try {
                if ($oldRole === 'vendedor' && $newRole !== 'vendedor' && $user->vendedor) {
                    $user->vendedor->activo = false;
                    $user->vendedor->saveQuietly();
                }
                if ($oldRole === 'panadero' && $newRole !== 'panadero' && $user->panadero) {
                    $user->panadero->activo = false;
                    $user->panadero->saveQuietly();
                }
                if ($oldRole === 'cliente' && $newRole !== 'cliente' && $user->cliente) {
                    $user->cliente->activo = false;
                    $user->cliente->saveQuietly();
                }
            } catch (\Throwable $e) {
                // swallow errors to keep update flow stable
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     * Soft-delete related domain records to keep data consistent.
     */
    public function deleted(User $user): void
    {
        try {
            if ($user->vendedor) {
                $user->vendedor->delete();
            }
            if ($user->panadero) {
                $user->panadero->delete();
            }
            if ($user->cliente) {
                $user->cliente->delete();
            }
        } catch (\Throwable $e) {
            // ignore to avoid cascading failures
        }
    }

    /**
     * Asignar usuario a su tabla específica según rol
     */
    protected function asignarTablaSegunRol(User $user): void
    {
    SafeTransaction::run(function () use ($user) {
            switch ($user->role) {
                case 'vendedor':
                    // Crear registro en tabla vendedores si no existe
                    if (!$user->vendedor) {
                        Vendedor::create([
                            'user_id' => $user->id,
                            'codigo_vendedor' => Vendedor::generarCodigoVendedor(),
                            'comision_porcentaje' => 2.5, // Comisión por defecto
                            'descuento_maximo_bs' => 50, // Máximo descuento: 50 Bs.
                            'puede_dar_descuentos' => true,
                            'puede_cancelar_ventas' => false,
                            'fecha_ingreso' => now(),
                            'estado' => 'activo',
                        ]);
                    }
                    break;

                case 'panadero':
                    // Crear registro en tabla panaderos si no existe
                    if (!$user->panadero) {
                        // Use safe defaults for required fields (telefono, ci) to avoid DB errors
                        $defaultTelefono = '';
                        $defaultCi = 'AUTO-CI-' . $user->id;

                        Panadero::create([
                            'user_id' => $user->id,
                            'codigo_panadero' => Panadero::generarCodigoPanadero(),
                            'nombre' => $user->name ? explode(' ', trim($user->name))[0] : 'SinNombre',
                            'apellido' => $user->name ? (explode(' ', trim($user->name), 2)[1] ?? '') : '',
                            'telefono' => $defaultTelefono,
                            'ci' => $defaultCi,
                            'direccion' => null,
                            'especialidad' => 'ambos', // Debe ser: pan, reposteria, o ambos
                            'turno' => 'mañana',
                            'fecha_ingreso' => now(),
                            'activo' => true,
                            'salario_base' => 3000.00, // Salario base por defecto
                        ]);
                    }
                    break;

                case 'cliente':
                    // Crear registro en tabla clientes si no existe
                    if (!$user->cliente) {
                        // Si el nombre completo está en user->name, intentar separar nombre y apellido
                        $fullName = trim($user->name ?? '');
                        $nombre = $fullName;
                        $apellido = '';
                        if ($fullName !== '') {
                            $parts = preg_split('/\s+/', $fullName);
                            $nombre = $parts[0] ?? $fullName;
                            if (count($parts) > 1) {
                                $apellido = implode(' ', array_slice($parts, 1));
                            }
                        }

                        // Use updateOrInsert on the query builder to avoid nested
                        // transactions inside model observers which can create
                        // savepoints and lead to desynchronization with PDO.
                        Cliente::query()->updateOrInsert(
                            ['user_id' => $user->id],
                            [
                                'nombre' => $nombre,
                                'apellido' => $apellido,
                                'email' => $user->email ?? null,
                                'telefono' => $user->phone ?? '',
                                'activo' => true,
                            ]
                        );
                    }
                    break;

                // Podríamos agregar más roles aquí en el futuro
                case 'admin':
                case 'cliente':
                    // No requieren tablas adicionales
                    break;
            }
        });
    }
}
