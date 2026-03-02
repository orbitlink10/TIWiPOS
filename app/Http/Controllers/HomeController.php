<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    /**
     * Display the application home page.
     */
    public function index()
    {
        if (! auth()->check()) {
            $stats = [
                'month_name' => now()->format('F Y'),
                'month_sales' => 0,
                'out_of_stock' => 0,
                'low_stock' => 0,
                'this_month' => 0,
                'this_week' => 0,
                'today' => 0,
                'today_profit' => 0,
            ];

            return view('index', compact('stats'));
        }

        $canViewFinancials = auth()->user()->canViewFinancials();
        $canViewProfit = auth()->user()->canViewProfit();
        $branchId = Tenant::branchId();
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();
        $yearStart = now()->copy()->startOfYear();
        $yearEnd = now()->copy()->endOfYear();
        $saleItemCostExpression = $this->saleItemCostExpression();

        $monthSales = null;
        $weekSales = null;
        $todaySales = null;

        if ($canViewFinancials) {
            $monthSales = Sale::query()
                ->withoutGlobalScope('branch')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$monthStart, now()])
                ->sum('total');

            $weekSales = Sale::query()
                ->withoutGlobalScope('branch')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$weekStart, now()])
                ->sum('total');

            $todaySales = Sale::query()
                ->withoutGlobalScope('branch')
                ->where('status', 'completed')
                ->whereDate('created_at', $today)
                ->sum('total');
        }

        $products = Product::query()
            ->select('id', 'stock_alert')
            ->withSum(['stocks as stock_on_hand' => function ($q) use ($branchId) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            }], 'quantity')
            ->get();

        $outOfStock = $products->filter(fn($p) => (int) ($p->stock_on_hand ?? 0) <= 0)->count();
        $lowStock = $products->filter(function ($p) {
            $stock = (int) ($p->stock_on_hand ?? 0);
            $alert = (int) ($p->stock_alert ?? 0);
            return $stock > 0 && $alert > 0 && $stock <= $alert;
        })->count();

        $todayProfit = $canViewProfit
            ? (SaleItem::query()
                ->withoutGlobalScope('branch')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->where('sales.status', 'completed')
                ->whereDate('sales.created_at', $today)
                ->selectRaw('coalesce(sum(sale_items.subtotal - (' . $saleItemCostExpression . ') * sale_items.quantity),0) as profit')
                ->value('profit') ?? 0)
            : null;

        $monthlyPerformance = [];
        if ($canViewFinancials) {
            $salesMonthExpression = $this->monthIndexExpression('created_at');
            $salesByMonthRaw = Sale::query()
                ->withoutGlobalScope('branch')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$yearStart, $yearEnd])
                ->selectRaw("{$salesMonthExpression} as month_index, coalesce(sum(total),0) as total_sales")
                ->groupBy('month_index')
                ->pluck('total_sales', 'month_index');

            $salesByMonth = collect($salesByMonthRaw)
                ->mapWithKeys(fn($total, $month) => [(int) $month => (float) $total]);

            $profitByMonth = collect();
            if ($canViewProfit) {
                $profitMonthExpression = $this->monthIndexExpression('sales.created_at');
                $profitByMonthRaw = SaleItem::query()
                    ->withoutGlobalScope('branch')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->where('sales.status', 'completed')
                    ->whereBetween('sales.created_at', [$yearStart, $yearEnd])
                    ->selectRaw("{$profitMonthExpression} as month_index, coalesce(sum(sale_items.subtotal - ({$saleItemCostExpression}) * sale_items.quantity),0) as total_profit")
                    ->groupBy('month_index')
                    ->pluck('total_profit', 'month_index');

                $profitByMonth = collect($profitByMonthRaw)
                    ->mapWithKeys(fn($total, $month) => [(int) $month => (float) $total]);
            }

            $monthlyPerformance = collect(range(1, 12))
                ->map(function (int $month) use ($salesByMonth, $profitByMonth) {
                    return [
                        'month' => now()->copy()->month($month)->format('M'),
                        'sales' => (float) ($salesByMonth->get($month, 0)),
                        'profit' => (float) ($profitByMonth->get($month, 0)),
                    ];
                })
                ->all();
        }

        $stats = [
            'month_name' => now()->format('F Y'),
            'month_sales' => $monthSales,
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'this_month' => $monthSales,
            'this_week' => $weekSales,
            'today' => $todaySales,
            'today_profit' => $todayProfit,
        ];

        $subscriptionActive = auth()->user()->business?->subscription_status === 'active';

        return view('dashboard', compact('stats', 'subscriptionActive', 'canViewProfit', 'canViewFinancials', 'monthlyPerformance'));
    }

    private function monthIndexExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "cast(strftime('%m', {$column}) as integer)"
            : "month({$column})";
    }

    private function saleItemCostExpression(): string
    {
        return Schema::hasColumn('sale_items', 'unit_cost')
            ? 'coalesce(sale_items.unit_cost, products.cost)'
            : 'products.cost';
    }
}
