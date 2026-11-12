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
            'name' => ['sometimes', 'nullable', 'string', 'max:128'],
        ]);

        $products = Product::query()
            ->with(['manufacturer', 'material'])
            ->when(isset($filters['name']), function ($query) use ($filters) {
                // Lowercase both sides so the match ignores case on every database,
                // and escape LIKE wildcards so the search term is matched literally.
                $term = addcslashes(mb_strtolower($filters['name']), '\\%_');

                $query->whereRaw("lower(name) like ? escape '\\'", ["%{$term}%"]);
            })
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
        $product->load(['manufacturer', 'material']);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load(['manufacturer', 'material']);

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
        $product->load(['manufacturer', 'material']);

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
