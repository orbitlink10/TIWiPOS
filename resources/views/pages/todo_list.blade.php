@extends('layouts.app')

@section('title', 'To Do List')

@push('styles')
<style>
    .todo-page { display: grid; gap: 20px; max-width: 1000px; }
    .todo-page h1, .todo-page h2 { margin: 0 0 10px; }
    .todo-page p { color: var(--muted); }
    .todo-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 22px; }
    .todo-form { display: grid; gap: 14px; }
    .todo-form label { display: grid; gap: 7px; font-weight: 700; }
    .todo-page input, .todo-page textarea { font: inherit; padding: 10px 12px; border: 1px solid var(--border); border-radius: 9px; max-width: 100%; }
    .todo-page textarea { width: 100%; resize: vertical; }
    .todo-toolbar { display: flex; align-items: end; gap: 12px; flex-wrap: wrap; }
    .todo-toolbar label { display: grid; gap: 7px; }
    .todo-status { background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; padding: 12px; border-radius: 10px; }
    .todo-errors { color: #991b1b; background: #fef2f2; padding: 12px; border-radius: 10px; }
    .todo-list { list-style: none; padding: 0; margin: 18px 0; }
    .todo-item { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 18px 0; border-top: 1px solid var(--border); }
    .todo-activity { white-space: pre-wrap; overflow-wrap: anywhere; margin-bottom: 6px; }
    .todo-date { color: var(--muted); font-size: 13px; }
    .todo-done .todo-activity { text-decoration: line-through; color: #166534; }
    .todo-complete { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border: 1px solid var(--border); border-radius: 10px; background: #fff; color: var(--text); font: inherit; font-weight: 700; cursor: pointer; white-space: nowrap; }
    .todo-done .todo-complete { color: #166534; background: #dcfce7; border-color: #86efac; }
    .todo-tick { display: inline-grid; place-items: center; width: 22px; height: 22px; border: 2px solid #94a3b8; border-radius: 6px; }
    .todo-done .todo-tick { background: #15803d; border-color: #15803d; color: white; }
    .todo-page button:focus-visible, .todo-page input:focus-visible, .todo-page textarea:focus-visible { outline: 3px solid #0f7fa7; outline-offset: 3px; }
    @media (max-width: 540px) { .todo-item { align-items: start; flex-direction: column; } .todo-card { padding: 16px; } }
</style>
@endpush

@section('content')
<div class="todo-page">
    <div>
        <h1>To Do List</h1>
        <p>Your personal daily journal. Plan activities and mark them done as you complete them.</p>
    </div>

    @if(session('status'))
        <div class="todo-status" role="status">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="todo-errors" role="alert">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <section class="todo-card" aria-labelledby="add-activity-title">
        <h2 id="add-activity-title">Add an activity</h2>
        <form method="POST" action="{{ route('todos.store') }}" class="todo-form">
            @csrf
            <label>Date
                <input type="date" name="activity_date" value="{{ old('activity_date', $date) }}" required>
            </label>
            <label>Activity
                <textarea name="activity" rows="3" maxlength="5000" placeholder="What would you like to achieve?" required>{{ old('activity') }}</textarea>
            </label>
            <div><button type="submit" class="btn">Add activity</button></div>
        </form>
    </section>

    <section class="todo-card" aria-labelledby="daily-activities-title">
        <h2 id="daily-activities-title">Activities for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h2>
        <form method="GET" action="{{ route('todos.index') }}" class="todo-toolbar">
            <label for="journal-date">View date
                <input id="journal-date" type="date" name="date" value="{{ $date }}" required>
            </label>
            <button type="submit" class="btn">View activities</button>
            <a href="{{ route('todos.index') }}">Today</a>
        </form>
        <ul class="todo-list">
            @forelse($items as $item)
                <li class="todo-item {{ $item->is_completed ? 'todo-done' : '' }}">
                    <div>
                        <div class="todo-activity">{{ $item->activity }}</div>
                        <time class="todo-date" datetime="{{ $item->activity_date->toDateString() }}">{{ $item->activity_date->format('d M Y') }}</time>
                    </div>
                    <form method="POST" action="{{ route('todos.update', $item) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="is_completed" value="{{ $item->is_completed ? '0' : '1' }}">
                        <button type="submit" class="todo-complete" aria-pressed="{{ $item->is_completed ? 'true' : 'false' }}" aria-label="{{ $item->is_completed ? 'Mark pending: ' : 'Mark done: ' }}{{ $item->activity }}">
                            <span class="todo-tick" aria-hidden="true">{{ $item->is_completed ? '✓' : '' }}</span>
                            {{ $item->is_completed ? 'Done' : 'Mark done' }}
                        </button>
                    </form>
                </li>
            @empty
                <li><p>No activities for this date. Add one above to get started.</p></li>
            @endforelse
        </ul>
        {{ $items->links() }}
    </section>
</div>
@endsection
