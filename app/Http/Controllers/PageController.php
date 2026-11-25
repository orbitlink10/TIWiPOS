<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\Tenant;

class PageController extends Controller
{
    public function stock(Request $request)
    {
        $branchId = Tenant::branchId();
        $search = trim((string) $request->query('q', ''));
        $products = \App\Models\Product::query()
            ->select('id', 'category_id', 'stock_alert', 'cost')
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
                    'stock_value' => $rows->sum(fn($p) => (int) ($p->stock_on_hand ?? 0) * (float) ($p->cost ?? 0)),
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

}
