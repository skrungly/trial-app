<?php

namespace App\Http\Controllers;

use App\Models\Manufacturer;
use Illuminate\Http\Request;

class ManufacturerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Manufacturer::all(), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
        ]);

        $manufacturer = Manufacturer::create($validated);

        return response()->json($manufacturer, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Manufacturer $manufacturer)
    {
        return response()->json($manufacturer, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Manufacturer $manufacturer)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:128'],
        ]);

        $manufacturer->update($validated);

        return response()->json($manufacturer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Manufacturer $manufacturer)
    {
        if ($manufacturer->products()->exists()) {
            return response()->json([
                'message' => 'This manufacturer cannot be deleted because products still use it.',
            ], 409);
        }

        $manufacturer->delete();

        return response()->json(null, 204);
    }
}
