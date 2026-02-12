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
            'serial_numbers' => 'required|string',
            'location' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
        ]);

        $location = $data['location'] ?? 'main';
        $branchId = Tenant::branchId();
        $rawSerials = preg_split('/[\r\n,]+/', $data['serial_numbers']) ?: [];
        $serialNumbers = array_values(array_unique(array_filter(array_map('trim', $rawSerials))));

        $serialCount = count($serialNumbers);
        if ($serialCount === 0) {
            return back()->withErrors([
                'serial_numbers' => 'Enter at least one serial number.',
            ])->withInput();
        }

        $category = Category::find($data['category_id']);
        if (!$category) {
            return back()->withErrors([
                'category_id' => 'Selected category was not found.',
            ])->withInput();
        }

        $productsBySerial = Product::query()
            ->whereIn('serial_number', $serialNumbers)
            ->get()
            ->keyBy('serial_number');

        $mismatchedSerials = $productsBySerial
            ->filter(function ($product) use ($category) {
                return !empty($product->category_id) && (int) $product->category_id !== (int) $category->id;
            })
            ->keys()
            ->values()
            ->all();

        if (!empty($mismatchedSerials)) {
            return back()->withErrors([
                'serial_numbers' => 'These serial numbers already belong to another category: ' . implode(', ', $mismatchedSerials),
            ])->withInput();
        }

        DB::transaction(function () use ($productsBySerial, $location, $branchId, $data, $serialNumbers, $category) {
            foreach ($serialNumbers as $serialNumber) {
                $product = $productsBySerial->get($serialNumber);

                if (!$product) {
                    $skuSuffix = preg_replace('/[^A-Za-z0-9]/', '', $serialNumber);
                    $skuSuffix = $skuSuffix !== '' ? strtoupper($skuSuffix) : strtoupper(bin2hex(random_bytes(4)));
                    $product = Product::create([
                        'name' => $category->name . ' ' . $serialNumber,
                        'sku' => 'AUTO-' . $skuSuffix . '-' . strtoupper(substr((string) uniqid(), -6)),
                        'serial_number' => $serialNumber,
                        'category_id' => $category->id,
                        'supplier_id' => null,
                        'cost' => 0,
                        'price' => 0,
                        'stock_alert' => 0,
                        'is_active' => true,
                        'description' => null,
                    ]);
                } elseif (empty($product->category_id)) {
                    $product->update(['category_id' => $category->id]);
                }

                $stock = ProductStock::firstOrCreate(
                    ['product_id' => $product->id, 'location' => $location, 'branch_id' => $branchId],
                    ['quantity' => 0, 'business_id' => auth()->user()->business_id]
                );

                $before = $stock->quantity;
                $after = $before + 1;
                $stock->update(['quantity' => $after]);

                StockMovement::create([
                    'business_id' => auth()->user()->business_id,
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'location' => $location,
                    'type' => 'adjustment',
                    'quantity_change' => 1,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'reference_type' => 'manual_adjustment',
                    'reference_id' => null,
                    'note' => trim(($data['note'] ?? '') . ' Serial: ' . $serialNumber),
                ]);
            }
        });

        return redirect()->route('stock')->with('status', 'Stock updated. Added ' . $serialCount . ' serial number(s) to ' . $category->name . '.');
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
