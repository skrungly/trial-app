<?php

namespace App\Exceptions;

use App\Models\Pigeonhole;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Thrown when releasing a pigeonhole that has nothing to release, or whose order isn't fully consolidated yet.
 */
class PigeonholeCannotBeReleasedException extends RuntimeException
{
    public static function empty(Pigeonhole $pigeonhole): self
    {
        return new self("Pigeonhole {$pigeonhole->code} is already empty.");
    }

    public static function notFull(Pigeonhole $pigeonhole, int $unconsolidated): self
    {
        $units = $unconsolidated === 1 ? '1 unit' : "{$unconsolidated} units";

        return new self("Order #{$pigeonhole->order_id} in pigeonhole {$pigeonhole->code} still has {$units} to consolidate.");
    }

    /**
     * Report the problem to API clients as a 422, like a validation error.
     */
    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
