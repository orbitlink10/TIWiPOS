<?php

namespace App\Http\Controllers;

use App\Models\{Category, Product, ProductStock, StockAdjustment, StockMovement};
use App\Support\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockControlController extends Controller
{
    private function context(): void
    {
        abort_unless(Tenant::businessId() && Tenant::branchId(), 403, 'Select a business and branch first.');
    }

    public function index(Request $request)
    {
        $this->context();
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['stock', 'movements'])], 'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('business_id', Tenant::businessId())],
            'status' => ['nullable', Rule::in(['in', 'low', 'out'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => array_merge(['nullable', 'date_format:Y-m-d'], $request->filled('from') ? ['after_or_equal:from'] : []),
            'type' => ['nullable', Rule::in(['sale', 'purchase', 'adjustment', 'return'])],
        ]);
        $tab = $filters['tab'] ?? 'stock';
        $search = trim($filters['q'] ?? '');
        $canViewValue = $request->user()->canViewFinancials();
        $categories = Category::where('business_id', Tenant::businessId())->whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
        if ($tab === 'movements') {
            $rows = StockMovement::where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId())
                ->with(['product:id,name,sku', 'user:id,name'])
                ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
                ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('note', 'like', '%'.$search.'%')->orWhereHas('product', fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('sku', 'like', '%'.$search.'%'));
                }))->orderByDesc('created_at')->orderByDesc('id')->paginate(25)->withQueryString();
            $totals = null;
        } else {
            $stock = ProductStock::where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId())
                ->selectRaw('product_id, SUM(quantity) as quantity')->groupBy('product_id');
            $query = Product::where('products.business_id', Tenant::businessId())->leftJoinSub($stock, 'stock_totals', 'stock_totals.product_id', '=', 'products.id')
                ->select('products.id', 'name', 'sku', 'category_id', 'stock_alert', 'is_active')
                ->selectRaw('COALESCE(stock_totals.quantity, 0) as on_hand')
                ->when($canViewValue, fn ($query) => $query->selectRaw('COALESCE(stock_totals.quantity, 0) * COALESCE(cost, 0) as stock_value'))
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    foreach (['name', 'sku', 'barcode', 'serial_number'] as $column) $query->orWhere($column, 'like', '%'.$search.'%');
                }))
                ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where(function ($query) use ($id) {
                    $query->where('category_id', $id)->orWhereIn('category_id', Category::where('business_id', Tenant::businessId())->where('parent_id', $id)->select('id'));
                }));
            $status = $filters['status'] ?? '';
            if ($status === 'in') $query->whereRaw('COALESCE(stock_totals.quantity, 0) > 0');
            if ($status === 'out') $query->whereRaw('COALESCE(stock_totals.quantity, 0) <= 0');
            if ($status === 'low') $query->where('stock_alert', '>', 0)->whereRaw('COALESCE(stock_totals.quantity, 0) > 0 AND stock_totals.quantity <= products.stock_alert');
            $summary = DB::query()->fromSub((clone $query)->toBase(), 'stock_rows')
                ->selectRaw('COUNT(*) as products, COALESCE(SUM(on_hand), 0) as units, COALESCE(SUM(CASE WHEN on_hand <= 0 THEN 1 ELSE 0 END), 0) as out_count, COALESCE(SUM(CASE WHEN on_hand > 0 AND stock_alert > 0 AND on_hand <= stock_alert THEN 1 ELSE 0 END), 0) as low_count');
            if ($canViewValue) $summary->selectRaw('COALESCE(SUM(stock_value), 0) as stock_value');
            $totals = $summary->first();
            $rows = $query->with('category.parent')->orderBy('name')->orderBy('products.id')->paginate(25)->withQueryString();
        }

        return view('pages.stock_control.index', compact('filters', 'tab', 'search', 'canViewValue', 'categories', 'rows', 'totals'));
    }

    public function adjust(Request $request)
    {
        $this->context();
        $data = $request->validate(['product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('business_id', Tenant::businessId())]]);
        $products = Product::where('business_id', Tenant::businessId())->orderBy('name')->get(['id', 'name', 'sku']);
        $stocks = ProductStock::where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId())->get(['product_id', 'location', 'quantity']);
        $selectedProduct = old('product_id', $data['product_id'] ?? null);
        return view('pages.stock_control.adjust', compact('products', 'stocks', 'selectedProduct'));
    }

    public function storeAdjustment(Request $request)
    {
        $this->context();
        $data = $request->validate([
            'operation_token' => ['required', 'uuid'],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('business_id', Tenant::businessId())],
            'location' => ['required', 'string', 'max:100'], 'mode' => ['required', Rule::in(['add', 'remove', 'count'])],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'expected_quantity' => ['required', 'integer'],
            'reason' => ['required', Rule::in(['Stock count', 'Opening balance', 'Damage', 'Loss', 'Correction', 'Other'])],
            'notes' => ['nullable', 'required_if:reason,Other', 'string', 'max:1000'],
        ]);
        try {
            DB::transaction(function () use ($data) {
                $product = Product::where('business_id', Tenant::businessId())->whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
                $existing = StockAdjustment::where('operation_token', $data['operation_token'])->first();
                if ($existing) {
                    abort_unless((int) $existing->business_id === (int) Tenant::businessId() && (int) $existing->branch_id === (int) Tenant::branchId() && (int) $existing->product_id === (int) $product->id, 422);
                    return;
                }
                $location = strtolower(trim($data['location']));
                if ($location === '') throw ValidationException::withMessages(['location' => 'Choose a stock location.']);
                $stock = ProductStock::firstOrCreate(['business_id' => Tenant::businessId(), 'branch_id' => Tenant::branchId(), 'product_id' => $product->id, 'location' => $location], ['quantity' => 0]);
                $stock = ProductStock::whereKey($stock->id)->lockForUpdate()->firstOrFail();
                $before = (int) $stock->quantity;
                if ($before !== (int) $data['expected_quantity']) throw ValidationException::withMessages(['quantity' => 'Stock changed while this form was open. Review the latest quantity and submit again.']);
                $quantity = (int) $data['quantity'];
                if ($data['mode'] !== 'count' && $quantity === 0) throw ValidationException::withMessages(['quantity' => 'Enter a quantity greater than zero.']);
                $after = match ($data['mode']) { 'add' => $before + $quantity, 'remove' => $before - $quantity, default => $quantity };
                if ($after < 0 || $after > 2000000000) throw ValidationException::withMessages(['quantity' => 'The adjustment must leave a valid, non-negative stock balance.']);
                if ($after === $before) throw ValidationException::withMessages(['quantity' => 'The entered count matches current stock. No adjustment is needed.']);
                $adjustment = StockAdjustment::create([
                    'business_id' => Tenant::businessId(), 'branch_id' => Tenant::branchId(), 'product_id' => $product->id, 'user_id' => auth()->id(),
                    'operation_token' => $data['operation_token'], 'location' => $location, 'mode' => $data['mode'], 'reason' => $data['reason'],
                    'quantity_before' => $before, 'quantity_after' => $after, 'adjusted_at' => today(), 'notes' => $data['notes'] ?? null,
                ]);
                $stock->update(['quantity' => $after]);
                StockMovement::create([
                    'business_id' => Tenant::businessId(), 'branch_id' => Tenant::branchId(), 'product_id' => $product->id, 'user_id' => auth()->id(),
                    'location' => $location, 'type' => 'adjustment', 'quantity_change' => $after - $before, 'quantity_before' => $before, 'quantity_after' => $after,
                    'reference_type' => 'stock_adjustment', 'reference_id' => $adjustment->id, 'note' => $data['reason'].(!empty($data['notes']) ? ': '.$data['notes'] : ''),
                ]);
            }, 3);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23000') throw $exception;
            throw ValidationException::withMessages(['quantity' => 'This adjustment may already be saved. Check movement history before submitting again.']);
        }

        return redirect()->route('stock-control.index', ['tab' => 'movements', 'type' => 'adjustment'])->with('status', 'Stock adjustment saved and recorded in movement history.');
    }
}
