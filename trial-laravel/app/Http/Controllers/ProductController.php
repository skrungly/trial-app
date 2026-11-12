<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'manufacturer_id' => ['sometimes', 'integer', 'exists:manufacturers,id'],
            'material_id' => ['sometimes', 'integer', 'exists:materials,id'],
        ]);

        $products = Product::query()
            ->when(isset($filters['manufacturer_id']), fn ($query) => $query->where('manufacturer_id', $filters['manufacturer_id']))
            ->when(isset($filters['material_id']), fn ($query) => $query->where('material_id', $filters['material_id']))
            ->get();

        return response()->json($products, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'colour' => ['required', 'string', 'max:64'],
            'size' => ['required', 'string', 'max:32'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'material_id' => ['required', 'integer', 'exists:materials,id'],
            'manufacturer_id' => ['required', 'integer', 'exists:manufacturers,id'],
        ]);

        $product = Product::create($validated);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return response()->json($product, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:128'],
            'colour' => ['sometimes', 'required', 'string', 'max:64'],
            'size' => ['sometimes', 'required', 'string', 'max:32'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'material_id' => ['sometimes', 'required', 'integer', 'exists:materials,id'],
            'manufacturer_id' => ['sometimes', 'required', 'integer', 'exists:manufacturers,id'],
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
