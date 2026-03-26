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

    public function stock()
    {
        $branchId = Tenant::branchId();
        $products = \App\Models\Product::query()
            ->select('id', 'category_id', 'stock_alert')
            ->withSum(['stocks as stock_on_hand' => function ($q) use ($branchId) {
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        }], 'quantity')
            ->get();

        $productsByCategory = $products
            ->filter(fn($product) => !empty($product->category_id))
            ->groupBy('category_id');

        $categories = \App\Models\Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($category) use ($productsByCategory) {
                $rows = $productsByCategory->get($category->id, collect());
                $onHand = (int) $rows->sum(fn($p) => (int) ($p->stock_on_hand ?? 0));
                $reorderAt = (int) $rows->sum(fn($p) => (int) ($p->stock_alert ?? 0));

                return [
                    'category_id' => (int) $category->id,
                    'category_name' => $category->name,
                    'products_count' => $rows->count(),
                    'on_hand' => $onHand,
                    'reorder_at' => $reorderAt,
                ];
            })
            ->sortBy('category_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $outOfStock = $categories->filter(fn($row) => $row['on_hand'] <= 0)->count();
        $lowStock = $categories->filter(fn($row) => $row['on_hand'] > 0 && $row['reorder_at'] > 0 && $row['on_hand'] <= $row['reorder_at'])->count();
        $totalItems = $categories->sum('on_hand');

        return view('pages.stock', compact('categories', 'outOfStock', 'lowStock', 'totalItems'));
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

    public function services()
    {
        $serviceStats = [
            'active_services' => 12,
            'signature_packages' => 4,
            'avg_visit_time' => '75 min',
            'median_ticket' => 'KES 4,800',
        ];

        $serviceCategories = collect([
            [
                'name' => 'Hair Studio',
                'count' => 4,
                'lead' => 'Cuts, blow-dries, silk press, and protective styling.',
                'anchor' => 'Silk press, wash and set, signature braids',
            ],
            [
                'name' => 'Nail Bar',
                'count' => 2,
                'lead' => 'Manicure and pedicure services for quick refresh sessions.',
                'anchor' => 'Classic manicure, gel pedicure',
            ],
            [
                'name' => 'Skin Care',
                'count' => 2,
                'lead' => 'Facials and glow treatments built around short appointment blocks.',
                'anchor' => 'Express facial, detox facial',
            ],
            [
                'name' => 'Body Therapy',
                'count' => 2,
                'lead' => 'Massage rituals and body treatments for wellness-focused clients.',
                'anchor' => 'Swedish massage, deep tissue therapy',
            ],
            [
                'name' => 'Waxing',
                'count' => 2,
                'lead' => 'Fast turnover grooming services with simple prep notes.',
                'anchor' => 'Underarm wax, full leg wax',
            ],
        ]);

        $featuredServices = collect([
            ['name' => 'Wash and Blow Dry', 'category' => 'Hair Studio', 'duration' => '45 min', 'price' => 1500, 'status' => 'Fast mover'],
            ['name' => 'Silk Press', 'category' => 'Hair Studio', 'duration' => '90 min', 'price' => 3500, 'status' => 'Premium'],
            ['name' => 'Classic Manicure', 'category' => 'Nail Bar', 'duration' => '40 min', 'price' => 1200, 'status' => 'Steady'],
            ['name' => 'Gel Pedicure', 'category' => 'Nail Bar', 'duration' => '55 min', 'price' => 1800, 'status' => 'Popular'],
            ['name' => 'Express Facial', 'category' => 'Skin Care', 'duration' => '30 min', 'price' => 2200, 'status' => 'Quick add-on'],
            ['name' => 'Deep Tissue Massage', 'category' => 'Body Therapy', 'duration' => '60 min', 'price' => 3500, 'status' => 'Top value'],
            ['name' => 'Full Leg Wax', 'category' => 'Waxing', 'duration' => '35 min', 'price' => 2000, 'status' => 'Repeat booking'],
            ['name' => 'Bridal Glow Package', 'category' => 'Packages', 'duration' => '120 min', 'price' => 8500, 'status' => 'Signature'],
        ]);

        $serviceBundles = collect([
            [
                'name' => 'Glow Reset',
                'price' => 'KES 5,400',
                'items' => 'Express facial, back massage, herbal tea finish',
            ],
            [
                'name' => 'Weekend Prep',
                'price' => 'KES 4,100',
                'items' => 'Wash and blow dry, classic manicure',
            ],
            [
                'name' => 'Bride Preview',
                'price' => 'KES 9,800',
                'items' => 'Silk press, trial makeup slot, gel pedicure',
            ],
            [
                'name' => 'Spa Calm',
                'price' => 'KES 6,300',
                'items' => 'Deep tissue massage, detox facial',
            ],
        ]);

        $serviceStandards = collect([
            'Reserve a 10-minute buffer after massage and facial sessions for room reset.',
            'Capture therapist notes for skin sensitivity, pressure preference, and aftercare.',
            'Use package pricing for bundled appointments instead of manual discounting at checkout.',
            'Keep add-on upsells ready: scalp treatment, paraffin dip, aromatherapy boost.',
        ]);

        return view('pages.services', compact('serviceStats', 'serviceCategories', 'featuredServices', 'serviceBundles', 'serviceStandards'));
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
