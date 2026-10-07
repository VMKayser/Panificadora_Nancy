<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Falta stock para completar una operación. $detalle lista qué falta
 * (productos de un pedido o ingredientes de una producción) para devolverlo
 * al frontend con un 422.
 */
class StockInsuficienteException extends RuntimeException
{
    public function __construct(string $message, public readonly array $detalle = [])
    {
        parent::__construct($message);
    }
}
