<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductStock;
use App\Support\Tenant;
use Illuminate\Database\QueryException;

class ProductController extends Controller
{
    private function normalizeLocation(?string $location): string
    {
        $normalized = strtolower(trim((string) $location));
        return $normalized !== '' ? $normalized : 'main';
    }

    public function index()
    {
        $branchId = Tenant::branchId();

        $products = Product::withSum(['stocks as stock_on_hand' => function ($q) use ($branchId) {
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        }], 'quantity')->latest()->get();

        return view('pages.products', compact('products'));
    }

    public function create()
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();
        $productNames = \App\Models\Product::orderBy('name')->pluck('name');
        return view('pages.product_create', compact('categories', 'suppliers', 'productNames'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:products,serial_number',
            'barcode' => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'supplier_id' => 'nullable|integer',
            'cost' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'stock_alert' => 'nullable|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'stock_location' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $branchId = Tenant::branchId();

        $product = Product::create([
            'name' => $data['name'],
            'sku' => $data['sku'],
            'serial_number' => $data['serial_number'],
            'barcode' => $data['barcode'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'price' => $data['price'],
            'stock_alert' => $data['stock_alert'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'description' => $data['description'] ?? null,
        ]);

        // New serialized products should count as stock by default unless user sets a value.
        $initialStock = array_key_exists('stock', $data) ? (int) $data['stock'] : 1;
        $stockLocation = $this->normalizeLocation($data['stock_location'] ?? null);
        ProductStock::updateOrCreate(
            [
                'product_id' => $product->id,
                'location' => $stockLocation,
                'branch_id' => $branchId,
            ],
            [
                'quantity' => $initialStock,
                'business_id' => $product->business_id,
            ]
        );

        return redirect()->route('products')->with('status', 'Product added successfully.');
    }

    public function destroy(Request $request, Product $product)
    {
        $redirectTo = $request->input('redirect_to') === 'settings.index' ? 'settings.index' : 'products';

        try {
            $product->delete();
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return redirect()->route($redirectTo)->with('error', 'Cannot delete product because it is linked to existing sales records.');
            }

            throw $exception;
        }

        return redirect()->route($redirectTo)->with('status', 'Product deleted successfully.');
    }
}
