<?php

namespace App\Observers;

use App\Models\Producto;
use App\Models\InventarioProductoFinal;
use Illuminate\Support\Facades\Log;

class ProductoObserver
{
    /**
     * Handle the Producto "created" event.
     * Cuando se crea un producto activo, crear su fila de inventario.
     */
    public function created(Producto $producto): void
    {
        if ($producto->esta_activo) {
            $this->ensureInventarioExists($producto);
        }
    }

    /**
     * Handle the Producto "updated" event.
     * Si el producto pasa a activo, asegurar que tenga inventario.
     */
    public function updated(Producto $producto): void
    {
        if ($producto->esta_activo && $producto->wasChanged('esta_activo')) {
            $this->ensureInventarioExists($producto);
        }
    }

    /**
     * Asegura que exista una fila de inventario para el producto.
     */
    private function ensureInventarioExists(Producto $producto): void
    {
        try {
            InventarioProductoFinal::firstOrCreate(
                ['producto_id' => $producto->id],
                [
                    'stock_actual' => 0,
                    'stock_minimo' => 0,
                    'costo_promedio' => 0,
                ]
            );
        } catch (\Exception $e) {
            Log::warning("No se pudo crear inventario para producto {$producto->id}: " . $e->getMessage());
        }
    }
}
