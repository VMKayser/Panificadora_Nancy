<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Vendedor;
use App\Models\Panadero;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * Auto-crear registros en tablas específicas según el rol
     */
    public function created(User $user): void
    {
        // Si no tiene role, asignar 'cliente' por defecto y guardar silenciosamente
        if (empty($user->role)) {
            $user->role = 'cliente';
            // saveQuietly evita re-disparar observers en algunos casos
            $user->saveQuietly();
        }

        $this->asignarTablaSegunRol($user);
    }

    /**
     * Handle the User "updated" event.
     * Si se cambió el rol, actualizar las tablas correspondientes
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('role')) {
            $this->asignarTablaSegunRol($user);
        }
    }

    /**
     * Asignar usuario a su tabla específica según rol
     */
    protected function asignarTablaSegunRol(User $user): void
    {
        DB::transaction(function () use ($user) {
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
                        Panadero::create([
                            'user_id' => $user->id,
                            'codigo_panadero' => Panadero::generarCodigoPanadero(),
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
                        Cliente::firstOrCreate(
                            ['user_id' => $user->id],
                            [
                                'nombre' => $user->name ?? null,
                                'email' => $user->email ?? null,
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
