<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SubCategoryStockTotals
{
    public static function productBuckets(?int $branchId): array
    {
        $products = Product::query()
            ->select('id', 'name', 'category_id', 'stock_alert')
            ->with('category:id,parent_id')
            ->withSum(['stocks as stock_on_hand' => function ($query) use ($branchId) {
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            }], 'quantity')
            ->get();

        $productsBySubCategory = $products
            ->filter(fn (Product $product) => $product->category && ! empty($product->category->parent_id))
            ->groupBy('category_id');

        $legacyProductsByName = $products
            ->reject(fn (Product $product) => $product->category && ! empty($product->category->parent_id))
            ->groupBy(fn (Product $product) => self::nameKey($product->name));

        return [$productsBySubCategory, $legacyProductsByName];
    }

    public static function rowsFor(Category $subCategory, Collection $productsBySubCategory, Collection $legacyProductsByName): Collection
    {
        return $productsBySubCategory
            ->get($subCategory->id, collect())
            ->concat($legacyProductsByName->get(self::nameKey($subCategory->name), collect()));
    }

    private static function nameKey(?string $name): string
    {
        return Str::lower(trim((string) $name));
    }
}
