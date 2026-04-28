@extends('layouts.app')

@section('title', 'Services')

@section('header')
    <div class="header-row">
        <h1>Salon & Spa Services</h1>
        @if(auth()->user()->canAccessAbility('manage_catalog'))
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btn" href="{{ route('services.create') }}">Add Service</a>
                <a class="btn" href="{{ route('service-categories.create') }}" style="background:#0ea5e9;">Manage Categories</a>
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        .services-stack {
            display: grid;
            gap: 16px;
        }

        .services-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(12, 30, 60, 0.08);
            padding: 22px;
        }

        .services-subgrid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .service-category-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .service-category-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border-radius: 999px;
            border: 1px solid #d9e6f5;
            background: #f8fbff;
            padding: 10px 14px;
            font-weight: 700;
        }

        .service-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .service-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .service-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
        }

        .service-note {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .service-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
            font-size: 14px;
        }

        .service-table th {
            text-align: left;
            padding: 10px;
            background: #f7f7fb;
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .service-table td {
            padding: 12px 10px;
            border-top: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .service-tag {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 800;
        }

        .status-booked { background: #e0f2fe; color: #075985; }
        .status-progress { background: #fef3c7; color: #92400e; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .service-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .service-actions form,
        .service-actions a {
            display: inline-flex;
        }

        .btn-soft {
            border: 1px solid #d8e1ee;
            border-radius: 8px;
            background: #f8fbff;
            color: #0f1b2d;
            padding: 7px 11px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-soft.danger {
            background: #fff1f2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .btn-soft.success {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }

        .btn-soft.warn {
            background: #fff7ed;
            color: #b45309;
            border-color: #fed7aa;
        }

        .service-assignments {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .service-assignments span {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: #eef6ff;
            color: #1d4ed8;
            border: 1px solid #cfe0ff;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 800;
        }

        @media (max-width: 980px) {
            .services-subgrid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @php($canManageCatalog = auth()->user()->canAccessAbility('manage_catalog'))
    @php($completedStatus = \App\Models\ServiceVisit::STATUS_COMPLETED)
    @php($inProgressStatus = \App\Models\ServiceVisit::STATUS_IN_PROGRESS)
    @php($cancelledStatus = \App\Models\ServiceVisit::STATUS_CANCELLED)

    <div class="services-stack">
        <section class="services-card">
            <div class="service-toolbar">
                <div>
                    <h2 style="margin:0;">Service Desk Snapshot</h2>
                    <p class="service-note" style="margin:6px 0 0;">Register stylists, assign them to services, and record daily salon or spa work with customer names and completion status.</p>
                </div>
                <form method="GET" action="{{ route('services') }}" style="display:flex; gap:8px; align-items:end; flex-wrap:wrap;">
                    <label class="service-field" style="min-width:180px;">
                        Service date
                        <input type="date" name="date" value="{{ $selectedDate }}">
                    </label>
                    <button class="btn" type="submit">Load Day</button>
                </form>
            </div>

            @if (session('status'))
                <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if(!empty($schemaMissing))
                <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                    Service desk tables are missing in this environment. Run the latest migrations to enable salon operations.
                </div>
            @endif

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-top:16px;">
                <div class="kpi-card blue">
                    {{ $stats['active_services'] }}
                    <span>Active services</span>
                </div>
                <div class="kpi-card">
                    {{ $stats['categories_count'] }}
                    <span>Service categories</span>
                </div>
                <div class="kpi-card green">
                    {{ $stats['active_workers'] }}
                    <span>Active stylists</span>
                </div>
                <div class="kpi-card amber">
                    {{ $stats['completed_visits'] }}
                    <span>Completed on {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d M') }}</span>
                </div>
                <div class="kpi-card" style="background:#edf7ff; color:#0f5d8d;">
                    KES {{ number_format($stats['daily_revenue'], 2) }}
                    <span>Completed service value</span>
                </div>
            </div>

            <div class="service-category-pills">
                @forelse($serviceCategories as $category)
                    <div class="service-category-pill">
                        <span>{{ $category->name }}</span>
                        <span style="color:var(--muted);">{{ $category->services_count }}</span>
                    </div>
                @empty
                    <div class="service-note">No service categories registered yet.</div>
                @endforelse
            </div>
        </section>

        <div class="services-subgrid">
            <section class="services-card">
                <div class="service-toolbar">
                    <div>
                        <h2 style="margin:0;">Record Service Visit</h2>
                        <p class="service-note" style="margin:6px 0 0;">Capture customer name, assign the service to a stylist, and mark what happened during the day.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('service-visits.store') }}" style="display:grid; gap:12px;">
                    @csrf
                    <div class="service-form-grid">
                        <label class="service-field">
                            Customer name
                            <input name="customer_name" type="text" value="{{ old('customer_name') }}" placeholder="Walk-in customer" required>
                        </label>
                        <label class="service-field">
                            Customer phone
                            <input name="customer_phone" type="text" value="{{ old('customer_phone') }}" placeholder="+254..." inputmode="tel">
                        </label>
                        <label class="service-field">
                            Service
                            <select name="service_id" id="service-visit-service" required>
                                <option value="">Select service</option>
                                @foreach($services->where('is_active', true) as $service)
                                    <option value="{{ $service->id }}" data-price="{{ number_format((float) $service->price, 2, '.', '') }}" data-worker-count="{{ $service->workers->count() }}" @selected((string) old('service_id') === (string) $service->id)>
                                        {{ $service->name }} · {{ $service->duration_minutes }} min
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="service-field">
                            Stylist
                            <select name="service_worker_id" id="service-visit-worker" required>
                                <option value="">Select stylist</option>
                                @foreach($serviceWorkers->where('is_active', true) as $worker)
                                    <option value="{{ $worker->id }}" data-services="{{ $worker->services->pluck('id')->implode(',') }}" @selected((string) old('service_worker_id') === (string) $worker->id)>
                                        {{ $worker->name }}{{ $worker->title ? ' · '.$worker->title : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small id="service-worker-help" style="color:var(--muted); font-weight:500;"></small>
                        </label>
                        <label class="service-field">
                            Visit date
                            <input name="service_date" type="date" value="{{ old('service_date', $selectedDate) }}" required>
                        </label>
                        <label class="service-field">
                            Visit time
                            <input name="service_time" type="time" value="{{ old('service_time') }}">
                        </label>
                        <label class="service-field">
                            Price charged (KES)
                            <input name="price" id="service-visit-price" type="number" min="0" step="0.01" value="{{ old('price') }}" required>
                        </label>
                        <label class="service-field">
                            Status
                            <select name="status" required>
                                @foreach($visitStatuses as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}" @selected(old('status', $completedStatus) === $statusKey)>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <label class="service-field">
                        Notes
                        <textarea name="notes" rows="3" placeholder="Aftercare, room notes, color formula, product used...">{{ old('notes') }}</textarea>
                    </label>

                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <button class="btn" type="submit">Save Visit</button>
                    </div>
                </form>
            </section>

            <section class="services-card">
                <div class="service-toolbar">
                    <div>
                        <h2 style="margin:0;">Register Stylist</h2>
                        <p class="service-note" style="margin:6px 0 0;">Add salon workers first, then assign them to services in the catalog.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('service-workers.store') }}" style="display:grid; gap:12px;">
                    @csrf
                    <div class="service-form-grid">
                        <label class="service-field">
                            Full name
                            <input name="name" type="text" value="{{ old('name') }}" placeholder="Stylist full name" required>
                        </label>
                        <label class="service-field">
                            Title
                            <input name="title" type="text" value="{{ old('title', 'Stylist') }}" placeholder="Stylist, Barber, Therapist" required>
                        </label>
                        <label class="service-field">
                            Phone
                            <input name="phone" type="text" value="{{ old('phone') }}" placeholder="+254..." inputmode="tel">
                        </label>
                        <label class="service-field">
                            Email
                            <input name="email" type="email" value="{{ old('email') }}" placeholder="optional@example.com">
                        </label>
                        <label class="service-field">
                            Branch
                            <select name="branch_id">
                                <option value="">Use active branch</option>
                                @foreach($workerBranches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', session('branch_id')) === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <label class="service-field">
                        Notes
                        <textarea name="notes" rows="3" placeholder="Specialties, room preference, commission notes...">{{ old('notes') }}</textarea>
                    </label>

                    <label style="display:flex; align-items:center; gap:10px; font-weight:700;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') == '1') style="width:18px;height:18px;">
                        Active stylist
                    </label>

                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <button class="btn" type="submit">Add Stylist</button>
                    </div>
                </form>
            </section>
        </div>

        <section class="services-card">
            <div class="service-toolbar">
                <div>
                    <h2 style="margin:0;">Daily Service Register</h2>
                    <p class="service-note" style="margin:6px 0 0;">Track the work booked, in progress, completed, or cancelled for {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d M Y') }}.</p>
                </div>
            </div>

            <div style="overflow:auto;">
                <table class="service-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Stylist</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Recorded By</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serviceVisits as $visit)
                            <tr>
                                <td>{{ $visit->service_time ? substr((string) $visit->service_time, 0, 5) : '-' }}</td>
                                <td>
                                    <div style="font-weight:700;">{{ $visit->customer_name }}</div>
                                    <div class="service-note">{{ $visit->customer_phone ?: 'No phone recorded' }}</div>
                                </td>
                                <td>
                                    <div style="font-weight:700;">{{ $visit->service->name ?? 'Service' }}</div>
                                    <div class="service-note">{{ $visit->service->category->name ?? 'No category' }}</div>
                                </td>
                                <td>
                                    <div style="font-weight:700;">{{ $visit->worker->name ?? 'Unassigned' }}</div>
                                    <div class="service-note">{{ $visit->worker->title ?? '-' }}</div>
                                </td>
                                <td>KES {{ number_format((float) $visit->price, 2) }}</td>
                                <td>
                                    <span class="service-tag {{
                                        $visit->status === \App\Models\ServiceVisit::STATUS_BOOKED ? 'status-booked'
                                        : ($visit->status === \App\Models\ServiceVisit::STATUS_IN_PROGRESS ? 'status-progress'
                                        : ($visit->status === \App\Models\ServiceVisit::STATUS_CANCELLED ? 'status-cancelled' : 'status-completed'))
                                    }}">
                                        {{ $visitStatuses[$visit->status] ?? ucfirst(str_replace('_', ' ', $visit->status)) }}
                                    </span>
                                </td>
                                <td>{{ $visit->recorder->name ?? 'System' }}</td>
                                <td style="text-align:center;">
                                    <div class="service-actions" style="justify-content:center;">
                                        <a class="btn-soft" href="{{ route('service-visits.edit', $visit) }}">Edit</a>
                                        @if($visit->status !== $inProgressStatus)
                                            <form method="POST" action="{{ route('service-visits.status', $visit) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $inProgressStatus }}">
                                                <button type="submit" class="btn-soft warn">Start</button>
                                            </form>
                                        @endif
                                        @if($visit->status !== $completedStatus)
                                            <form method="POST" action="{{ route('service-visits.status', $visit) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $completedStatus }}">
                                                <button type="submit" class="btn-soft success">Complete</button>
                                            </form>
                                        @endif
                                        @if($visit->status !== $cancelledStatus)
                                            <form method="POST" action="{{ route('service-visits.status', $visit) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $cancelledStatus }}">
                                                <button type="submit" class="btn-soft danger">Cancel</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center; color:var(--muted);">No service visits recorded for this day yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="services-card">
            <div class="service-toolbar">
                <div>
                    <h2 style="margin:0;">Stylist Roster</h2>
                    <p class="service-note" style="margin:6px 0 0;">Stylists can be activated or deactivated without removing past visit history.</p>
                </div>
            </div>

            <div style="overflow:auto;">
                <table class="service-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Contact</th>
                            <th>Assigned Services</th>
                            <th>Status</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serviceWorkers as $worker)
                            <tr>
                                <td>{{ $worker->name }}</td>
                                <td>{{ $worker->title }}</td>
                                <td>
                                    <div>{{ $worker->phone ?: '-' }}</div>
                                    <div class="service-note">{{ $worker->email ?: 'No email' }}</div>
                                </td>
                                <td>
                                    <div class="service-assignments">
                                        @forelse($worker->services as $service)
                                            <span>{{ $service->name }}</span>
                                        @empty
                                            <span style="background:#f8fafc; color:#64748b; border-color:#e2e8f0;">No services assigned</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <span class="service-tag {{ $worker->is_active ? 'status-completed' : 'status-cancelled' }}">
                                        {{ $worker->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <div class="service-actions" style="justify-content:center;">
                                        <a class="btn-soft" href="{{ route('service-workers.edit', $worker) }}">Edit</a>
                                        <form method="POST" action="{{ route('service-workers.status', $worker) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $worker->is_active ? 0 : 1 }}">
                                            <button type="submit" class="btn-soft {{ $worker->is_active ? 'danger' : 'success' }}">
                                                {{ $worker->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center; color:var(--muted);">No stylists registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="services-card">
            <div class="service-toolbar">
                <div>
                    <h2 style="margin:0;">Service Catalog</h2>
                    <p class="service-note" style="margin:6px 0 0;">Each service can be mapped to the stylists allowed to perform it.</p>
                </div>
                @if($canManageCatalog)
                    <a class="btn" href="{{ route('services.create') }}">Add Service</a>
                @endif
            </div>

            <div style="overflow:auto;">
                <table class="service-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Duration</th>
                            <th>Assigned Stylists</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $service)
                            <tr>
                                <td>
                                    <div style="font-weight:700;">{{ $service->name }}</div>
                                    <div class="service-note">{{ $service->description ?: 'No description added yet.' }}</div>
                                </td>
                                <td>{{ $service->category->name ?? 'No category' }}</td>
                                <td>{{ $service->duration_minutes }} min</td>
                                <td>
                                    <div class="service-assignments">
                                        @forelse($service->workers as $worker)
                                            <span>{{ $worker->name }}</span>
                                        @empty
                                            <span style="background:#f8fafc; color:#64748b; border-color:#e2e8f0;">Assign stylists</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>KES {{ number_format((float) $service->price, 2) }}</td>
                                <td>
                                    <span class="service-tag {{ $service->is_active ? 'status-completed' : 'status-cancelled' }}">
                                        {{ $service->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <div class="service-actions" style="justify-content:center;">
                                        <a class="btn-soft" href="{{ route('services.edit', $service) }}">Edit</a>
                                        <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('Delete this service? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-soft danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center; color:var(--muted);">No services yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var serviceSelect = document.getElementById('service-visit-service');
            var workerSelect = document.getElementById('service-visit-worker');
            var priceInput = document.getElementById('service-visit-price');
            var workerHelp = document.getElementById('service-worker-help');

            if (!serviceSelect || !workerSelect || !priceInput) {
                return;
            }

            function filterWorkers() {
                var serviceId = serviceSelect.value;
                var selectedServiceOption = serviceSelect.options[serviceSelect.selectedIndex];
                var serviceHasAssignedWorkers = selectedServiceOption && Number(selectedServiceOption.dataset.workerCount || '0') > 0;
                var fallbackValue = '';
                var visibleCount = 0;

                for (var i = 0; i < workerSelect.options.length; i += 1) {
                    var option = workerSelect.options[i];
                    if (!option.value) {
                        continue;
                    }

                    var allowedServices = (option.dataset.services || '').split(',').filter(Boolean);
                    var visible = !serviceId || !serviceHasAssignedWorkers || allowedServices.indexOf(serviceId) !== -1;
                    option.hidden = !visible;

                    if (visible && !fallbackValue) {
                        fallbackValue = option.value;
                    }

                    if (visible) {
                        visibleCount += 1;
                    }
                }

                if (workerSelect.value) {
                    var currentOption = workerSelect.options[workerSelect.selectedIndex];
                    if (currentOption && currentOption.hidden) {
                        workerSelect.value = fallbackValue;
                    }
                }

                if (!serviceId) {
                    workerHelp.textContent = '';
                    return;
                }

                if (!serviceHasAssignedWorkers) {
                    workerHelp.textContent = visibleCount > 0
                        ? 'No stylist is assigned to this service yet. Showing all active stylists for first assignment.'
                        : 'No active stylists are available for the current branch.';
                    return;
                }

                if (visibleCount === 0) {
                    workerHelp.textContent = 'No assigned stylist is currently available for this service.';
                    return;
                }

                workerHelp.textContent = '';
            }

            function fillPrice() {
                var selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                if (!selectedOption || !selectedOption.dataset.price) {
                    return;
                }

                if (!priceInput.value || priceInput.dataset.autofilled === '1') {
                    priceInput.value = selectedOption.dataset.price;
                    priceInput.dataset.autofilled = '1';
                }
            }

            serviceSelect.addEventListener('change', function () {
                filterWorkers();
                fillPrice();
            });

            priceInput.addEventListener('input', function () {
                priceInput.dataset.autofilled = '0';
            });

            filterWorkers();
            fillPrice();
        })();
    </script>
@endpush
