<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    private function authorizeAccess(Request $request): void
    {
        abort_unless($request->user()->canViewFinancials() && Tenant::businessId(), 403);
    }

    private function invoices()
    {
        return SupplierInvoice::where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId());
    }

    public function index(Request $request)
    {
        $this->authorizeAccess($request);
        $data = $request->validate([
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('business_id', Tenant::businessId())],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => array_merge(['nullable', 'date_format:Y-m-d'], $request->filled('from') ? ['after_or_equal:from'] : []),
        ]);
        $search = trim($data['q'] ?? '');
        $query = $this->invoices()->select('supplier_invoices.*')->withSum('payments', 'amount_cents')
            ->when($data['supplier_id'] ?? null, fn ($query, $id) => $query->where('supplier_id', $id))
            ->when($data['from'] ?? null, fn ($query, $date) => $query->where('supplied_at', '>=', $date))
            ->when($data['to'] ?? null, fn ($query, $date) => $query->where('supplied_at', '<=', $date))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('reference', 'like', '%'.$search.'%')->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', '%'.$search.'%'));
            }));
        $balance = 'CASE WHEN total_cents > COALESCE(payments_sum_amount_cents, 0) THEN total_cents - COALESCE(payments_sum_amount_cents, 0) ELSE 0 END';
        $totals = DB::query()->fromSub((clone $query)->toBase(), 'purchase_rows')
            ->selectRaw('COALESCE(SUM(total_cents), 0) as total, COALESCE(SUM(payments_sum_amount_cents), 0) as paid')
            ->selectRaw("COALESCE(SUM({$balance}), 0) as outstanding")
            ->selectRaw("COALESCE(SUM(CASE WHEN due_at < ? THEN {$balance} ELSE 0 END), 0) as overdue", [today()->toDateString()])->first();
        $invoices = $query->with(['supplier', 'payments'])->orderByDesc('supplied_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $suppliers = Supplier::where('business_id', Tenant::businessId())->orderBy('name')->get(['id', 'name']);

        return view('pages.purchases.index', compact('invoices', 'suppliers', 'totals', 'search'));
    }

    public function create(Request $request)
    {
        $this->authorizeAccess($request);
        $data = $request->validate(['supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('business_id', Tenant::businessId())->where('is_active', true)]]);
        $suppliers = Supplier::where('business_id', Tenant::businessId())->where('is_active', true)->orderBy('name')->get();
        $supplier = isset($data['supplier_id']) ? $suppliers->firstWhere('id', $data['supplier_id']) : null;
        $products = $supplier ? Product::where('business_id', Tenant::businessId())
            ->orderByRaw('CASE WHEN supplier_id = ? THEN 0 ELSE 1 END', [$supplier->id])->orderBy('name')->get() : collect();

        return view('pages.purchases.create', compact('suppliers', 'supplier', 'products'));
    }

    public function show(Request $request, int $invoice)
    {
        $this->authorizeAccess($request);
        $invoice = $this->invoices()->with(['supplier', 'items', 'payments'])->findOrFail($invoice);
        $supplier = $invoice->supplier;
        abort_unless($supplier && (int) $supplier->business_id === (int) Tenant::businessId(), 404);

        return view('pages.purchases.show', compact('invoice', 'supplier'));
    }
}
