@extends('layouts.app')

@section('title', 'Edit Service Visit')

@section('header')
    <div class="header-row">
        <h1>Edit Service Visit</h1>
        <a class="btn" href="{{ route('services', ['date' => $serviceVisit->service_date?->toDateString()]) }}">Back to Services</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Visit details</h2>
        <p style="color: var(--muted); margin-top:6px;">Update the customer, assigned stylist, timing, and completion status.</p>

        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('service-visits.update', $serviceVisit) }}" style="margin-top:14px; display:grid; gap:14px;">
            @csrf
            @method('PUT')

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Customer name
                    <input name="customer_name" type="text" value="{{ old('customer_name', $serviceVisit->customer_name) }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Customer phone
                    <input name="customer_phone" type="text" value="{{ old('customer_phone', $serviceVisit->customer_phone) }}" inputmode="tel" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Service
                    <select name="service_id" id="service-visit-edit-service" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="">Select service</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" data-price="{{ number_format((float) $service->price, 2, '.', '') }}" @selected((string) old('service_id', $serviceVisit->service_id) === (string) $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Stylist
                    <select name="service_worker_id" id="service-visit-edit-worker" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="">Select stylist</option>
                        @foreach($workers as $worker)
                            <option value="{{ $worker->id }}" data-services="{{ $worker->services->pluck('id')->implode(',') }}" @selected((string) old('service_worker_id', $serviceVisit->service_worker_id) === (string) $worker->id)>{{ $worker->name }} · {{ $worker->title }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Visit date
                    <input name="service_date" type="date" value="{{ old('service_date', $serviceVisit->service_date?->toDateString()) }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Visit time
                    <input name="service_time" type="time" value="{{ old('service_time', $serviceVisit->service_time ? substr($serviceVisit->service_time, 0, 5) : null) }}" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Price charged (KES)
                    <input name="price" id="service-visit-edit-price" type="number" min="0" step="0.01" value="{{ old('price', number_format((float) $serviceVisit->price, 2, '.', '')) }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Status
                    <select name="status" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        @foreach($statuses as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected(old('status', $serviceVisit->status) === $statusKey)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Notes
                <textarea name="notes" rows="4" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px; resize:vertical;">{{ old('notes', $serviceVisit->notes) }}</textarea>
            </label>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn" type="submit">Save Changes</button>
                <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route('services', ['date' => $serviceVisit->service_date?->toDateString()]) }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var serviceSelect = document.getElementById('service-visit-edit-service');
            var workerSelect = document.getElementById('service-visit-edit-worker');
            var priceInput = document.getElementById('service-visit-edit-price');

            if (!serviceSelect || !workerSelect || !priceInput) {
                return;
            }

            function filterWorkers() {
                var serviceId = serviceSelect.value;
                var fallbackValue = '';

                for (var i = 0; i < workerSelect.options.length; i += 1) {
                    var option = workerSelect.options[i];
                    if (!option.value) {
                        continue;
                    }

                    var allowedServices = (option.dataset.services || '').split(',').filter(Boolean);
                    var visible = !serviceId || allowedServices.indexOf(serviceId) !== -1;
                    option.hidden = !visible;

                    if (visible && !fallbackValue) {
                        fallbackValue = option.value;
                    }
                }

                var currentOption = workerSelect.options[workerSelect.selectedIndex];
                if (currentOption && currentOption.hidden) {
                    workerSelect.value = fallbackValue;
                }
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
        })();
    </script>
@endpush
