<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceCategoryController extends Controller
{
    public function create()
    {
        $categories = ServiceCategory::with('parent')
            ->withCount('services')
            ->orderBy('name')
            ->get();

        return view('pages.service_category_create', compact('categories'));
    }

    public function store(Request $request)
    {
        $businessId = Tenant::businessId();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_categories', 'name')->where(function ($query) use ($businessId) {
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
                Rule::exists('service_categories', 'id')->where(function ($query) use ($businessId) {
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
            $baseSlug = 'service-category';
        }

        ServiceCategory::create([
            'name' => $data['name'],
            'slug' => $this->nextAvailableSlug($baseSlug),
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('services.create')->with('status', 'Service category added.');
    }

    public function destroy(Request $request, ServiceCategory $serviceCategory)
    {
        $redirectTo = $request->input('redirect_to') === 'services'
            ? 'services'
            : 'service-categories.create';

        try {
            DB::transaction(function () use ($serviceCategory) {
                Service::query()->where('service_category_id', $serviceCategory->id)->delete();
                $serviceCategory->delete();
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return redirect()->route($redirectTo)->with('error', 'Cannot delete this service category because it is linked to existing records.');
            }

            throw $exception;
        }

        return redirect()->route($redirectTo)->with('status', 'Service category and related services deleted successfully.');
    }

    private function nextAvailableSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $index = 2;

        while (ServiceCategory::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$index++;
        }

        return $slug;
    }
}
