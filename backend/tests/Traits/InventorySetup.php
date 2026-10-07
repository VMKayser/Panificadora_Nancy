<?php

namespace Tests\Traits;

use App\Models\InventarioProductoFinal;

trait InventorySetup
{
    /**
     * Ensure an InventarioProductoFinal row exists for the product with given stock.
     * @param int $productoId
     * @param float $stock
     * @return void
     */
    protected function ensureInventory(int $productoId, float $stock = 10.0): void
    {
        InventarioProductoFinal::query()->updateOrInsert([
            'producto_id' => $productoId
        ], [
            'stock_actual' => $stock,
            'costo_promedio' => 1
        ]);
    }
}
