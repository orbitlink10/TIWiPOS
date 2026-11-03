<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    private function suppliers()
    {
        abort_unless(Tenant::businessId(), 403, 'Select a business first.');

        return Supplier::query()->where('business_id', Tenant::businessId());
    }

    private function supplier(int $id): Supplier
    {
        return $this->suppliers()->findOrFail($id);
    }

    private function invoices(int $supplier)
    {
        return SupplierInvoice::where('business_id', Tenant::businessId())
            ->where('branch_id', Tenant::branchId())->where('supplier_id', $supplier);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->canViewFinancials(), 403);
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $suppliers = $this->suppliers()->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('contact_name', 'like', "%{$search}%");
        }))->orderBy('name')->paginate(20)->withQueryString();
        $invoices = SupplierInvoice::where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId())->with('payments')->get();
        $total = $invoices->sum('total_cents');
        $balance = $invoices->sum('balance_cents');
        $overdue = $invoices->where('status', 'Overdue')->sum('balance_cents');
        $balances = $invoices->groupBy('supplier_id')->map(fn ($rows) => $rows->sum('balance_cents'));

        return view('pages.suppliers.index', compact('suppliers', 'search', 'total', 'balance', 'overdue', 'balances'));
    }

    public function create()
    {
        return view('pages.supplier_create');
    }

    public function store(Request $request)
    {
        $this->suppliers();
        $supplier = Supplier::create($this->details($request) + ['business_id' => Tenant::businessId()]);

        return $request->user()->canViewFinancials()
            ? redirect()->route('suppliers.show', $supplier->id)->with('status', 'Supplier added.')
            : redirect()->route('products.create')->with('status', 'Supplier added.');
    }

    private function details(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30', 'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255', 'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255', 'is_active' => 'required|boolean',
        ]);

        return $data;
    }

    public function update(Request $request, int $supplier)
    {
        $this->supplier($supplier)->update($this->details($request));

        return back()->with('status', 'Supplier details updated.');
    }

    public function show(int $supplier)
    {
        abort_unless(auth()->user()->canViewFinancials(), 403);
        $supplier = $this->supplier($supplier);
        $invoices = $this->invoices($supplier->id)->with(['payments', 'items'])->orderByDesc('supplied_at')->orderByDesc('id')->get();
        $products = Product::where('business_id', Tenant::businessId())->orderBy('name')->get();

        return view('pages.suppliers.show', compact('supplier', 'invoices', 'products'));
    }

    // All ledger amounts are calculated and persisted in whole cents.
    private function cents($value): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function supply(Request $request, int $supplier)
    {
        abort_unless($request->user()->canViewFinancials(), 403);
        $supplier = $this->supplier($supplier);
        abort_unless($supplier->is_active && Tenant::branchId(), 422, 'An active supplier and branch are required.');
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('supplier_invoices')->where('business_id', Tenant::businessId())->where('supplier_id', $supplier->id)],
            'supplied_at' => 'required|date|before_or_equal:today',
            'terms' => 'required|in:cash,credit',
            'due_at' => 'nullable|required_if:terms,credit|date|after_or_equal:supplied_at',
            'notes' => 'nullable|string|max:2000', 'receive_stock' => 'nullable|boolean',
            'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => ['nullable', 'integer', 'distinct', Rule::exists('products', 'id')->where('business_id', Tenant::businessId())],
            'items.*.product_search' => 'nullable|required_without:items.*.product_id|string|max:255',
            'items.*.quantity' => 'required|integer|min:1|max:1000000',
            'items.*.unit_cost' => ['required', 'regex:/^\d{1,8}(\.\d{1,2})?$/'],
            'amount' => ['required', 'regex:/^\d{1,14}(\.\d{1,2})?$/'],
            'method' => 'required|in:cash,mpesa,bank,cheque,other', 'payment_reference' => 'nullable|string|max:100',
        ]);
        $receive = $request->boolean('receive_stock');
        abort_if($receive && ! $request->user()->canAccessAbility('add_stock'), 403);
        $total = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $this->cents($item['unit_cost']));
        $paid = $this->cents($data['amount']);
        if ($total <= 0 || $paid > $total || ($data['terms'] === 'cash' && $paid !== $total)) {
            throw ValidationException::withMessages(['amount' => 'Enter a positive invoice total. Payment cannot exceed it; cash purchases must be paid in full.']);
        }
        DB::transaction(function () use ($data, $supplier, $total, $paid, $receive) {
            $invoice = SupplierInvoice::create([
                'business_id' => Tenant::businessId(), 'branch_id' => Tenant::branchId(), 'supplier_id' => $supplier->id,
                'reference' => $data['reference'], 'supplied_at' => $data['supplied_at'], 'due_at' => $data['terms'] === 'credit' ? $data['due_at'] : null,
                'terms' => $data['terms'], 'total_cents' => $total, 'stock_received' => $receive, 'notes' => $data['notes'] ?? null, 'user_id' => auth()->id(),
            ]);
            foreach ($data['items'] as $item) {
                $product = ! empty($item['product_id'])
                    ? Product::where('business_id', Tenant::businessId())->lockForUpdate()->findOrFail($item['product_id'])
                    : null;
                if ($receive && ! $product) {
                    throw ValidationException::withMessages(['receive_stock' => 'To add stock, choose a registered product for every row. Otherwise, uncheck Add these quantities to save typed names on the invoice.']);
                }
                $invoice->items()->create(['product_id' => $product?->id, 'product_name' => $product?->name ?? trim($item['product_search']), 'serial_number' => $product?->serial_number,
                    'quantity' => $item['quantity'], 'unit_cost_cents' => $this->cents($item['unit_cost'])]);
                if ($receive) {
                    $stock = ProductStock::firstOrCreate(['business_id' => Tenant::businessId(), 'branch_id' => Tenant::branchId(), 'product_id' => $product->id, 'location' => 'main'], ['quantity' => 0]);
                    $stock = ProductStock::whereKey($stock->id)->lockForUpdate()->firstOrFail();
                    $before = $stock->quantity;
                    $stock->increment('quantity', $item['quantity']);
                    StockMovement::create(['business_id' => Tenant::businessId(), 'branch_id' => Tenant::branchId(), 'product_id' => $product->id,
                        'user_id' => auth()->id(), 'location' => 'main', 'type' => 'purchase', 'quantity_change' => $item['quantity'],
                        'quantity_before' => $before, 'quantity_after' => $before + $item['quantity'], 'reference_type' => 'supplier_invoice',
                        'reference_id' => $invoice->id, 'note' => 'Supplier invoice: '.$data['reference']]);
                }
            }
            if ($paid > 0) {
                $invoice->payments()->create(['amount_cents' => $paid, 'paid_at' => $data['supplied_at'], 'method' => $data['method'], 'reference' => $data['payment_reference'] ?? null, 'user_id' => auth()->id()]);
            }
        });

        return back()->with('status', 'Supplier invoice recorded.'.($receive ? ' Branch stock increased.' : ' Stock unchanged.'));
    }

    public function pay(Request $request, int $supplier, int $invoice)
    {
        abort_unless($request->user()->canViewFinancials(), 403);
        $this->supplier($supplier);
        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d{1,14}(\.\d{1,2})?$/'],
            'paid_at' => 'required|date|before_or_equal:today', 'method' => 'required|in:cash,mpesa,bank,cheque,other',
            'reference' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:2000',
        ]);
        DB::transaction(function () use ($supplier, $invoice, $data) {
            $invoice = $this->invoices($supplier)->lockForUpdate()->findOrFail($invoice);
            $amount = $this->cents($data['amount']);
            if ($amount <= 0 || $amount > $invoice->balance_cents || $data['paid_at'] < $invoice->supplied_at->toDateString()) {
                throw ValidationException::withMessages(['amount' => 'Payment must be positive, within the outstanding balance, and dated on or after supply.']);
            }
            $invoice->payments()->create(['amount_cents' => $amount, 'paid_at' => $data['paid_at'], 'method' => $data['method'], 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null, 'user_id' => auth()->id()]);
        });

        return back()->with('status', 'Supplier payment recorded.');
    }
}
