<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Support\Tenant;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class CategoryController extends Controller
{
    public function create()
    {
        $categories = Category::with('parent')->withCount('products')->orderBy('name')->get();
        $parentCategories = Category::whereNull('parent_id')->orderBy('name')->pluck('name');

        return view('pages.category_create', compact('categories', 'parentCategories'));
    }

    public function store(Request $request)
    {
        $businessId = Tenant::businessId();
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'category_name' => trim((string) $request->input('category_name')),
        ]);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(function ($query) use ($businessId) {
                    if ($businessId) {
                        $query->where('business_id', $businessId);
                    } else {
                        $query->whereNull('business_id');
                    }
                }),
            ],
            'category_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $categoryName = $data['category_name'] ?? '';
        $parentCategoryId = null;

        if ($categoryName !== '') {
            $parentCategory = $this->findCategoryByName($categoryName, $businessId);

            if (! $parentCategory) {
                $parentCategory = Category::create([
                    'business_id' => $businessId,
                    'name' => $categoryName,
                    'slug' => $this->nextAvailableSlug($this->baseSlug($categoryName)),
                    'description' => null,
                    'parent_id' => null,
                    'is_active' => true,
                ]);
            }

            $parentCategoryId = $parentCategory->id;
        }

        Category::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'slug' => $this->nextAvailableSlug($this->baseSlug($data['name'])),
            'description' => $data['description'] ?? null,
            'parent_id' => $parentCategoryId,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('products.create')->with('status', 'Category added.');
    }

    private function findCategoryByName(string $name, ?int $businessId): ?Category
    {
        $query = Category::withoutGlobalScopes()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        } else {
            $query->whereNull('business_id');
        }

        return $query
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->first();
    }

    private function baseSlug(string $name): string
    {
        $baseSlug = Str::slug($name);

        return $baseSlug !== '' ? $baseSlug : 'category';
    }

    private function nextAvailableSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $index = 2;

        while (Category::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$index++;
        }

        return $slug;
    }

    public function destroy(Request $request, Category $category)
    {
        $redirectTo = $request->input('redirect_to') === 'settings.index' ? 'settings.index' : 'categories.create';
        $productIds = Product::query()->where('category_id', $category->id)->pluck('id');

        if ($productIds->isNotEmpty()) {
            $hasSoldProducts = SaleItem::query()
                ->withoutGlobalScope('branch')
                ->whereIn('product_id', $productIds)
                ->exists();
            if ($hasSoldProducts) {
                return redirect()->route($redirectTo)->with('error', 'Cannot delete this category because one or more products in it already have sales history.');
            }
        }

        try {
            DB::transaction(function () use ($category) {
                Product::query()->where('category_id', $category->id)->delete();
                $category->delete();
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return redirect()->route($redirectTo)->with('error', 'Cannot delete this category because its products are linked to existing records.');
            }

            throw $exception;
        }

        return redirect()->route($redirectTo)->with('status', 'Category and related products deleted successfully.');
    }
}
