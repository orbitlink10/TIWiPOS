<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\OperationalReports;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    private ?int $reportBranchId = null;

    public function index(Request $request)
    {
        $user = $request->user();
        $reports = [
            'sales' => ['Sales register', 'Sales', 'Completed receipts, VAT and installation charges.'],
            'daily-sales' => ['Daily sales', 'Sales', 'Daily receipt counts and sales totals.'],
            'product-sales' => ['Product performance', 'Sales', 'Quantities sold and product revenue, excluding VAT and installation.'],
            'payments' => ['Payment methods', 'Sales', 'Recorded sale payments by method and payment date.'],
        ];
        if ($user->canAccessAbility('view_stock')) {
            if ($user->canViewProfit()) {
                $reports['stock-valuation'] = ['Stock valuation', 'Inventory', 'Current stock at current catalogue cost; not a historical valuation.'];
            }
            $reports['low-stock'] = ['Low & out-of-stock', 'Inventory', 'Active products at or below their reorder level, including zero stock.'];
            $reports['stock-movements'] = ['Stock movements', 'Inventory', 'Stock changes recorded during the selected period.'];
        }
        if ($user->canAccessAbility('manage_catalog') && $user->canViewFinancials() && Tenant::businessId()) {
            $reports['purchases'] = ['Supplier purchases', 'Purchasing', 'Invoices supplied during the selected period, with balances as of now.'];
        }

        $reports += OperationalReports::catalog($user);
        $branches = Branch::where('business_id', Tenant::businessId())->orderBy('name')->get(['id', 'name']);
        $reportTimezone = config('app.reporting_timezone', 'Africa/Nairobi');
        $reportToday = now()->timezone($reportTimezone)->toDateString();

        $data = $request->validate([
            'as_at' => ['sometimes', 'required', 'date_format:Y-m-d', 'before_or_equal:'.$reportToday],
            'branch_id' => ['sometimes', 'required', Rule::in($branches->pluck('id')->map(fn ($id) => (string) $id)->push('all')->all())],
            'report' => ['sometimes', 'string', Rule::in(array_keys($reports))],
            'from' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'required', 'date_format:Y-m-d', 'after_or_equal:'.($request->input('from') ?: now()->startOfMonth()->toDateString())],
            'q' => ['nullable', 'string', 'max:100'],
            'format' => ['sometimes', Rule::in(['csv'])],
        ]);
        $branchFilter = $data['branch_id'] ?? (Tenant::branchId() ? (string) Tenant::branchId() : 'all');
        $this->reportBranchId = $branchFilter === 'all' ? null : (int) $branchFilter;
        $reportBranchName = $this->reportBranchId ? $branches->firstWhere('id', $this->reportBranchId)->name : 'All branches';
        $report = $data['report'] ?? 'sales';
        $from = $data['from'] ?? now()->startOfMonth()->toDateString();
        $to = $data['to'] ?? now()->toDateString();
        $asAt = $data['as_at'] ?? $reportToday;
        if ($report === 'stock-as-at') {
            $to = $asAt;
            $from = min($from, $to);
        }
        $search = trim($data['q'] ?? '');
        $currentStock = in_array($report, ['stock-valuation', 'low-stock'], true);
        $columns = [];
        $metrics = [];

        switch ($report) {
            case 'sales':
                $query = $this->sales()->select('sales.id', 'sale_number', 'created_at', 'customer_name', 'subtotal', 'tax', 'installation_amount', 'total');
                $this->period($query, 'sales.created_at', $from, $to);
                $this->search($query, ['sale_number', 'customer_name', 'customer_phone'], $search);
                $query->orderByDesc('sales.created_at')->orderByDesc('sales.id');
                $columns = ['sale_number'=>['Receipt','text'], 'created_at'=>['Date','datetime'], 'customer_name'=>['Customer','text'], 'subtotal'=>['Product sales','money'], 'tax'=>['VAT','money'], 'installation_amount'=>['Installation','money'], 'total'=>['Total','money']];
                $metrics = ['subtotal'=>'Product sales', 'tax'=>'VAT', 'total'=>'Sales incl. VAT'];
                break;
            case 'daily-sales':
                $query = $this->sales()->selectRaw('DATE(sales.created_at) as day, COUNT(*) as receipts, SUM(subtotal) as subtotal, SUM(tax) as tax, SUM(installation_amount) as installation_amount, SUM(total) as total');
                $this->period($query, 'sales.created_at', $from, $to);
                $query->groupByRaw('DATE(sales.created_at)')->orderByDesc('day');
                $columns = ['day'=>['Date','text'], 'receipts'=>['Receipts','integer'], 'subtotal'=>['Product sales','money'], 'tax'=>['VAT','money'], 'installation_amount'=>['Installation','money'], 'total'=>['Total','money']];
                $metrics = ['receipts'=>'Receipts', 'tax'=>'VAT', 'total'=>'Sales incl. VAT'];
                break;
            case 'product-sales':
                $sales = $this->sales()->select('sales.id');
                $this->period($sales, 'sales.created_at', $from, $to);
                $query = SaleItem::withoutGlobalScope('branch')->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->whereIn('sale_items.sale_id', $sales)
                    ->selectRaw('products.id, products.name, products.sku, SUM(sale_items.quantity) as quantity, SUM(sale_items.subtotal) as revenue')
                    ->groupBy('products.id', 'products.name', 'products.sku')->orderByDesc('revenue')->orderBy('products.id');
                $this->search($query, ['products.name', 'products.sku'], $search);
                $columns = ['name'=>['Product','text'], 'sku'=>['SKU','text'], 'quantity'=>['Units sold','integer'], 'revenue'=>['Product revenue','money']];
                $metrics = ['quantity'=>'Units sold', 'revenue'=>'Product revenue'];
                if ($user->canViewProfit()) {
                    $cost = Schema::hasColumn('sale_items', 'unit_cost') ? 'COALESCE(sale_items.unit_cost, products.cost, 0)' : 'COALESCE(products.cost, 0)';
                    $query->selectRaw("SUM(sale_items.subtotal - sale_items.quantity * {$cost}) as gross_profit");
                    $columns['gross_profit'] = ['Gross product profit','money'];
                    $metrics['gross_profit'] = 'Gross product profit';
                }
                break;
            case 'payments':
                $query = $this->branchScope(Payment::query(), 'payments')->whereIn('sale_id', $this->sales()->select('sales.id'))
                    ->selectRaw('method, COUNT(*) as payments_count, SUM(amount) as amount')->groupBy('method')->orderBy('method');
                $this->period($query, 'payments.paid_at', $from, $to);
                $columns = ['method'=>['Method','text'], 'payments_count'=>['Payments','integer'], 'amount'=>['Amount','money']];
                $metrics = ['payments_count'=>'Payments', 'amount'=>'Recorded payments'];
                break;
            case 'stock-valuation':
            case 'low-stock':
                $stock = $this->branchScope(ProductStock::query(), 'product_stocks')->selectRaw('product_id, SUM(quantity) as on_hand')->groupBy('product_id');
                $query = Product::query()->leftJoinSub($stock, 'stock_totals', 'stock_totals.product_id', '=', 'products.id')
                    ->where('is_active', true)->select('products.id', 'name', 'sku', 'stock_alert')
                    ->selectRaw('COALESCE(stock_totals.on_hand, 0) as quantity')->orderBy('name')->orderBy('products.id');
                $this->search($query, ['name', 'sku', 'barcode'], $search);
                $columns = ['name'=>['Product','text'], 'sku'=>['SKU','text'], 'quantity'=>['On hand','integer'], 'stock_alert'=>['Reorder level','integer']];
                $metrics = ['quantity'=>'Units on hand'];
                if ($report === 'low-stock') {
                    $query->whereRaw('COALESCE(stock_totals.on_hand, 0) <= products.stock_alert');
                } else {
                    $query->addSelect('cost')->selectRaw('COALESCE(stock_totals.on_hand, 0) * COALESCE(cost, 0) as stock_value');
                    $columns['cost'] = ['Unit cost','money'];
                    $columns['stock_value'] = ['Stock value at cost','money'];
                    $metrics['stock_value'] = 'Stock value at cost';
                }
                break;
            case 'stock-movements':
                $query = $this->branchScope(StockMovement::query(), 'stock_movements')->join('products', 'products.id', '=', 'stock_movements.product_id')
                    ->select('stock_movements.id', 'stock_movements.created_at', 'products.name', 'products.sku', 'stock_movements.type', 'location', 'quantity_change', 'quantity_before', 'quantity_after', 'note')
                    ->orderByDesc('stock_movements.created_at')->orderByDesc('stock_movements.id');
                $this->period($query, 'stock_movements.created_at', $from, $to);
                $this->search($query, ['products.name', 'products.sku', 'stock_movements.note'], $search);
                $columns = ['created_at'=>['Date','datetime'], 'name'=>['Product','text'], 'sku'=>['SKU','text'], 'type'=>['Type','text'], 'location'=>['Location','text'], 'quantity_change'=>['Change','integer'], 'quantity_before'=>['Before','integer'], 'quantity_after'=>['After','integer'], 'note'=>['Note','text']];
                $metrics = ['quantity_change'=>'Net unit change'];
                break;
            case 'purchases':
                $payments = SupplierPayment::query()->selectRaw('supplier_invoice_id, SUM(amount_cents) as paid')->groupBy('supplier_invoice_id');
                $query = SupplierInvoice::query()->where('supplier_invoices.business_id', Tenant::businessId())
                    ->when($this->reportBranchId, fn ($query) => $query->where('supplier_invoices.branch_id', $this->reportBranchId))
                    ->join('suppliers', 'suppliers.id', '=', 'supplier_invoices.supplier_id')
                    ->leftJoinSub($payments, 'invoice_payments', 'invoice_payments.supplier_invoice_id', '=', 'supplier_invoices.id')
                    ->select('supplier_invoices.id', 'reference', 'supplied_at', 'due_at', 'suppliers.name')
                    ->selectRaw('total_cents / 100.0 as total, COALESCE(invoice_payments.paid, 0) / 100.0 as paid, (total_cents - COALESCE(invoice_payments.paid, 0)) / 100.0 as balance')
                    ->orderByDesc('supplied_at')->orderByDesc('supplier_invoices.id');
                $this->period($query, 'supplied_at', $from, $to);
                $this->search($query, ['reference', 'suppliers.name'], $search);
                $columns = ['reference'=>['Invoice','text'], 'name'=>['Supplier','text'], 'supplied_at'=>['Supplied','date'], 'due_at'=>['Due','date'], 'total'=>['Invoice total','money'], 'paid'=>['Paid to date','money'], 'balance'=>['Outstanding','money']];
                $metrics = ['total'=>'Purchases', 'paid'=>'Paid to date', 'balance'=>'Outstanding'];
                break;
            default:
                [$query, $columns, $metrics, $currentStock] = (new OperationalReports($this->reportBranchId, $from, $to, $search))->build($report, $user->canViewProfit());
        }

        if (($data['format'] ?? '') === 'csv') {
            return response()->streamDownload(function () use ($query, $columns) {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");
                fputcsv($file, array_column($columns, 0));
                foreach ($query->cursor() as $row) {
                    $values = [];
                    foreach ($columns as $key => [$label, $type]) {
                        $value = $this->format($row->{$key}, $type, false);
                        // Prevent spreadsheet formulas in user-entered text fields.
                        $values[] = $type === 'text' && preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value;
                    }
                    fputcsv($file, $values);
                }
                fclose($file);
            }, "tiwipos-{$report}-{$from}-{$to}.csv", ['Content-Type'=>'text/csv; charset=UTF-8']);
        }

        $totalsQuery = DB::query()->fromSub((clone $query)->reorder(), 'report_rows')->selectRaw('COUNT(*) as row_count');
        foreach ($metrics as $key => $label) {
            $totalsQuery->selectRaw("COALESCE(SUM({$key}), 0) as {$key}");
        }
        $totals = $totalsQuery->first();
        $rows = $query->paginate(25)->withQueryString();
        $formatValue = fn ($value, $type) => $this->format($value, $type);

        return view('pages.reports', compact('reportToday', 'reportTimezone', 'asAt', 'branches', 'branchFilter', 'reportBranchName', 'reports', 'report', 'from', 'to', 'search', 'currentStock', 'columns', 'metrics', 'totals', 'rows', 'formatValue'));
    }

    private function branchScope($query, string $table)
    {
        return $query->withoutGlobalScope('branch')->where($table.'.business_id', Tenant::businessId())
            ->when($this->reportBranchId, fn ($query) => $query->where($table.'.branch_id', $this->reportBranchId));
    }

    private function sales()
    {
        return $this->branchScope(Sale::query(), 'sales')->where('sales.status', 'completed')
            ->when(!auth()->user()->canViewAllSales(), fn ($query) => $query->where('sales.user_id', auth()->id()));
    }

    private function period($query, string $column, string $from, string $to): void
    {
        if ($column === 'supplied_at') {
            $query->where($column, '>=', $from)->where($column, '<', Carbon::parse($to)->addDay()->toDateString());
            return;
        }
        $query->where($column, '>=', Carbon::parse($from)->startOfDay())
            ->where($column, '<', Carbon::parse($to)->addDay()->startOfDay());
    }

    private function search($query, array $columns, string $term): void
    {
        if ($term !== '') {
            $query->where(function ($query) use ($columns, $term) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }
    }

    private function format($value, string $type, bool $display = true): string
    {
        if ($value === null || $value === '') return $type === 'text' && $display ? '—' : '';
        return match ($type) {
            'money' => ($display ? 'KES ' : '').number_format((float) $value, 2, '.', $display ? ',' : ''),
            'integer' => number_format((float) $value, 0, '.', $display ? ',' : ''),
            'date' => Carbon::parse($value)->format('Y-m-d'),
            'datetime' => Carbon::parse($value)->format('Y-m-d H:i'),
            default => (string) $value,
        };
    }
}
