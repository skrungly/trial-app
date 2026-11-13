<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Models\PickList;

class PickListController extends Controller
{
    /**
     * Generate a pick list from the oldest waiting units that have a location.
     *
     * Responds with 204 and creates nothing when there are no units to pick.
     */
    public function store()
    {
        $pickList = PickList::createForOldestWaitingUnits();

        if ($pickList === null) {
            return response()->json(null, 204);
        }

        // List units in the order the picker reaches their locations, with each location's details.
        return response()->json($pickList->load([
            'units' => fn ($query) => $query->inPickingOrder(),
            'units.location',
        ]), 201);
    }
}
