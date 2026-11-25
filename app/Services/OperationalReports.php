<?php

namespace App\Services;

use App\Models\User;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OperationalReports
{
    public function __construct(private ?int $branchId, private string $from, private string $to, private string $search = '') {}

    public static function catalog(User $user): array
    {
        if (!$user->canViewAllSales()) return [];
        $reports = [
            'summary' => ['Summary report', 'Sales', 'Completed sales, VAT, installation revenue and recorded product cost for the selected period.'],
            'sales-outlet' => ['Sales by branch', 'Sales', 'Completed receipts and revenue by branch.'],
            'daily-sales-stores' => ['Daily sales by branch', 'Sales', 'Daily receipt counts and sales totals for each branch.'],
            'monthly-store-performance' => ['Monthly branch performance', 'Sales', 'Completed receipts and revenue by month and branch.'],
            'sales-customer' => ['Sales by customer', 'Sales', 'Customers grouped by recorded name and phone. Walk-in sales are grouped together.'],
            'sales-user-store' => ['Sales by cashier and branch', 'Sales', 'Sales attributed to their original cashier.'],
            'sales-user-store-item' => ['Cashier sales by item', 'Sales', 'Product quantities and revenue by cashier and branch; excludes installation and VAT.'],
            'store-item-sales' => ['Branch sales by item', 'Sales', 'Product quantities and revenue by branch; excludes installation and VAT.'],
            'sales-supplier' => ['Sales by supplier', 'Sales', 'Product revenue grouped by the product’s current supplier assignment.'],
            'vat-analysis' => ['VAT analysis', 'Sales', 'Recorded VAT and product sales by day. Installation is shown separately.'],
        ];
        if ($user->canViewProfit()) {
            $reports['profit-loss'] = ['Gross profit by branch', 'Profit', 'Product and installation revenue less recorded product cost. Excludes VAT and operating expenses; this is not an accounting profit-and-loss statement.'];
        }
        if ($user->canAccessAbility('view_stock')) {
            $reports += [
                'price-list' => ['Product price list', 'Inventory', 'Current selling prices and product categories.'],
                'stock-vendor' => ['Stock by supplier', 'Inventory', 'Current stock by product and current supplier assignment, including inactive products.'],
                'stock-supplier-summary' => ['Supplier stock summary', 'Inventory', 'Current quantities by supplier, including inactive products.'],
                'stock-period-counts' => ['Completed stock counts', 'Inventory', 'Saved opening, expected and counted quantities from completed stock periods.'],
                'stock-period-variance' => ['Periodic stock variance', 'Inventory', 'Non-zero variances from completed stock periods, valued at each period’s saved unit cost.'],
                'day-close' => ['Shift closing summary', 'Cashier shifts', 'Shifts opened in the selected period, with live open-shift totals and saved closing totals.'],
                'shift-reconciliation' => ['Shift reconciliation', 'Cashier shifts', 'Closed shifts, counted cash, cash variance and verification status. Filtered by closing date.'],
                'staff-accountability' => ['Cashier accountability', 'Cashier shifts', 'Completed sales and recorded cash/mobile collections by original cashier and branch.'],
            ];
        }
        if ($user->canAccessAbility('manage_catalog') && $user->canViewFinancials()) {
            $reports += [
                'purchases-item' => ['Purchases by item', 'Purchasing', 'Ordered quantities and cost by recorded invoice item.'],
                'supplier-ordered-received' => ['Ordered vs received', 'Purchasing', 'Invoice quantities ordered, received to date and outstanding. Filtered by invoice date.'],
                'supplier-performance' => ['Supplier delivery performance', 'Purchasing', 'Invoice counts, received quantities and outstanding units by supplier. Filtered by invoice date.'],
                'grn-register' => ['Goods received register', 'Purchasing', 'Posted delivery receipts and received quantities, filtered by receiving date.'],
                'supplier-outstanding' => ['Outstanding supplier invoices', 'Purchasing', 'Unpaid invoices supplied in the selected period, with balances as of now.'],
                'supplier-statements' => ['Supplier statement activity', 'Purchasing', 'Invoice debits and payment credits dated within the selected period. Net activity excludes opening balances.'],
                'supplier-payments' => ['Supplier payments', 'Purchasing', 'Payments to suppliers by payment date and method.'],
            ];
        }
        return $reports;
    }

    private function scoped(string $table, string $alias): Builder
    {
        return DB::table($table.' as '.$alias)->where($alias.'.business_id', Tenant::businessId())
            ->when($this->branchId, fn ($q) => $q->where($alias.'.branch_id', $this->branchId));
    }

    private function dated(Builder $query, string $column, bool $dateOnly = false): Builder
    {
        return $dateOnly ? $query->where($column, '>=', $this->from)->where($column, '<', Carbon::parse($this->to)->addDay()->toDateString())
            : $query->where($column, '>=', Carbon::parse($this->from)->startOfDay())
                ->where($column, '<', Carbon::parse($this->to)->addDay()->startOfDay());
    }

    private function search(Builder $query, array $columns): void
    {
        if ($this->search !== '') $query->where(function ($q) use ($columns) {
            foreach ($columns as $column) $q->orWhere($column, 'like', '%'.$this->search.'%');
        });
    }

    private function sales(): Builder
    {
        return $this->dated($this->scoped('sales', 's')->where('s.status', 'completed'), 's.created_at');
    }

    private function invoiceItems(): Builder
    {
        return $this->dated($this->scoped('supplier_invoices', 'i'), 'i.supplied_at', true)
            ->join('suppliers as v', 'v.id', '=', 'i.supplier_id')
            ->join('supplier_invoice_items as l', 'l.supplier_invoice_id', '=', 'i.id');
    }

    /** Each definition returns a query, typed columns, sum metrics and snapshot flag. */
    public function build(string $report, bool $canViewCost): array
    {
        $money = fn ($label) => [$label, 'money'];
        $text = fn ($label) => [$label, 'text'];
        $count = fn ($label) => [$label, 'integer'];
        $snapshot = false;
        $columns = $metrics = [];

        switch ($report) {
            case 'summary':
            case 'profit-loss':
                $costs = DB::table('sale_items as l')->join('products as p', 'p.id', '=', 'l.product_id')
                    ->whereIn('l.sale_id', $this->sales()->select('s.id'))
                    ->selectRaw('l.sale_id, SUM(l.quantity * COALESCE(l.unit_cost, p.cost, 0)) as cost')->groupBy('l.sale_id');
                $query = $this->sales()->join('branches as b', 'b.id', '=', 's.branch_id')->leftJoinSub($costs, 'c', 'c.sale_id', '=', 's.id');
                $this->search($query, ['b.name']);
                if ($report === 'profit-loss') {
                    $query->select('b.id', 'b.name')->groupBy('b.id', 'b.name')->orderBy('b.name');
                    $columns['name'] = $text('Branch');
                }
                $query->selectRaw('COUNT(*) as receipts, COALESCE(SUM(s.subtotal),0) as revenue, COALESCE(SUM(s.installation_amount),0) as installation, COALESCE(SUM(s.tax),0) as vat, COALESCE(SUM(s.total),0) as total');
                $columns += ['receipts' => $count('Receipts'), 'revenue' => $money('Product revenue'), 'installation' => $money('Installation'), 'vat' => $money('VAT'), 'total' => $money('Sales incl. VAT')];
                $metrics = ['receipts'=>'Receipts', 'total'=>'Sales incl. VAT'];
                if ($canViewCost) {
                    $query->selectRaw('COALESCE(SUM(c.cost),0) as cost, COALESCE(SUM(s.subtotal + s.installation_amount - COALESCE(c.cost,0)),0) as gross_profit');
                    $columns += ['cost'=>$money('Recorded product cost'), 'gross_profit'=>$money('Gross trading profit')];
                    $metrics['cost'] = 'Recorded product cost';
                    $metrics['gross_profit'] = 'Gross trading profit';
                }
                break;
            case 'sales-outlet':
            case 'daily-sales-stores':
            case 'monthly-store-performance':
            case 'sales-user-store':
            case 'sales-customer':
            case 'vat-analysis':
            case 'staff-accountability':
                $query = $this->sales()->join('branches as b', 'b.id', '=', 's.branch_id')->leftJoin('users as u', 'u.id', '=', 's.user_id');
                $groups = ['b.id', 'b.name'];
                $query->select('b.name as branch');
                $columns['branch'] = $text('Branch');
                if ($report === 'daily-sales-stores') {
                    $query->selectRaw('DATE(s.created_at) as day')->groupByRaw('DATE(s.created_at)')->orderByDesc('day');
                    $columns = ['day'=>$text('Date')] + $columns;
                }
                if ($report === 'monthly-store-performance') {
                    $query->selectRaw('SUBSTR(CAST(s.created_at AS CHAR),1,7) as month')->groupByRaw('SUBSTR(CAST(s.created_at AS CHAR),1,7)')->orderByDesc('month');
                    $columns = ['month'=>$text('Month')] + $columns;
                }
                if (in_array($report, ['sales-user-store','staff-accountability'])) {
                    $query->addSelect('u.name as cashier'); $groups = array_merge($groups, ['s.user_id', 'u.name']);
                    $columns['cashier'] = $text('Cashier');
                }
                if ($report === 'sales-customer') {
                    $query->selectRaw("COALESCE(NULLIF(s.customer_name,''),'Walk-in') as customer")->addSelect('s.customer_phone');
                    $groups = array_merge($groups, ['s.customer_name','s.customer_phone']);
                    $columns += ['customer'=>$text('Customer'), 'customer_phone'=>$text('Phone')];
                }
                if ($report === 'vat-analysis') {
                    $query->selectRaw('DATE(s.created_at) as day, SUM(CASE WHEN s.tax > 0 THEN s.subtotal ELSE 0 END) as vat_sales, SUM(CASE WHEN s.tax = 0 THEN s.subtotal ELSE 0 END) as no_vat_sales')->groupByRaw('DATE(s.created_at)')->orderByDesc('day');
                    $columns = ['day'=>$text('Date')] + $columns + ['vat_sales'=>$money('Product sales with VAT'), 'no_vat_sales'=>$money('Product sales without VAT')];
                }
                if ($report === 'staff-accountability') {
                    $payments = $this->scoped('payments','p')->whereIn('p.sale_id', $this->sales()->select('s.id'))
                        ->selectRaw("p.sale_id, SUM(CASE WHEN p.method='cash' THEN p.amount ELSE 0 END) as cash, SUM(CASE WHEN p.method='mobile' THEN p.amount ELSE 0 END) as mobile")->groupBy('p.sale_id');
                    $query->leftJoinSub($payments,'pay','pay.sale_id','=','s.id')->selectRaw('SUM(COALESCE(pay.cash,0)) as cash, SUM(COALESCE(pay.mobile,0)) as mobile');
                    $columns += ['cash'=>$money('Cash'), 'mobile'=>$money('Mobile')];
                }
                $query->selectRaw('COUNT(*) as receipts, SUM(s.subtotal) as revenue, SUM(s.tax) as vat, SUM(s.installation_amount) as installation, SUM(s.total) as total')
                    ->groupBy(...$groups)->orderBy('b.name');
                $this->search($query, ['b.name','u.name','s.customer_name','s.customer_phone']);
                $columns += ['receipts'=>$count('Receipts'), 'revenue'=>$money('Product revenue'), 'vat'=>$money('VAT'), 'installation'=>$money('Installation'), 'total'=>$money('Sales incl. VAT')];
                $metrics = ['receipts'=>'Receipts', 'total'=>'Sales incl. VAT', 'vat'=>'VAT'];
                if ($report === 'staff-accountability') $metrics += ['cash'=>'Cash', 'mobile'=>'Mobile'];
                break;
            case 'sales-user-store-item':
            case 'store-item-sales':
            case 'sales-supplier':
                $query = $this->sales()->join('sale_items as l','l.sale_id','=','s.id')->join('products as p','p.id','=','l.product_id')
                    ->join('branches as b','b.id','=','s.branch_id')->leftJoin('users as u','u.id','=','s.user_id')->leftJoin('suppliers as v','v.id','=','p.supplier_id');
                if ($report === 'sales-supplier') {
                    $query->selectRaw("COALESCE(v.name,'Unassigned') as supplier")->groupBy('v.id','v.name')->orderBy('supplier');
                    $columns = ['supplier'=>$text('Current supplier')];
                } else {
                    $query->select('b.name as branch','p.name','p.sku')->groupBy('b.id','b.name','p.id','p.name','p.sku')->orderBy('b.name')->orderBy('p.name');
                    $columns = ['branch'=>$text('Branch'), 'name'=>$text('Product'), 'sku'=>$text('SKU')];
                    if ($report === 'sales-user-store-item') {
                        $query->addSelect('u.name as cashier')->groupBy('s.user_id','u.name'); $columns['cashier'] = $text('Cashier');
                    }
                }
                $query->selectRaw('SUM(l.quantity) as quantity, SUM(l.subtotal) as revenue');
                $this->search($query, ['b.name','p.name','p.sku','u.name','v.name']);
                $columns += ['quantity'=>$count('Units sold'), 'revenue'=>$money('Product revenue')]; $metrics = ['quantity'=>'Units sold', 'revenue'=>'Product revenue'];
                if ($canViewCost) {
                    $query->selectRaw('SUM(l.subtotal - l.quantity * COALESCE(l.unit_cost,p.cost,0)) as gross_profit');
                    $columns['gross_profit'] = $money('Gross product profit'); $metrics['gross_profit'] = 'Gross product profit';
                }
                break;
            case 'price-list':
            case 'stock-vendor':
            case 'stock-supplier-summary':
                $snapshot = true;
                $stock = $this->scoped('product_stocks','st')->selectRaw('st.product_id, SUM(st.quantity) as quantity')->groupBy('st.product_id');
                $query = DB::table('products as p')->where('p.business_id',Tenant::businessId())->leftJoinSub($stock,'st','st.product_id','=','p.id')
                    ->leftJoin('suppliers as v','v.id','=','p.supplier_id')->leftJoin('categories as c','c.id','=','p.category_id');
                $this->search($query, ['p.name','p.sku','v.name','c.name']);
                if ($report === 'stock-supplier-summary') {
                    $query->selectRaw("COALESCE(v.name,'Unassigned') as supplier, COUNT(*) as products, SUM(COALESCE(st.quantity,0)) as quantity")->groupBy('v.id','v.name')->orderBy('supplier');
                    $columns = ['supplier'=>$text('Current supplier'), 'products'=>$count('Products'), 'quantity'=>$count('Units on hand')]; $metrics = ['quantity'=>'Units on hand'];
                    if ($canViewCost) { $query->selectRaw('SUM(COALESCE(st.quantity,0)*COALESCE(p.cost,0)) as stock_value'); $columns['stock_value']=$money('Stock value at cost'); $metrics['stock_value']='Stock value at cost'; }
                } else {
                    $query->select('p.name','p.sku','c.name as category','p.price','p.is_active')->selectRaw("COALESCE(v.name,'Unassigned') as supplier, COALESCE(st.quantity,0) as quantity")->orderBy('p.name')->orderBy('p.id');
                    $columns = ['name'=>$text('Product'), 'sku'=>$text('SKU'), 'category'=>$text('Category'), 'supplier'=>$text('Current supplier'), 'price'=>$money('Selling price'), 'is_active'=>$count('Active (1=yes)'), 'quantity'=>$count('Units on hand')]; $metrics=['quantity'=>'Units on hand'];
                    if ($canViewCost) { $query->addSelect('p.cost')->selectRaw('COALESCE(st.quantity,0)*COALESCE(p.cost,0) as stock_value'); $columns += ['cost'=>$money('Current cost'), 'stock_value'=>$money('Stock value at cost')]; $metrics['stock_value']='Stock value at cost'; }
                }
                break;
            case 'stock-period-counts':
            case 'stock-period-variance':
                $query = $this->dated($this->scoped('stock_periods','sp')->where('sp.status','closed'), 'sp.ends_on', true)
                    ->join('stock_period_lines as l','l.stock_period_id','=','sp.id')->join('branches as b','b.id','=','sp.branch_id')
                    ->select('sp.id as period','sp.starts_on','sp.ends_on','b.name as branch','l.product_name','l.sku','l.opening_quantity','l.expected_quantity','l.counted_quantity','l.variance')
                    ->orderByDesc('sp.ends_on')->orderBy('sp.id')->orderBy('l.product_name');
                if ($report === 'stock-period-variance') $query->where('l.variance','!=',0);
                $this->search($query,['l.product_name','l.sku','b.name']);
                $columns=['period'=>$count('Period #'),'starts_on'=>['From','date'],'ends_on'=>['To','date'],'branch'=>$text('Branch'),'product_name'=>$text('Product'),'sku'=>$text('SKU'),'opening_quantity'=>$count('Opening'),'expected_quantity'=>$count('Expected'),'counted_quantity'=>$count('Counted'),'variance'=>$count('Unit variance')];
                $metrics=['variance'=>'Net unit variance'];
                if ($canViewCost) { $query->selectRaw('l.variance*l.unit_cost as variance_value'); $columns['variance_value']=$money('Variance at saved cost'); $metrics['variance_value']='Net variance at saved cost'; }
                break;
            case 'day-close':
            case 'shift-reconciliation':
                $sales = $this->scoped('sales','s')->where('s.status','completed')->selectRaw('s.cashier_shift_id, COUNT(*) as receipts, SUM(s.total) as total')->groupBy('s.cashier_shift_id');
                $cash = $this->scoped('payments','p')->join('sales as s','s.id','=','p.sale_id')->where('s.status','completed')->where('p.method','cash')
                    ->where('s.business_id',Tenant::businessId())->selectRaw('s.cashier_shift_id, SUM(p.amount) as cash')->groupBy('s.cashier_shift_id');
                $query = $this->dated($this->scoped('cashier_shifts','sh'), $report==='day-close' ? 'sh.opened_at' : 'sh.closed_at')
                    ->join('branches as b','b.id','=','sh.branch_id')->leftJoin('users as u','u.id','=','sh.user_id')
                    ->leftJoinSub($sales,'s','s.cashier_shift_id','=','sh.id')->leftJoinSub($cash,'p','p.cashier_shift_id','=','sh.id')
                    ->select('sh.id','b.name as branch','u.name as cashier','sh.status','sh.opened_at','sh.closed_at')
                    ->selectRaw("CASE WHEN sh.status='closed' AND sh.sales_count IS NOT NULL THEN sh.sales_count ELSE COALESCE(s.receipts,0) END as receipts,
                        CASE WHEN sh.status='closed' AND sh.sales_total_cents IS NOT NULL THEN sh.sales_total_cents/100.0 ELSE COALESCE(s.total,0) END as total,
                        sh.opening_cash_cents/100.0 as opening_cash,
                        CASE WHEN sh.status='closed' AND sh.expected_cash_cents IS NOT NULL THEN sh.expected_cash_cents/100.0 ELSE sh.opening_cash_cents/100.0+COALESCE(p.cash,0) END as expected_cash,
                        sh.counted_cash_cents/100.0 as counted_cash, sh.cash_variance_cents/100.0 as variance,
                        CASE WHEN sh.verified_at IS NOT NULL THEN 'Verified' WHEN sh.status='closed' THEN 'Pending verification' ELSE 'Open' END as verification")
                    ->orderByDesc('sh.opened_at')->orderByDesc('sh.id');
                if ($report==='shift-reconciliation') $query->where('sh.status','closed');
                $this->search($query,['b.name','u.name']);
                $columns=['id'=>$count('Shift #'),'branch'=>$text('Branch'),'cashier'=>$text('Cashier'),'status'=>$text('Status'),'verification'=>$text('Verification'),'opened_at'=>['Opened','datetime'],'closed_at'=>['Closed','datetime'],'receipts'=>$count('Receipts'),'total'=>$money('Sales'),'opening_cash'=>$money('Opening cash'),'expected_cash'=>$money('Expected cash'),'counted_cash'=>$money('Counted cash'),'variance'=>$money('Cash variance')];
                $metrics=['receipts'=>'Receipts','total'=>'Sales','expected_cash'=>'Expected cash','variance'=>'Cash variance'];
                break;
            case 'purchases-item':
            case 'supplier-ordered-received':
            case 'supplier-performance':
                $query = $this->invoiceItems();
                $this->search($query,['v.name','i.reference','l.product_name']);
                if ($report==='supplier-performance') {
                    $query->select('v.name as supplier')->selectRaw('COUNT(DISTINCT i.id) as invoices')->groupBy('v.id','v.name')->orderBy('v.name');
                    $columns=['supplier'=>$text('Supplier'),'invoices'=>$count('Invoices')];
                } elseif ($report==='purchases-item') {
                    $query->select('l.product_name')->groupBy('l.product_id','l.product_name')->orderBy('l.product_name');
                    $columns=['product_name'=>$text('Recorded product')];
                } else {
                    $query->select('i.reference','i.supplied_at','v.name as supplier','l.product_name')->groupBy('i.id','i.reference','i.supplied_at','v.name','l.id','l.product_name')->orderByDesc('i.supplied_at')->orderBy('i.id')->orderBy('l.id');
                    $columns=['reference'=>$text('Invoice'),'supplied_at'=>['Supplied','date'],'supplier'=>$text('Supplier'),'product_name'=>$text('Recorded product')];
                }
                $query->selectRaw('SUM(l.quantity) as ordered, SUM(l.received_quantity) as received, SUM(l.quantity-l.received_quantity) as pending, SUM(l.quantity*l.unit_cost_cents)/100.0 as ordered_cost');
                $columns+=['ordered'=>$count('Ordered units'),'received'=>$count('Received to date'),'pending'=>$count('Outstanding units'),'ordered_cost'=>$money('Ordered cost')];
                $metrics=['ordered'=>'Ordered units','received'=>'Received to date','pending'=>'Outstanding units','ordered_cost'=>'Ordered cost'];
                break;
            case 'grn-register':
                $lines = DB::table('purchase_receipt_lines as l')->join('supplier_invoice_items as i','i.id','=','l.supplier_invoice_item_id')
                    ->selectRaw('l.purchase_receipt_id, SUM(l.quantity) as quantity, SUM(l.quantity*i.unit_cost_cents)/100.0 as cost')->groupBy('l.purchase_receipt_id');
                $query = $this->dated($this->scoped('purchase_receipts','r'), 'r.received_at', true)
                    ->join('supplier_invoices as i','i.id','=','r.supplier_invoice_id')->join('suppliers as v','v.id','=','i.supplier_id')
                    ->leftJoin('users as u','u.id','=','r.user_id')->leftJoinSub($lines,'l','l.purchase_receipt_id','=','r.id')
                    ->select('r.id','r.received_at','i.reference','v.name as supplier','u.name as receiver')->selectRaw('COALESCE(l.quantity,0) as quantity, COALESCE(l.cost,0) as cost')->orderByDesc('r.received_at')->orderByDesc('r.id');
                $this->search($query,['i.reference','v.name','u.name']);
                $columns=['id'=>$count('GRN #'),'received_at'=>['Received','date'],'reference'=>$text('Invoice'),'supplier'=>$text('Supplier'),'receiver'=>$text('Received by'),'quantity'=>$count('Received units'),'cost'=>$money('Received cost')];
                $metrics=['quantity'=>'Received units','cost'=>'Received cost'];
                break;
            case 'supplier-payments':
            case 'supplier-statements':
                $credits = $this->scoped('supplier_invoices','i')->join('supplier_payments as p','p.supplier_invoice_id','=','i.id')->join('suppliers as v','v.id','=','i.supplier_id');
                $this->dated($credits,'p.paid_at',true); $this->search($credits,['v.name','i.reference','p.reference']);
                if ($report==='supplier-payments') {
                    $query=$credits->select('p.id','p.paid_at','v.name as supplier','i.reference as invoice','p.method','p.reference')->selectRaw('p.amount_cents/100.0 as amount')->orderByDesc('p.paid_at')->orderByDesc('p.id');
                    $columns=['paid_at'=>['Paid','date'],'supplier'=>$text('Supplier'),'invoice'=>$text('Invoice'),'method'=>$text('Method'),'reference'=>$text('Reference'),'amount'=>$money('Paid')]; $metrics=['amount'=>'Paid'];
                } else {
                    $debits=$this->dated($this->scoped('supplier_invoices','i'),'i.supplied_at',true)->join('suppliers as v','v.id','=','i.supplier_id');
                    $this->search($debits,['v.name','i.reference']);
                    $debits->select('i.supplied_at as day','v.name as supplier','i.reference')->selectRaw("'Invoice' as type, i.total_cents/100.0 as debit, 0 as credit, i.total_cents/100.0 as net");
                    $credits->select('p.paid_at as day','v.name as supplier','i.reference')->selectRaw("'Payment' as type, 0 as debit, p.amount_cents/100.0 as credit, -p.amount_cents/100.0 as net");
                    $query=DB::query()->fromSub($debits->unionAll($credits),'activity')->orderBy('day')->orderBy('supplier')->orderBy('reference')->orderBy('type');
                    $columns=['day'=>['Date','date'],'supplier'=>$text('Supplier'),'reference'=>$text('Invoice'),'type'=>$text('Type'),'debit'=>$money('Invoice debit'),'credit'=>$money('Payment credit'),'net'=>$money('Net activity')]; $metrics=['debit'=>'Invoice debits','credit'=>'Payment credits','net'=>'Net activity'];
                }
                break;
            case 'supplier-outstanding':
                $paid=DB::table('supplier_payments')->selectRaw('supplier_invoice_id, SUM(amount_cents) as paid')->groupBy('supplier_invoice_id');
                $query=$this->dated($this->scoped('supplier_invoices','i'),'i.supplied_at',true)->join('suppliers as v','v.id','=','i.supplier_id')->leftJoinSub($paid,'p','p.supplier_invoice_id','=','i.id')
                    ->whereRaw('i.total_cents > COALESCE(p.paid,0)')->select('v.name as supplier','i.reference','i.supplied_at','i.due_at')
                    ->selectRaw('i.total_cents/100.0 as total, COALESCE(p.paid,0)/100.0 as paid, (i.total_cents-COALESCE(p.paid,0))/100.0 as balance')
                    ->orderBy('i.due_at')->orderBy('i.id');
                $this->search($query,['v.name','i.reference']);
                $columns=['supplier'=>$text('Supplier'),'reference'=>$text('Invoice'),'supplied_at'=>['Supplied','date'],'due_at'=>['Due','date'],'total'=>$money('Invoice total'),'paid'=>$money('Paid to date'),'balance'=>$money('Outstanding')]; $metrics=['total'=>'Invoice totals','paid'=>'Paid to date','balance'=>'Outstanding'];
                break;
            default:
                abort(404);
        }
        return [$query, $columns, $metrics, $snapshot];
    }
}
