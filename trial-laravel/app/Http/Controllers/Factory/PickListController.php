<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Models\Operative;
use App\Models\PickList;
use Illuminate\Http\Request;

class PickListController extends Controller
{
    /**
     * Generate a pick list from the oldest waiting units that have a location.
     *
     * Optionally takes the operative_id of who the pick list is for.
     * Responds with 204 and creates nothing when there are no units to pick.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'operative_id' => ['nullable', 'integer', 'exists:operatives,id'],
        ]);

        $operative = isset($validated['operative_id']) ? Operative::find($validated['operative_id']) : null;

        $pickList = PickList::createForOldestWaitingUnits($operative);

        if ($pickList === null) {
            return response()->json(null, 204);
        }

        // List units in the order the picker reaches their locations, with where to go and what to pick.
        return response()->json($pickList->load([
            'units' => fn ($query) => $query->inPickingOrder(),
            'units.location',
            'units.orderItem',
        ]), 201);
    }
}
