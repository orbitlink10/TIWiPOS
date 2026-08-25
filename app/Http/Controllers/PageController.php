<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Tenant;
use Illuminate\Support\Facades\Schema;

class PageController extends Controller
{
    private function visibleSales(Builder $query, string $table = 'sales'): Builder
    {
        $user = auth()->user();

        if ($user && !$user->canViewAllSales()) {
            $query->where($table . '.user_id', $user->id);
        }

        return $query;
    }

    public function stock(Request $request)
    {
        $branchId = Tenant::branchId();
        $search = trim((string) $request->query('q', ''));
        $products = \App\Models\Product::query()
            ->select('id', 'category_id', 'stock_alert')
            ->withSum(['stocks as stock_on_hand' => function ($q) use ($branchId) {
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        }], 'quantity')
            ->get();

        $productsBySubCategory = $products
            ->filter(fn($product) => !empty($product->category_id))
            ->groupBy('category_id');

        $subCategories = \App\Models\Category::query()
            ->whereNotNull('parent_id')
            ->when($search !== '', fn($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($subCategory) use ($productsBySubCategory) {
                $rows = $productsBySubCategory->get($subCategory->id, collect());
                $onHand = (int) $rows->sum(fn($p) => (int) ($p->stock_on_hand ?? 0));
                $reorderAt = (int) $rows->sum(fn($p) => (int) ($p->stock_alert ?? 0));

                return [
                    'sub_category_id' => (int) $subCategory->id,
                    'sub_category_name' => $subCategory->name,
                    'products_count' => $rows->count(),
                    'on_hand' => $onHand,
                    'reorder_at' => $reorderAt,
                ];
            })
            ->sortBy('sub_category_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $outOfStock = $subCategories->filter(fn($row) => $row['on_hand'] <= 0)->count();
        $lowStock = $subCategories->filter(fn($row) => $row['on_hand'] > 0 && $row['reorder_at'] > 0 && $row['on_hand'] <= $row['reorder_at'])->count();
        $totalItems = $subCategories->sum('on_hand');

        return view('pages.stock', compact('subCategories', 'outOfStock', 'lowStock', 'totalItems', 'search'));
    }

    public function sale()
    {
        return app(\App\Http\Controllers\SaleController::class)->create();
    }

    public function products()
    {
        return app(\App\Http\Controllers\ProductController::class)->index();
    }

    public function productCreate()
    {
        return app(\App\Http\Controllers\ProductController::class)->create();
    }

    public function summary()
    {
        $canViewProfit = auth()->user()->canViewProfit();
        $showingOwnSalesOnly = !auth()->user()->canViewAllSales();
        $today = now()->toDateString();
        $branchId = Tenant::branchId();

        $todaySalesTotal = $this->visibleSales(Sale::where('status', 'completed'))
            ->whereDate('created_at', $today)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('total');

        $todayOrders = $this->visibleSales(Sale::where('status', 'completed'))
            ->whereDate('created_at', $today)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();

        $todayCustomers = $this->visibleSales(Sale::where('status', 'completed'))
            ->whereDate('created_at', $today)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        $recentSales = $this->visibleSales(Sale::where('status', 'completed'))
            ->with(['items.product'])
            ->orderByDesc('created_at')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->limit(5)
            ->get();

        $todayProfit = null;
        $profitByProduct = collect();

        if ($canViewProfit) {
            $costExpression = $this->saleItemCostExpression();
            $todayProfit = \App\Models\SaleItem::query()
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->when($showingOwnSalesOnly, fn ($q) => $q->where('sales.user_id', auth()->id()))
                ->where('sales.status', 'completed')
                ->whereDate('sales.created_at', $today)
                ->when($branchId, fn($q) => $q->where('sales.branch_id', $branchId))
                ->selectRaw("coalesce(sum(sale_items.subtotal - ({$costExpression}) * sale_items.quantity),0) as profit")
                ->value('profit') ?? 0;

            $profitByProduct = \App\Models\SaleItem::query()
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->when($showingOwnSalesOnly, fn ($q) => $q->where('sales.user_id', auth()->id()))
                ->where('sales.status', 'completed')
                ->whereDate('sales.created_at', $today)
                ->when($branchId, fn($q) => $q->where('sales.branch_id', $branchId))
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->selectRaw("products.id, products.name, products.sku, SUM(sale_items.quantity) as qty, SUM(sale_items.subtotal) as sales_total, SUM(sale_items.subtotal - ({$costExpression}) * sale_items.quantity) as profit_total")
                ->orderByDesc('profit_total')
                ->get();
        }

        return view('pages.summary', compact('todaySalesTotal', 'todayOrders', 'todayCustomers', 'recentSales', 'todayProfit', 'profitByProduct', 'canViewProfit', 'showingOwnSalesOnly'));
    }

    private function saleItemCostExpression(): string
    {
        return Schema::hasColumn('sale_items', 'unit_cost')
            ? 'coalesce(sale_items.unit_cost, products.cost)'
            : 'products.cost';
    }
}
