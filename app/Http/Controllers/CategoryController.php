<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Support\Tenant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('pages.category_create', compact('categories'));
    }

    public function store(Request $request)
    {
        $businessId = Tenant::businessId();

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
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($businessId) {
                    if ($businessId) {
                        $query->where('business_id', $businessId);
                    } else {
                        $query->whereNull('business_id');
                    }
                }),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $baseSlug = Str::slug($data['name']);
        if ($baseSlug === '') {
            $baseSlug = 'category';
        }

        $slug = $baseSlug;
        $index = 2;
        while ($this->slugExistsForBusiness($slug, $businessId)) {
            $slug = $baseSlug.'-'.$index++;
        }

        try {
            Category::create([
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'A category with this name already exists.']);
        }

        return redirect()->route('products.create')->with('status', 'Category added.');
    }

    private function slugExistsForBusiness(string $slug, ?int $businessId): bool
    {
        $query = Category::withoutGlobalScopes()->where('slug', $slug);

        if ($businessId) {
            $query->where('business_id', $businessId);
        } else {
            $query->whereNull('business_id');
        }

        return $query->exists();
    }
}
