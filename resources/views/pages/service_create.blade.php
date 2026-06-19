@extends('layouts.app')

@section('title', 'Add Goods')

@section('header')
    <div class="header-row">
        <h1>Add Goods</h1>
        <a class="btn" href="{{ route('services') }}">Back to Delivery</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Goods details</h2>
        <p style="color: var(--muted); margin-top:6px;">Register goods, then assign the delivery people allowed to handle them.</p>

        @if (session('status'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('services.store') }}" style="margin-top:14px; display:grid; gap:14px;">
            @csrf

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Goods name
                    <input name="name" type="text" value="{{ old('name') }}" placeholder="Box of electronics" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Category
                    <select name="service_category_id" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="" disabled @selected(!old('service_category_id'))>Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('service_category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @if($categories->isEmpty())
                        <small style="color:#b45309; font-weight:500;">
                            No delivery categories exist for this business yet.
                            <a href="{{ route('service-categories.create') }}" style="color:#0369a1; font-weight:600;">Add a delivery category</a>
                        </small>
                    @endif
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Expected duration (minutes)
                    <input name="duration_minutes" type="number" min="1" max="1440" value="{{ old('duration_minutes') }}" placeholder="45" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
            </div>

            <div>
                <div style="font-weight:700; margin-bottom:8px;">Assign delivery people to these goods</div>
                @if($workers->isEmpty())
                    <div style="color:#b45309; font-size:14px;">
                        No delivery people are registered for the active branch yet. Use the Delivery page to add them first.
                    </div>
                @else
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px;">
                        @foreach($workers as $worker)
                            <label style="display:flex; align-items:flex-start; gap:10px; border:1px solid #e5e7eb; border-radius:12px; padding:12px;">
                                <input type="checkbox" name="worker_ids[]" value="{{ $worker->id }}" @checked(collect(old('worker_ids', []))->contains((string) $worker->id) || collect(old('worker_ids', []))->contains($worker->id)) style="width:18px; height:18px; margin-top:2px;">
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
                    <input name="cost" type="number" step="0.01" min="0" value="{{ old('cost', 0) }}" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Delivery charge (KES)
                    <input name="price" type="number" step="0.01" min="0" value="{{ old('price') }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
            </div>

            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Description
                <textarea name="description" rows="4" placeholder="Optional notes, preparation steps, or inclusions" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px; resize:vertical;">{{ old('description') }}</textarea>
            </label>

            <label style="display:flex; align-items:center; gap:10px; font-weight:600;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') == '1') style="width:18px;height:18px;"> Active
            </label>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn" type="submit">Save Goods</button>
                <a class="btn" style="background:#0ea5e9;" href="{{ route('service-categories.create') }}">Add Category</a>
                <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route('services') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
