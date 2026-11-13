<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with('items')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return response()->json($orders, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:128'],
            'address' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $order = DB::transaction(function () use ($validated) {
            $products = Product::whereIn('id', array_column($validated['items'], 'product_id'))
                ->get()
                ->keyBy('id');

            // New orders always start as paid; the client cannot choose a status.
            $order = Order::create([
                'total' => 0,
                'status' => OrderStatus::Paid,
                'customer_name' => $validated['customer_name'],
                'address' => $validated['address'],
            ]);
            $total = '0';

            // Names and prices come from the database, never from the request.
            foreach ($validated['items'] as $item) {
                $product = $products[$item['product_id']];

                $line = $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $item['quantity'],
                ]);

                $line->createUnits();

                $total = bcadd($total, $line->line_total, 2);
            }

            $order->update(['total' => $total]);

            return $order;
        });

        return response()->json($order->load('items'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        return response()->json($order->load('items'), 200);
    }
}
