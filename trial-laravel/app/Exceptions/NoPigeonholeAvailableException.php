<?php

namespace App\Exceptions;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Thrown when an order needs a pigeonhole but every one is holding another order.
 */
class NoPigeonholeAvailableException extends RuntimeException
{
    public static function forOrder(Order $order): self
    {
        return new self("There is no free pigeonhole for order {$order->id}.");
    }

    /**
     * Report the problem to API clients as a 422, like a validation error.
     */
    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
