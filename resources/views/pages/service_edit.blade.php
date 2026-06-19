@extends('layouts.app')

@section('title', 'Edit Goods')

@section('header')
    <div class="header-row">
        <h1>Edit Goods</h1>
        <a class="btn" href="{{ route('services') }}">Back to Delivery</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Goods details</h2>
        <p style="color: var(--muted); margin-top:6px;">Update goods information, category, pricing, and delivery person assignment.</p>

        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('services.update', $service) }}" style="margin-top:14px; display:grid; gap:14px;">
            @csrf
            @method('PUT')

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Goods name
                    <input name="name" type="text" value="{{ old('name', $service->name) }}" placeholder="Goods name" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Category
                    <select name="service_category_id" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="" disabled @selected(!old('service_category_id', $service->service_category_id))>Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('service_category_id', $service->service_category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Expected duration (minutes)
                    <input name="duration_minutes" type="number" min="1" max="1440" value="{{ old('duration_minutes', $service->duration_minutes) }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
            </div>

            <div>
                <div style="font-weight:700; margin-bottom:8px;">Assigned delivery people</div>
                @if($workers->isEmpty())
                    <div style="color:#b45309; font-size:14px;">
                        No delivery people are registered for the active branch yet. Add them from the Delivery page.
                    </div>
                @else
                    @php($selectedWorkerIds = collect(old('worker_ids', $assignedWorkerIds ?? []))->map(fn($id) => (int) $id))
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px;">
                        @foreach($workers as $worker)
                            <label style="display:flex; align-items:flex-start; gap:10px; border:1px solid #e5e7eb; border-radius:12px; padding:12px;">
                                <input type="checkbox" name="worker_ids[]" value="{{ $worker->id }}" @checked($selectedWorkerIds->contains((int) $worker->id)) style="width:18px; height:18px; margin-top:2px;">
                                <span style="display:grid; gap:3px;">
                                    <strong>{{ $worker->name }}</strong>
                                    <span style="color:var(--muted); font-size:13px;">{{ $worker->title }}{{ $worker->phone ? ' - '.$worker->phone : '' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Handling cost (KES)
                    <input name="cost" type="number" step="0.01" min="0" value="{{ old('cost', $service->cost) }}" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Delivery charge (KES)
                    <input name="price" type="number" step="0.01" min="0" value="{{ old('price', $service->price) }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
            </div>

            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Description
                <textarea name="description" rows="4" placeholder="Optional notes" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px; resize:vertical;">{{ old('description', $service->description) }}</textarea>
            </label>

            <label style="display:flex; align-items:center; gap:10px; font-weight:600;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', (int) $service->is_active)) style="width:18px;height:18px;"> Active
            </label>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn" type="submit">Save Changes</button>
                <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route('services') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
