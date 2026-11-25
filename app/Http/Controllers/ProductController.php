<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Support\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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

    private function categoriesForSelection()
    {
        return Category::query()
            ->whereNull('parent_id')
            ->orderBy('name');
    }

    private function categoryExistsRule(?int $businessId)
    {
        return Rule::exists('categories', 'id')->where(function ($query) use ($businessId) {
            $query->whereNull('parent_id');

            if ($businessId) {
                $query->where('business_id', $businessId);
            } else {
                $query->whereNull('business_id');
            }
        });
    }

    public function index()
    {
        $branchId = Tenant::branchId();
        $businessId = Tenant::businessId();
        $selectedSubCategory = null;
        if (request()->filled('category_id')) {
            $selectedSubCategory = Category::query()
                ->where('business_id', $businessId)
                ->whereNotNull('parent_id')
                ->findOrFail(request()->query('category_id'));
        }

        $products = Product::where('business_id', $businessId)
            ->when($selectedSubCategory, fn ($query) => $query->where('category_id', $selectedSubCategory->id))
            ->withSum(['stocks as stock_on_hand' => function ($q) use ($branchId) {
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        }], 'quantity')->latest()->get();

        if ($selectedSubCategory) {
            $products = $products->filter(fn (Product $product) => (int) $product->stock_on_hand > 0)->values();
        }

        return view('pages.products', compact('products', 'selectedSubCategory'));
    }

    public function show(Request $request, Product $product)
    {
        abort_unless((int) $product->business_id === (int) Tenant::businessId(), 404);
        $movementTypes = ['sale' => 'Sales', 'purchase' => 'Purchase receipts', 'adjustment' => 'Adjustments', 'return' => 'Returns'];
        $movementFilters = $request->validate([
            'movement_from' => ['nullable', 'date_format:Y-m-d'],
            'movement_to' => array_merge(['nullable', 'date_format:Y-m-d'], $request->filled('movement_from') ? ['after_or_equal:movement_from'] : []),
            'movement_type' => ['nullable', Rule::in(array_keys($movementTypes))],
            'movement_format' => ['nullable', Rule::in(['csv', 'print'])],
        ]);
        $movementFormat = $movementFilters['movement_format'] ?? null;
        unset($movementFilters['movement_format']);
        $branchId = Tenant::branchId();
        $product->load(['category.parent', 'supplier']);
        $stocks = $product->stocks()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->orderBy('location')->get();
        $serials = DB::table('purchase_receipt_serials as serials')
            ->join('purchase_receipt_lines as lines', 'lines.id', '=', 'serials.purchase_receipt_line_id')
            ->join('purchase_receipts as receipts', 'receipts.id', '=', 'lines.purchase_receipt_id')
            ->where('lines.product_id', $product->id)->where('receipts.business_id', Tenant::businessId())
            ->when($branchId, fn ($query) => $query->where('receipts.branch_id', $branchId))
            ->orderByDesc('serials.id')->paginate(20, ['serials.serial_number', 'receipts.received_at'], 'serial_page')->withQueryString();
        $movementQuery = StockMovement::where('business_id', Tenant::businessId())->where('product_id', $product->id)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->with('user:id,name')
            ->when($movementFilters['movement_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($movementFilters['movement_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($movementFilters['movement_type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->orderByDesc('created_at')->orderByDesc('id');
        if ($movementFormat === 'csv') {
            $branchName = view()->shared('currentBranch')?->name ?? 'Current business';
            return response()->streamDownload(function () use ($movementQuery, $product, $branchName) {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");
                fputcsv($file, ['Product', 'SKU', 'Branch', 'Date / time', 'Type', 'Location', 'Change', 'Before', 'After', 'Reference', 'Notes', 'Recorded by']);
                $safeText = fn ($value) => preg_match('/^[\s]*[=+@-]/u', (string) $value) ? "'".$value : (string) $value;
                foreach ($movementQuery->lazy(500) as $movement) {
                    $reference = $movement->reference_type ? str($movement->reference_type)->replace('_', ' ')->title().' #'.$movement->reference_id : '';
                    fputcsv($file, [
                        $safeText($product->name), $safeText($product->sku), $safeText($branchName), $movement->created_at->format('Y-m-d H:i:s'),
                        $movement->type === 'purchase' ? 'Purchase receipt' : ucfirst($movement->type), $safeText($movement->location),
                        (int) $movement->quantity_change, $movement->quantity_before === null ? '' : (int) $movement->quantity_before,
                        $movement->quantity_after === null ? '' : (int) $movement->quantity_after, $safeText($reference), $safeText($movement->note), $safeText($movement->user?->name),
                    ]);
                }
                fclose($file);
            }, 'product-'.$product->id.'-stock-movements-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
        if ($movementFormat === 'print') {
            $movementCount = (clone $movementQuery)->count();
            $movements = $movementQuery->lazy(500);
            return view('pages.product_movements_print', compact('product', 'movements', 'movementCount', 'movementFilters', 'movementTypes'));
        }
        $movements = $movementQuery->paginate(20, ['*'], 'movement_page')->withQueryString()->fragment('stock-movements');
        $canViewFinancials = auth()->user()->canViewFinancials();

        return view('pages.product_show', compact('product', 'stocks', 'serials', 'movements', 'canViewFinancials', 'movementFilters', 'movementTypes'));
    }

    public function create()
    {
        $categories = $this->categoriesForSelection()->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();
        $productNames = \App\Models\Product::orderBy('name')->pluck('name');
        return view('pages.product_create', compact('categories', 'suppliers', 'productNames'));
    }

    public function edit(Product $product)
    {
        $branchId = Tenant::branchId();
        $categories = $this->categoriesForSelection()->get();
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
            'serial_number' => 'nullable|string|max:255|unique:products,serial_number',
            'barcode' => 'nullable|string|max:255',
            'category_id' => [
                'required',
                'integer',
                $this->categoryExistsRule($businessId),
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
            'sku' => 'SKU-'.Str::ulid(),
            'serial_number' => $data['serial_number'] ?? null,
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
            'serial_number' => ['nullable', 'string', 'max:255', Rule::unique('products', 'serial_number')->ignore($product->id)],
            'barcode' => 'nullable|string|max:255',
            'category_id' => [
                'required',
                'integer',
                $this->categoryExistsRule($businessId),
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
            'serial_number' => $data['serial_number'] ?? null,
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

        return redirect()->route('products.show', $product)->with('status', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product)
    {
        $redirectTo = $request->input('redirect_to') === 'settings.index' ? 'settings.index' : 'products';

        try {
            $product->delete();
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return redirect()->route($redirectTo)->with('error', 'Cannot delete this product because it has transaction history. Mark it inactive instead.');
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
