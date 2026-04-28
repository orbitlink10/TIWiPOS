<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceVisit;
use App\Models\ServiceWorker;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    private function serviceDeskSchemaReady(): bool
    {
        $tables = ['service_categories', 'services', 'service_workers', 'service_worker_service', 'service_visits'];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    public function index(Request $request)
    {
        if (! $this->serviceDeskSchemaReady()) {
            $services = collect();
            $serviceCategories = collect();
            $serviceWorkers = collect();
            $serviceVisits = collect();
            $workerBranches = collect();
            $stats = [
                'active_services' => 0,
                'categories_count' => 0,
                'active_workers' => 0,
                'completed_visits' => 0,
                'daily_revenue' => 0,
            ];
            $schemaMissing = true;
            $selectedDate = now()->toDateString();
            $visitStatuses = ServiceVisit::statuses();

            return view('pages.services', compact('services', 'serviceCategories', 'serviceWorkers', 'serviceVisits', 'workerBranches', 'stats', 'schemaMissing', 'selectedDate', 'visitStatuses'));
        }

        $branchId = Tenant::branchId();
        $selectedDate = $request->input('date', now()->toDateString());

        $services = Service::with([
            'category',
            'workers' => function ($query) use ($branchId) {
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
                $query->where('is_active', true)->orderBy('name');
            },
        ])->latest()->get();

        $serviceCategories = ServiceCategory::with('parent')->withCount('services')->orderBy('name')->get();
        $serviceWorkers = ServiceWorker::with(['branch', 'services'])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();

        $serviceVisits = ServiceVisit::with(['service.category', 'worker.branch', 'customer', 'recorder'])
            ->whereDate('service_date', $selectedDate)
            ->orderByRaw('CASE WHEN service_time IS NULL THEN 1 ELSE 0 END')
            ->orderBy('service_time')
            ->orderByDesc('created_at')
            ->get();

        $workerBranches = Branch::query()
            ->where('business_id', Tenant::businessId())
            ->orderBy('name')
            ->get();

        $visitStatuses = ServiceVisit::statuses();
        $schemaMissing = false;

        $stats = [
            'active_services' => Service::query()->where('is_active', true)->count(),
            'categories_count' => $serviceCategories->count(),
            'active_workers' => $serviceWorkers->where('is_active', true)->count(),
            'completed_visits' => $serviceVisits->where('status', ServiceVisit::STATUS_COMPLETED)->count(),
            'daily_revenue' => (float) $serviceVisits
                ->where('status', ServiceVisit::STATUS_COMPLETED)
                ->sum(fn (ServiceVisit $visit) => (float) $visit->price),
        ];

        return view('pages.services', compact('services', 'serviceCategories', 'serviceWorkers', 'serviceVisits', 'workerBranches', 'stats', 'schemaMissing', 'selectedDate', 'visitStatuses'));
    }

    public function create()
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Service catalog tables are missing. Run migrations first.');
        }

        $categories = ServiceCategory::orderBy('name')->get();
        $workers = $this->currentBranchWorkers()->where('is_active', true)->values();

        return view('pages.service_create', compact('categories', 'workers'));
    }

    public function edit(Service $service)
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Service catalog tables are missing. Run migrations first.');
        }

        $categories = ServiceCategory::orderBy('name')->get();
        $workers = $this->currentBranchWorkers()->where('is_active', true)->values();
        $assignedWorkerIds = $service->workers()
            ->when(Tenant::branchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->pluck('service_workers.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('pages.service_edit', compact('service', 'categories', 'workers', 'assignedWorkerIds'));
    }

    public function store(Request $request)
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Service catalog tables are missing. Run migrations first.');
        }

        $businessId = Tenant::businessId();
        $allowedWorkerIds = $this->currentBranchWorkerIds();

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
            'worker_ids' => ['nullable', 'array'],
            'worker_ids.*' => [Rule::in($allowedWorkerIds)],
        ]);

        $service = Service::create([
            'name' => $data['name'],
            'service_category_id' => $data['service_category_id'],
            'duration_minutes' => $data['duration_minutes'],
            'cost' => $data['cost'] ?? 0,
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->syncWorkersForCurrentBranch($service, $data['worker_ids'] ?? []);

        return redirect()->route('services')->with('status', 'Service added successfully.');
    }

    public function update(Request $request, Service $service)
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Service catalog tables are missing. Run migrations first.');
        }

        $businessId = Tenant::businessId();
        $allowedWorkerIds = $this->currentBranchWorkerIds();

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
            'worker_ids' => ['nullable', 'array'],
            'worker_ids.*' => [Rule::in($allowedWorkerIds)],
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

        $this->syncWorkersForCurrentBranch($service, $data['worker_ids'] ?? []);

        return redirect()->route('services')->with('status', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Service catalog tables are missing. Run migrations first.');
        }

        $service->delete();

        return redirect()->route('services')->with('status', 'Service deleted successfully.');
    }

    private function currentBranchWorkers(): Collection
    {
        return ServiceWorker::query()
            ->when(Tenant::branchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();
    }

    private function currentBranchWorkerIds(): array
    {
        return $this->currentBranchWorkers()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function syncWorkersForCurrentBranch(Service $service, array $selectedWorkerIds): void
    {
        $selectedWorkerIds = collect($selectedWorkerIds)->map(fn ($id) => (int) $id)->unique()->values();
        $branchId = Tenant::branchId();

        if ($branchId) {
            $branchWorkerIds = ServiceWorker::query()
                ->where('branch_id', $branchId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            $detachIds = $branchWorkerIds->diff($selectedWorkerIds)->all();
            if ($detachIds !== []) {
                $service->workers()->detach($detachIds);
            }

            if ($selectedWorkerIds->isNotEmpty()) {
                $service->workers()->syncWithoutDetaching($selectedWorkerIds->all());
            }

            return;
        }

        $service->workers()->sync($selectedWorkerIds->all());
    }
}
