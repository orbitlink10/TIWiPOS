<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\Tenant;

class PageController extends Controller
{
    public function stock()
    {
        return view('pages.stock');
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
        $today = now()->toDateString();
        $branchId = Tenant::branchId();

        $todaySalesTotal = \App\Models\Sale::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('total');

        $todayOrders = \App\Models\Sale::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();

        $todayCustomers = \App\Models\Sale::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        $recentSales = \App\Models\Sale::where('status', 'completed')
            ->with(['items.product'])
            ->orderByDesc('created_at')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->limit(5)
            ->get();

        $todayProfit = \App\Models\SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', $today)
            ->when($branchId, fn($q) => $q->where('sales.branch_id', $branchId))
            ->selectRaw('coalesce(sum(sale_items.subtotal - products.cost * sale_items.quantity),0) as profit')
            ->value('profit') ?? 0;

        $profitByProduct = \App\Models\SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', $today)
            ->when($branchId, fn($q) => $q->where('sales.branch_id', $branchId))
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->selectRaw('products.id, products.name, products.sku, SUM(sale_items.quantity) as qty, SUM(sale_items.subtotal) as sales_total, SUM(sale_items.subtotal - products.cost * sale_items.quantity) as profit_total')
            ->orderByDesc('profit_total')
            ->get();

        return view('pages.summary', compact('todaySalesTotal', 'todayOrders', 'todayCustomers', 'recentSales', 'todayProfit', 'profitByProduct'));
    }
}
