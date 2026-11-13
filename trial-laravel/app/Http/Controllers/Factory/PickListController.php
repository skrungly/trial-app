<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Models\Operative;
use App\Models\PickList;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PickListController extends Controller
{
    /**
     * Load a pick list's units in the order the picker reaches their locations,
     * with where to go and what to pick for each.
     *
     * @return array<int|string, mixed>
     */
    private function unitsInPickingOrder(): array
    {
        return [
            'units' => fn ($query) => $query->inPickingOrder(),
            'units.location',
            'units.orderItem',
        ];
    }

    /**
     * List pick lists, newest first, with their operative, units and how many units each holds.
     *
     * Filters:
     * - completed: true for completed pick lists only, false for ones still being picked.
     * - operative_id: only pick lists taken by this operative.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            // Query strings arrive as text, so accept "true"/"false" as well as 1/0.
            'completed' => ['sometimes', Rule::in(['true', 'false', '1', '0'])],
            'operative_id' => ['sometimes', 'integer'],
        ]);

        $pickLists = PickList::query()
            ->with(['operative', ...$this->unitsInPickingOrder()])
            ->withCount('units')
            ->when(isset($validated['completed']), fn ($query) => $request->boolean('completed')
                ? $query->whereNotNull('completed_at')
                : $query->whereNull('completed_at'))
            ->when(isset($validated['operative_id']), fn ($query) => $query->where('operative_id', $validated['operative_id']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return response()->json($pickLists, 200);
    }

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

        return response()->json($pickList->load($this->unitsInPickingOrder()), 201);
    }
}
