<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Support\Tenant;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function create(Request $request)
    {
        $categories = Category::with('parent')->withCount('products')->orderBy('name')->get();
        $redirectTo = $request->query('redirect_to') === 'products.create'
            ? 'products.create'
            : 'categories.create';

        return view('pages.category_create', compact('categories', 'redirectTo'));
    }

    public function store(Request $request)
    {
        $businessId = Tenant::businessId();
        $categoryName = trim((string) $request->input('category_name'));
        $subCategoryName = trim((string) $request->input('sub_category_name'));
        $legacyName = trim((string) $request->input('name'));

        if ($categoryName === '' && $legacyName !== '') {
            $categoryName = $legacyName;
        } elseif ($subCategoryName === '' && $legacyName !== '') {
            $subCategoryName = $legacyName;
        }

        $request->merge([
            'category_name' => $categoryName,
            'sub_category_name' => $subCategoryName,
        ]);

        $data = $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:255',
            ],
            'sub_category_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'redirect_to' => ['nullable', 'in:products.create,categories.create'],
        ]);

        $redirectTo = $data['redirect_to'] ?? 'categories.create';
        $categoryName = $data['category_name'];
        $subCategoryName = $data['sub_category_name'] ?? '';

        if ($subCategoryName === '') {
            if ($this->findAnyCategoryByName($categoryName, $businessId)) {
                throw ValidationException::withMessages([
                    'category_name' => 'The category name has already been taken.',
                ]);
            }

            Category::create([
                'business_id' => $businessId,
                'name' => $categoryName,
                'slug' => $this->nextAvailableSlug($this->baseSlug($categoryName)),
                'description' => $data['description'] ?? null,
                'parent_id' => null,
                'is_active' => $request->boolean('is_active'),
            ]);

            return redirect()->route($redirectTo)->with('status', 'Category added.');
        }

        if (Str::lower($categoryName) === Str::lower($subCategoryName)) {
            throw ValidationException::withMessages([
                'sub_category_name' => 'The sub-category must be different from the category.',
            ]);
        }

        $parentCategory = $this->findTopLevelCategoryByName($categoryName, $businessId);

        if (! $parentCategory && $this->findAnyCategoryByName($categoryName, $businessId)) {
            throw ValidationException::withMessages([
                'category_name' => 'The category name has already been taken.',
            ]);
        }

        if ($this->findAnyCategoryByName($subCategoryName, $businessId)) {
            throw ValidationException::withMessages([
                'sub_category_name' => 'The sub-category name has already been taken.',
            ]);
        }

        DB::transaction(function () use ($businessId, $categoryName, $subCategoryName, $data, $request, &$parentCategory) {
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

            Category::create([
                'business_id' => $businessId,
                'name' => $subCategoryName,
                'slug' => $this->nextAvailableSlug($this->baseSlug($subCategoryName)),
                'description' => $data['description'] ?? null,
                'parent_id' => $parentCategory->id,
                'is_active' => $request->boolean('is_active'),
            ]);
        });

        return redirect()->route($redirectTo)->with('status', 'Category and sub-category added.');
    }

    private function findAnyCategoryByName(string $name, ?int $businessId): ?Category
    {
        $query = Category::withoutGlobalScopes()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        } else {
            $query->whereNull('business_id');
        }

        return $query
            ->orderBy('id')
            ->first();
    }

    private function findTopLevelCategoryByName(string $name, ?int $businessId): ?Category
    {
        $query = Category::withoutGlobalScopes()
            ->whereNull('parent_id')
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        } else {
            $query->whereNull('business_id');
        }

        return $query->orderBy('id')->first();
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
