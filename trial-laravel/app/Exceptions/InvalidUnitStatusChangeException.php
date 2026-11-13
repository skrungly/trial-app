<?php

namespace App\Exceptions;

use App\Enums\UnitStatus;
use App\Models\Order;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Thrown when a unit's status is changed in a way that would skip a required step.
 */
class InvalidUnitStatusChangeException extends RuntimeException
{
    public static function mustBeConsolidatedBeforePacking(Unit $unit, UnitStatus $from): self
    {
        return new self("Unit #{$unit->id} must be consolidated before it can be packed.");
    }

    public static function orderAlreadyConsolidated(Unit $unit, Order $order): self
    {
        return new self("Order #{$order->id} is already consolidated.");
    }

    /**
     * Report the problem to API clients as a 422, like a validation error.
     */
    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
