<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\Tenant;

class StockController extends Controller
{
    public function adjustForm()
    {
        $categories = Category::orderBy('name')->get();
        $selectedCategoryId = request()->integer('category_id');
        return view('pages.stock_adjust', compact('categories', 'selectedCategoryId'));
    }

    public function adjustStore(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'quantity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
        ]);

        $location = $data['location'] ?? 'main';
        $branchId = Tenant::branchId();
        $product = Product::query()
            ->where('category_id', $data['category_id'])
            ->orderBy('id')
            ->first();

        if (!$product) {
            return back()->withErrors([
                'category_id' => 'No products are configured under this category yet.',
            ])->withInput();
        }

        DB::transaction(function () use ($product, $location, $branchId, $data) {
            $stock = ProductStock::firstOrCreate(
                ['product_id' => $product->id, 'location' => $location, 'branch_id' => $branchId],
                ['quantity' => 0, 'business_id' => auth()->user()->business_id]
            );

            $before = $stock->quantity;
            $after = $before + (int) $data['quantity'];
            $stock->update(['quantity' => $after]);

            StockMovement::create([
                'business_id' => auth()->user()->business_id,
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'location' => $location,
                'type' => 'adjustment',
                'quantity_change' => (int) $data['quantity'],
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference_type' => 'manual_adjustment',
                'reference_id' => null,
                'note' => $data['note'] ?? 'Category adjustment',
            ]);
        });

        $categoryName = Category::whereKey($data['category_id'])->value('name') ?? 'category';
        return redirect()->route('stock')->with('status', 'Stock updated. Added ' . (int) $data['quantity'] . ' unit(s) to ' . $categoryName . '.');
    }

    public function edit(Product $product)
    {
        $branchId = Tenant::branchId();
        $stock = $product->stocks()
            ->where('location', 'main')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->first();

        $quantity = $stock?->quantity ?? 0;

        return view('pages.stock_edit', compact('product', 'quantity'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'location' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:0',
            'note' => 'nullable|string|max:255',
            'serial_number' => ['nullable', 'string', 'max:255', Rule::unique('products', 'serial_number')->ignore($product->id)],
        ]);

        $location = $data['location'] ?? 'main';
        $branchId = Tenant::branchId();

        DB::transaction(function () use ($data, $product, $location, $branchId) {
            $stock = ProductStock::firstOrCreate(
                ['product_id' => $product->id, 'location' => $location, 'branch_id' => $branchId],
                ['quantity' => 0, 'business_id' => auth()->user()->business_id]
            );

            $before = $stock->quantity;
            $after = $data['quantity'];
            $change = $after - $before;

            $stock->update(['quantity' => $after]);

            if ($change !== 0) {
                StockMovement::create([
                    'business_id' => auth()->user()->business_id,
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'location' => $location,
                    'type' => 'adjustment',
                    'quantity_change' => $change,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'reference_type' => 'stock_edit',
                    'reference_id' => $product->id,
                    'note' => $data['note'] ?? 'Stock corrected before sale',
                ]);
            }

            if (!empty($data['serial_number']) && $product->serial_number !== $data['serial_number']) {
                $product->update(['serial_number' => $data['serial_number']]);
            }
        });

        return redirect()->route('stock')->with('status', 'Stock updated for ' . $product->name . '.');
    }
}
