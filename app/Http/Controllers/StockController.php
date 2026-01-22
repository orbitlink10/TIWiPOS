<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function adjustForm()
    {
        $products = Product::orderBy('name')->get();
        return view('pages.stock_adjust', compact('products'));
    }

    public function adjustStore(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'location' => 'nullable|string|max:100',
            'quantity' => 'required|integer|not_in:0',
            'note' => 'nullable|string|max:255',
        ]);

        $location = $data['location'] ?? 'main';

        DB::transaction(function () use ($data, $location) {
            $stock = ProductStock::firstOrCreate(
                ['product_id' => $data['product_id'], 'location' => $location],
                ['quantity' => 0]
            );

            $before = $stock->quantity;
            $after = $before + $data['quantity'];
            $stock->update(['quantity' => $after]);

            StockMovement::create([
                'product_id' => $data['product_id'],
                'user_id' => auth()->id(),
                'location' => $location,
                'type' => 'adjustment',
                'quantity_change' => $data['quantity'],
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference_type' => 'manual_adjustment',
                'reference_id' => null,
                'note' => $data['note'] ?? null,
            ]);
        });

        return redirect()->route('stock')->with('status', 'Stock updated.');
    }
}
