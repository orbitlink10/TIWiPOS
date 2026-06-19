<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceVisit;
use App\Models\ServiceWorker;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceVisitController extends Controller
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

    public function edit(ServiceVisit $serviceVisit)
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Delivery tables are missing. Run migrations first.');
        }

        $businessId = Tenant::businessId();
        $branchId = Tenant::branchId();

        $services = Service::with(['workers' => function ($query) use ($branchId) {
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        }])->orderBy('name')->get();

        $workers = ServiceWorker::with('services')
            ->where('is_active', true)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();

        $branches = Branch::query()
            ->where('business_id', $businessId)
            ->orderBy('name')
            ->get();

        $statuses = ServiceVisit::statuses();

        return view('pages.service_visit_edit', compact('serviceVisit', 'services', 'workers', 'branches', 'statuses'));
    }

    public function store(Request $request)
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Delivery tables are missing. Run migrations first.');
        }

        $data = $this->validateVisit($request);
        $service = Service::findOrFail((int) $data['service_id']);
        $worker = ServiceWorker::findOrFail((int) $data['service_worker_id']);

        $this->assertWorkerCanPerformService($service, $worker);

        $customer = $this->resolveCustomer(
            trim((string) $data['customer_name']),
            isset($data['customer_phone']) ? trim((string) $data['customer_phone']) : null
        );

        ServiceVisit::create([
            'customer_id' => $customer?->id,
            'service_id' => $service->id,
            'service_worker_id' => $worker->id,
            'recorded_by_user_id' => auth()->id(),
            'customer_name' => trim((string) $data['customer_name']),
            'customer_phone' => $data['customer_phone'] ?? null,
            'delivery_location' => $data['delivery_location'] ?? null,
            'delivery_reference' => $data['delivery_reference'] ?? null,
            'service_date' => $data['service_date'],
            'service_time' => $data['service_time'] ?? null,
            'price' => $data['price'],
            'status' => $data['status'],
            'completed_at' => $data['status'] === ServiceVisit::STATUS_COMPLETED ? now() : null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('services', ['date' => $data['service_date']])->with('status', 'Delivery recorded successfully.');
    }

    public function update(Request $request, ServiceVisit $serviceVisit)
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Delivery tables are missing. Run migrations first.');
        }

        $data = $this->validateVisit($request);
        $service = Service::findOrFail((int) $data['service_id']);
        $worker = ServiceWorker::findOrFail((int) $data['service_worker_id']);

        $this->assertWorkerCanPerformService($service, $worker);

        $customer = $this->resolveCustomer(
            trim((string) $data['customer_name']),
            isset($data['customer_phone']) ? trim((string) $data['customer_phone']) : null
        );

        $status = $data['status'];
        $completedAt = $status === ServiceVisit::STATUS_COMPLETED
            ? ($serviceVisit->completed_at ?? now())
            : null;

        $serviceVisit->update([
            'customer_id' => $customer?->id,
            'service_id' => $service->id,
            'service_worker_id' => $worker->id,
            'recorded_by_user_id' => auth()->id(),
            'customer_name' => trim((string) $data['customer_name']),
            'customer_phone' => $data['customer_phone'] ?? null,
            'delivery_location' => $data['delivery_location'] ?? null,
            'delivery_reference' => $data['delivery_reference'] ?? null,
            'service_date' => $data['service_date'],
            'service_time' => $data['service_time'] ?? null,
            'price' => $data['price'],
            'status' => $status,
            'completed_at' => $completedAt,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('services', ['date' => $data['service_date']])->with('status', 'Delivery updated successfully.');
    }

    public function status(Request $request, ServiceVisit $serviceVisit)
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Delivery tables are missing. Run migrations first.');
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ServiceVisit::statuses()))],
        ]);

        $completedAt = $data['status'] === ServiceVisit::STATUS_COMPLETED
            ? ($serviceVisit->completed_at ?? now())
            : null;

        $serviceVisit->update([
            'status' => $data['status'],
            'completed_at' => $completedAt,
            'recorded_by_user_id' => auth()->id(),
        ]);

        return redirect()->route('services', ['date' => $serviceVisit->service_date?->toDateString()])->with('status', 'Delivery status updated.');
    }

    private function validateVisit(Request $request): array
    {
        $businessId = Tenant::businessId();
        $branchId = Tenant::branchId();

        return $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'delivery_location' => ['required', 'string', 'max:255'],
            'delivery_reference' => ['nullable', 'string', 'max:255'],
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'service_worker_id' => [
                'required',
                'integer',
                Rule::exists('service_workers', 'id')->where(function ($query) use ($businessId, $branchId) {
                    $query->where('business_id', $businessId);
                    if ($branchId) {
                        $query->where('branch_id', $branchId);
                    }
                }),
            ],
            'service_date' => ['required', 'date'],
            'service_time' => ['nullable', 'date_format:H:i'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_keys(ServiceVisit::statuses()))],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function assertWorkerCanPerformService(Service $service, ServiceWorker $worker): void
    {
        $serviceWorkers = $service->workers();
        $assignedWorkersExist = $serviceWorkers->exists();

        if (! $assignedWorkersExist) {
            // First recorded delivery can establish the initial delivery person mapping.
            $serviceWorkers->syncWithoutDetaching([$worker->id]);
            return;
        }

        $allowed = $serviceWorkers->whereKey($worker->id)->exists();

        if (! $allowed) {
            throw ValidationException::withMessages([
                'service_worker_id' => 'The selected delivery person is not assigned to these goods.',
            ]);
        }
    }

    private function resolveCustomer(string $name, ?string $phone): ?Customer
    {
        if ($name === '' && blank($phone)) {
            return null;
        }

        $query = Customer::query();
        $customer = null;

        if (filled($phone)) {
            $customer = $query->where('phone', $phone)->first();
        }

        if (! $customer && $name !== '') {
            $customer = Customer::query()->where('name', $name)->first();
        }

        if ($customer) {
            $updates = [];
            if ($name !== '' && $customer->name !== $name) {
                $updates['name'] = $name;
            }
            if (filled($phone) && $customer->phone !== $phone) {
                $updates['phone'] = $phone;
            }
            if ($updates !== []) {
                $customer->update($updates);
            }

            return $customer;
        }

        return Customer::create([
            'name' => $name !== '' ? $name : 'Walk-in Customer',
            'phone' => $phone,
            'balance' => 0,
        ]);
    }
}
