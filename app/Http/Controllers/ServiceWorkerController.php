<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ServiceWorker;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ServiceWorkerController extends Controller
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

    public function edit(ServiceWorker $serviceWorker)
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Salon service tables are missing. Run migrations first.');
        }

        $branches = Branch::query()
            ->where('business_id', Tenant::businessId())
            ->orderBy('name')
            ->get();

        return view('pages.service_worker_edit', compact('serviceWorker', 'branches'));
    }

    public function store(Request $request)
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Salon service tables are missing. Run migrations first.');
        }

        $businessId = Tenant::businessId();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $branchId = $data['branch_id'] ?? Tenant::branchId();

        ServiceWorker::create([
            'name' => $data['name'],
            'title' => $data['title'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'branch_id' => $branchId,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('services')->with('status', 'Stylist added successfully.');
    }

    public function update(Request $request, ServiceWorker $serviceWorker)
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Salon service tables are missing. Run migrations first.');
        }

        $businessId = Tenant::businessId();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $serviceWorker->update([
            'name' => $data['name'],
            'title' => $data['title'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'branch_id' => $data['branch_id'] ?? Tenant::branchId(),
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('services')->with('status', 'Stylist updated successfully.');
    }

    public function status(Request $request, ServiceWorker $serviceWorker)
    {
        if (! $this->serviceDeskSchemaReady()) {
            return redirect()->route('services')->with('error', 'Salon service tables are missing. Run migrations first.');
        }

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $serviceWorker->update([
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()->route('services')->with(
            'status',
            $serviceWorker->is_active ? 'Stylist activated successfully.' : 'Stylist deactivated successfully.'
        );
    }
}
