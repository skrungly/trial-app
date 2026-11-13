<?php

namespace App\Http\Controllers\Factory;

use App\Enums\UnitStatus;
use App\Http\Controllers\Controller;
use App\Models\Pigeonhole;
use App\Models\Unit;

class PigeonholeController extends Controller
{
    /**
     * List all pigeonholes in aisle then shelf order, with how full each one is.
     *
     * For a pigeonhole holding an order:
     * - units_count is how many units the order has in total.
     * - consolidated_units_count is how many of them have been consolidated into it
     *   (including any since packed).
     * Both are 0 for an empty pigeonhole.
     */
    public function index()
    {
        // Counts the units of the pigeonhole's order, optionally narrowed by $filter.
        $orderUnits = fn (?callable $filter = null) => Unit::query()
            ->selectRaw('count(*)')
            ->join('order_items', 'order_items.id', '=', 'units.order_item_id')
            ->whereColumn('order_items.order_id', 'pigeonholes.order_id')
            ->when($filter, $filter);

        $pigeonholes = Pigeonhole::query()
            ->select('pigeonholes.*')
            ->addSelect([
                'units_count' => $orderUnits(),
                'consolidated_units_count' => $orderUnits(
                    fn ($query) => $query->whereIn('units.status', [UnitStatus::Consolidated, UnitStatus::Packed])
                ),
            ])
            ->withCasts(['units_count' => 'integer', 'consolidated_units_count' => 'integer'])
            ->orderBy('aisle')
            ->orderBy('shelf')
            ->orderBy('id')
            ->get();

        return response()->json($pigeonholes, 200);
    }

    /**
     * Release the order in a full pigeonhole, freeing it for another order.
     *
     * A consolidated order moves on to packing. Fails with 422 if the pigeonhole is empty or
     * its order still has units to consolidate.
     */
    public function release(Pigeonhole $pigeonhole)
    {
        $pigeonhole->release();

        return response()->json($pigeonhole, 200);
    }
}
