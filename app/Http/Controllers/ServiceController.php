<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    private function catalogSchemaReady(): bool
    {
        return Schema::hasTable('service_categories') && Schema::hasTable('services');
    }

    public function index()
    {
        if (! $this->catalogSchemaReady()) {
            $services = collect();
            $serviceCategories = collect();
            $stats = [
                'active_services' => 0,
                'inactive_services' => 0,
                'categories_count' => 0,
                'average_duration' => 0,
                'average_price' => 0,
            ];
            $schemaMissing = true;

            return view('pages.services', compact('services', 'serviceCategories', 'stats', 'schemaMissing'));
        }

        $services = Service::with('category')->latest()->get();
        $serviceCategories = ServiceCategory::with('parent')->withCount('services')->orderBy('name')->get();
        $schemaMissing = false;

        $stats = [
            'active_services' => Service::query()->where('is_active', true)->count(),
            'inactive_services' => Service::query()->where('is_active', false)->count(),
            'categories_count' => $serviceCategories->count(),
            'average_duration' => (int) round((float) (Service::query()->avg('duration_minutes') ?? 0)),
            'average_price' => (float) (Service::query()->avg('price') ?? 0),
        ];

        return view('pages.services', compact('services', 'serviceCategories', 'stats'));
    }

    public function create()
    {
        if (! $this->catalogSchemaReady()) {
            return redirect()->route('services')->with('error', 'Service catalog tables are missing. Run migrations first.');
        }

        $categories = ServiceCategory::orderBy('name')->get();

        return view('pages.service_create', compact('categories'));
    }

    public function edit(Service $service)
    {
        if (! $this->catalogSchemaReady()) {
            return redirect()->route('services')->with('error', 'Service catalog tables are missing. Run migrations first.');
        }

        $categories = ServiceCategory::orderBy('name')->get();

        return view('pages.service_edit', compact('service', 'categories'));
    }

    public function store(Request $request)
    {
        if (! $this->catalogSchemaReady()) {
            return redirect()->route('services')->with('error', 'Service catalog tables are missing. Run migrations first.');
        }

        $businessId = Tenant::businessId();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'name')->where(function ($query) use ($businessId) {
                    if ($businessId) {
                        $query->where('business_id', $businessId);
                    } else {
                        $query->whereNull('business_id');
                    }
                }),
            ],
            'service_category_id' => [
                'required',
                'integer',
                Rule::exists('service_categories', 'id')->where(function ($query) use ($businessId) {
                    if ($businessId) {
                        $query->where('business_id', $businessId);
                    } else {
                        $query->whereNull('business_id');
                    }
                }),
            ],
            'duration_minutes' => 'required|integer|min:1|max:1440',
            'cost' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        Service::create([
            'name' => $data['name'],
            'service_category_id' => $data['service_category_id'],
            'duration_minutes' => $data['duration_minutes'],
            'cost' => $data['cost'] ?? 0,
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('services')->with('status', 'Service added successfully.');
    }

    public function update(Request $request, Service $service)
    {
        if (! $this->catalogSchemaReady()) {
            return redirect()->route('services')->with('error', 'Service catalog tables are missing. Run migrations first.');
        }

        $businessId = Tenant::businessId();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'name')->ignore($service->id)->where(function ($query) use ($businessId) {
                    if ($businessId) {
                        $query->where('business_id', $businessId);
                    } else {
                        $query->whereNull('business_id');
                    }
                }),
            ],
            'service_category_id' => [
                'required',
                'integer',
                Rule::exists('service_categories', 'id')->where(function ($query) use ($businessId) {
                    if ($businessId) {
                        $query->where('business_id', $businessId);
                    } else {
                        $query->whereNull('business_id');
                    }
                }),
            ],
            'duration_minutes' => 'required|integer|min:1|max:1440',
            'cost' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $service->update([
            'name' => $data['name'],
            'service_category_id' => $data['service_category_id'],
            'duration_minutes' => $data['duration_minutes'],
            'cost' => $data['cost'] ?? 0,
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('services')->with('status', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        if (! $this->catalogSchemaReady()) {
            return redirect()->route('services')->with('error', 'Service catalog tables are missing. Run migrations first.');
        }

        $service->delete();

        return redirect()->route('services')->with('status', 'Service deleted successfully.');
    }
}
