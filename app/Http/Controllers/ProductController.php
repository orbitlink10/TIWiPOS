<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductStock;
use App\Support\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    private function ensureCatalogManager(): void
    {
        if (!auth()->user()->canAccessAbility('manage_catalog')) {
            abort(403, 'Your role cannot manage products.');
        }
    }

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

    public function edit(Product $product)
    {
        $branchId = Tenant::branchId();
        $categories = \App\Models\Category::orderBy('name')->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();

        $stockRow = $product->stocks()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByRaw("CASE WHEN location = 'main' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        $stockQuantity = (int) ($stockRow->quantity ?? 0);
        $stockLocation = (string) ($stockRow->location ?? 'main');

        return view('pages.product_edit', compact('product', 'categories', 'suppliers', 'stockQuantity', 'stockLocation'));
    }

    public function store(Request $request)
    {
        $businessId = Tenant::businessId();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:products,serial_number',
            'barcode' => 'nullable|string|max:255',
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'cost' => 'required|numeric|gt:0',
            'price' => 'required|numeric|min:0',
            'stock_alert' => 'nullable|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'stock_location' => 'nullable|string|max:100',
            'recorded_at' => 'nullable|date',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ], [
            'cost.required' => 'Product cost is required.',
            'cost.gt' => 'Product cost must be greater than 0.',
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
            'recorded_at' => $data['recorded_at'] ?? now()->toDateString(),
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

    public function update(Request $request, Product $product)
    {
        $businessId = Tenant::businessId();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255',
            'serial_number' => ['required', 'string', 'max:255', Rule::unique('products', 'serial_number')->ignore($product->id)],
            'barcode' => 'nullable|string|max:255',
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'cost' => 'required|numeric|gt:0',
            'price' => 'required|numeric|min:0',
            'stock_alert' => 'nullable|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'stock_location' => 'nullable|string|max:100',
            'recorded_at' => 'nullable|date',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ], [
            'cost.required' => 'Product cost is required.',
            'cost.gt' => 'Product cost must be greater than 0.',
        ]);

        $branchId = Tenant::branchId();

        $product->update([
            'name' => $data['name'],
            'sku' => $data['sku'],
            'serial_number' => $data['serial_number'],
            'barcode' => $data['barcode'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'price' => $data['price'],
            'stock_alert' => $data['stock_alert'] ?? 0,
            'recorded_at' => $data['recorded_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'description' => $data['description'] ?? null,
        ]);

        if (array_key_exists('stock', $data)) {
            $stockLocation = $this->normalizeLocation($data['stock_location'] ?? null);
            ProductStock::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'location' => $stockLocation,
                    'branch_id' => $branchId,
                ],
                [
                    'quantity' => (int) $data['stock'],
                    'business_id' => $product->business_id,
                ]
            );
        }

        return redirect()->route('products')->with('status', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product)
    {
        $redirectTo = $request->input('redirect_to') === 'settings.index' ? 'settings.index' : 'products';

        try {
            $product->delete();
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return redirect()->route($redirectTo)->with('error', 'Cannot delete product because it is linked to existing sales records. Archive it instead.');
            }

            throw $exception;
        }

        return redirect()->route($redirectTo)->with('status', 'Product deleted successfully.');
    }

    public function status(Request $request, Product $product)
    {
        $this->ensureCatalogManager();

        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $product->is_active = (bool) $data['is_active'];
        $product->save();

        $redirectTo = $request->input('redirect_to') === 'settings.index' ? 'settings.index' : 'products';

        return redirect()->route($redirectTo)->with(
            'status',
            $product->is_active ? 'Product activated successfully.' : 'Product archived successfully.'
        );
    }
}
