<?php

namespace App\Http\Controllers;

use App\Models\TodoItem;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TodoItemController extends Controller
{
    private function items(Request $request): Builder
    {
        abort_unless(Tenant::businessId(), 403);

        return TodoItem::query()
            ->where('business_id', Tenant::businessId())
            ->where('user_id', $request->user()->id);
    }

    public function index(Request $request)
    {
        $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $date = $data['date'] ?? now('Africa/Nairobi')->toDateString();
        $items = $this->items($request)->whereDate('activity_date', $date)
            ->orderBy('is_completed')->orderBy('id')->paginate(20)->withQueryString();

        return view('pages.todo_list', compact('items', 'date'));
    }

    public function store(Request $request)
    {
        abort_unless(Tenant::businessId(), 403);
        $data = $request->validate([
            'activity' => ['required', 'string', 'max:5000'],
            'activity_date' => ['required', 'date_format:Y-m-d'],
        ]);
        TodoItem::create($data + [
            'business_id' => Tenant::businessId(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('todos.index', ['date' => $data['activity_date']])
            ->with('status', 'Activity added.');
    }

    public function update(Request $request, int $todo)
    {
        $item = $this->items($request)->findOrFail($todo);
        $data = $request->validate(['is_completed' => ['required', 'boolean']]);
        $item->update($data);

        return redirect()->route('todos.index', ['date' => $item->activity_date->toDateString()])
            ->with('status', $item->is_completed ? 'Activity marked done.' : 'Activity marked pending.');
    }
}
