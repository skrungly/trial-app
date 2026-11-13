<?php

namespace App\Http\Controllers\Factory;

use App\Enums\UnitStatus;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    /**
     * List all units, each with the pigeonhole its order is in (null if none).
     */
    public function index()
    {
        return response()->json(Unit::with('pigeonhole')->orderBy('id')->get(), 200);
    }

    /**
     * Change a unit's status, e.g. to mark it as picked.
     *
     * Only the status can be changed; any other fields sent are ignored.
     */
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(UnitStatus::class)],
        ]);

        $unit->update(['status' => $validated['status']]);

        return response()->json($unit->load('pigeonhole'), 200);
    }
}
