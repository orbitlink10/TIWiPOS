<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Display the application home page.
     */
    public function index()
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        $monthSales = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$monthStart, now()])
            ->sum('total');

        $weekSales = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$weekStart, now()])
            ->sum('total');

        $todaySales = Sale::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->sum('total');

        $outOfStock = Product::whereRaw('(select coalesce(sum(quantity),0) from product_stocks where product_id = products.id) <= 0')->count();

        $lowStock = Product::whereRaw('(select coalesce(sum(quantity),0) from product_stocks where product_id = products.id) > 0')
            ->whereRaw('(select coalesce(sum(quantity),0) from product_stocks where product_id = products.id) <= coalesce(stock_alert,0)')
            ->count();

        $todayProfit = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', $today)
            ->selectRaw('coalesce(sum(sale_items.subtotal - products.cost * sale_items.quantity),0) as profit')
            ->value('profit') ?? 0;

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

        return view('dashboard', compact('stats'));
    }
}
