<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Material::all(), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
        ]);

        $material = Material::create($validated);

        return response()->json($material, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Material $material)
    {
        return response()->json($material, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:128'],
        ]);

        $material->update($validated);

        return response()->json($material);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Material $material)
    {
        $material->delete();

        return response()->json(null, 204);
    }
}
