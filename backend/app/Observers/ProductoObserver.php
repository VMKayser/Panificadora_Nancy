<?php

namespace App\Observers;

use App\Models\Producto;
use App\Models\InventarioProductoFinal;
use Illuminate\Support\Facades\Log;

class ProductoObserver
{
    /**
     * Handle the Producto "deleting" event (soft delete).
     */
    public function deleting(Producto $producto): void
    {
        try {
            // Mark as inactive to keep both fields consistent
            $producto->esta_activo = false;
            // Use saveQuietly to avoid triggering further model events
            $producto->saveQuietly();
        } catch (\Throwable $e) {
            // swallow: observer should not break the delete flow
            Log::warning('ProductoObserver deleting failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Producto "restored" event.
     */
    public function restored(Producto $producto): void
    {
        try {
            $producto->esta_activo = true;
            $producto->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('ProductoObserver restored failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Producto "created" event.
     * When a product is created and active, ensure an inventory row exists.
     */
    public function created(Producto $producto): void
    {
        if ($producto->esta_activo) {
            $this->ensureInventarioExists($producto);
        }
    }

    /**
     * Handle the Producto "updated" event.
     * If the product becomes active, ensure it has an inventory row.
     */
    public function updated(Producto $producto): void
    {
        if ($producto->esta_activo && $producto->wasChanged('esta_activo')) {
            $this->ensureInventarioExists($producto);
        }
    }

    /**
     * Ensure an InventarioProductoFinal row exists for the product.
     */
    private function ensureInventarioExists(Producto $producto): void
    {
        try {
            // Use updateOrInsert on the query builder to avoid creating nested
            // transactions/savepoints inside model observers (firstOrCreate
            // may wrap in a transaction). This reduces the chance of Laravel's
            // internal transaction counter getting out-of-sync with PDO
            // when observers run during factory/test setup.
            InventarioProductoFinal::query()->updateOrInsert(
                ['producto_id' => $producto->id],
                [
                    'stock_actual' => 0,
                    'stock_minimo' => 0,
                    'costo_promedio' => 0,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning("No se pudo crear inventario para producto {$producto->id}: " . $e->getMessage());
        }
    }
}

