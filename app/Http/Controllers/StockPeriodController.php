<?php

namespace App\Http\Controllers;

use App\Models\{Branch, CashierShift, Category, Product, ProductStock, StockAdjustment, StockMovement, StockPeriod};
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockPeriodController extends Controller
{
    private function periods()
    {
        abort_unless(Tenant::businessId() && Tenant::branchId(), 403, 'A business and branch are required.');
        return StockPeriod::where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId());
    }

    private function stock($period, $ids, bool $lock = false)
    {
        $query = ProductStock::where('business_id', $period->business_id)->where('branch_id', $period->branch_id)
            ->where('location', $period->location)->whereIn('product_id', $ids)->orderBy('product_id')->orderBy('id');
        return ($lock ? $query->lockForUpdate() : $query)->get()->groupBy('product_id');
    }

    private function lockProducts($period, $lines): void
    {
        $products = Product::where('business_id', $period->business_id)->whereIn('id', $lines->pluck('product_id'))->orderBy('id')->lockForUpdate()->get(['id']);
        if ($products->count() !== $lines->count()) throw ValidationException::withMessages(['period' => 'A product on this count is no longer available in the business.']);
    }

    private function checkVersion($period, Request $request): void
    {
        $request->validate(['version' => ['required', 'integer', 'min:1']]);
        if ((int) $period->version !== $request->integer('version')) throw ValidationException::withMessages(['period' => 'This count changed in another window. Reload it before continuing.']);
    }

    public function index(Request $request)
    {
        $query = $this->periods();
        $data = $request->validate(['status' => ['nullable', 'in:open,closed,cancelled'], 'q' => ['nullable', 'string', 'max:100']]);
        $search = trim($data['q'] ?? '');
        $periods = $query->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('notes', 'like', '%'.$search.'%');
                $number = preg_replace('/^SP-0*/i', '', $search);
                if (ctype_digit($number)) $query->orWhere('id', (int) $number);
            }))->with(['opener:id,name', 'category:id,name', 'shift:id,user_id'])->withCount('lines')
            ->withCount(['lines as counted_lines_count' => fn ($query) => $query->whereNotNull('counted_quantity')])
            ->orderByDesc('id')->paginate(20)->withQueryString();
        $categories = Category::where('business_id', Tenant::businessId())->whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
        $locations = ProductStock::where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId())->distinct()->pluck('location')->prepend('main')->unique()->values();
        $shifts = CashierShift::where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId())->where('status', 'closed')
            ->whereNotIn('id', $this->periods()->whereNotNull('cashier_shift_id')->select('cashier_shift_id'))
            ->with('user:id,name')->orderByDesc('closed_at')->limit(100)->get();
        return view('pages.stock_control.periods', compact('periods', 'categories', 'locations', 'shifts', 'search'));
    }

    public function store(Request $request)
    {
        $this->periods();
        $data = $request->validate([
            'creation_token' => ['required', 'uuid'], 'location' => ['required', 'string', 'max:100'],
            'starts_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on', 'before_or_equal:today'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('business_id', Tenant::businessId())->whereNull('parent_id')],
            'cashier_shift_id' => ['nullable', 'integer', Rule::exists('cashier_shifts', 'id')->where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId())->where('status', 'closed')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $period = DB::transaction(function () use ($data) {
            Branch::where('business_id', Tenant::businessId())->whereKey(Tenant::branchId())->lockForUpdate()->firstOrFail();
            if ($existing = $this->periods()->where('creation_token', $data['creation_token'])->first()) return $existing;
            $location = strtolower(trim($data['location']));
            if ($location === '') throw ValidationException::withMessages(['location' => 'Choose a stock location.']);
            $slot = hash('sha256', Tenant::businessId().':'.Tenant::branchId().':'.$location);
            if ($existing = $this->periods()->where('active_slot', $slot)->first()) return $existing;
            if (!empty($data['cashier_shift_id']) && $this->periods()->where('cashier_shift_id', $data['cashier_shift_id'])->exists()) {
                throw ValidationException::withMessages(['cashier_shift_id' => 'That shift already has a stock period. Open the existing period.']);
            }
            $stock = ProductStock::where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId())->where('location', $location)
                ->selectRaw('product_id, SUM(quantity) as quantity')->groupBy('product_id');
            $products = Product::where('products.business_id', Tenant::businessId())->leftJoinSub($stock, 'stock_snapshot', 'stock_snapshot.product_id', '=', 'products.id')
                ->where(fn ($query) => $query->where('is_active', true)->orWhereRaw('COALESCE(stock_snapshot.quantity, 0) != 0'))
                ->when($data['category_id'] ?? null, fn ($query, $id) => $query->where(function ($query) use ($id) {
                    $query->where('category_id', $id)->orWhereIn('category_id', Category::where('business_id', Tenant::businessId())->where('parent_id', $id)->select('id'));
                }))->select('products.id', 'name', 'sku', 'cost')->selectRaw('COALESCE(stock_snapshot.quantity, 0) as quantity')->orderBy('name')->orderBy('products.id')->get();
            if ($products->isEmpty()) throw ValidationException::withMessages(['category_id' => 'No products are available for this count.']);
            $period = StockPeriod::create(['business_id' => Tenant::businessId(), 'branch_id' => Tenant::branchId(), 'opened_by' => auth()->id(),
                'creation_token' => $data['creation_token'], 'active_slot' => $slot, 'location' => $location, 'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'],
                'category_id' => $data['category_id'] ?? null, 'cashier_shift_id' => $data['cashier_shift_id'] ?? null, 'notes' => $data['notes'] ?? null]);
            foreach ($products->chunk(300) as $chunk) {
                $period->lines()->insert($chunk->map(fn ($product) => ['stock_period_id' => $period->id, 'product_id' => $product->id, 'product_name' => $product->name,
                    'sku' => $product->sku, 'opening_quantity' => $product->quantity, 'expected_quantity' => $product->quantity, 'unit_cost' => $product->cost ?? 0, 'created_at' => now(), 'updated_at' => now()])->all());
            }
            return $period;
        }, 3);
        return redirect()->route('stock-control.periods.show', ['period' => $period->id, 'count' => 1])->with('status', 'Stock period ready. Enter the physical quantities in the count modal.');
    }

    public function show(int $period)
    {
        $period = $this->periods()->with(['opener:id,name', 'closer:id,name', 'category:id,name', 'shift:id,user_id'])->findOrFail($period);
        $summary = $period->lines()->selectRaw('COUNT(*) as total_lines, COUNT(counted_quantity) as counted_lines, COALESCE(SUM(expected_quantity), 0) as expected_units, COALESCE(SUM(counted_quantity), 0) as counted_units, COALESCE(SUM(CASE WHEN counted_quantity IS NOT NULL THEN CAST(counted_quantity AS SIGNED) - expected_quantity ELSE 0 END), 0) as variance, COALESCE(SUM(CASE WHEN counted_quantity IS NOT NULL THEN (CAST(counted_quantity AS SIGNED) - expected_quantity) * unit_cost ELSE 0 END), 0) as variance_value')->first();
        $lines = $period->lines()->orderBy('product_name')->orderBy('id')->paginate(50)->withQueryString();
        return view('pages.stock_control.period_show', compact('period', 'summary', 'lines'));
    }

    public function save(Request $request, int $period)
    {
        $data = $request->validate(['lines' => ['required', 'array', 'min:1', 'max:50'], 'lines.*.quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'], 'page' => ['required', 'integer', 'min:1'], 'next' => ['nullable', 'in:1']]);
        DB::transaction(function () use ($request, $period, $data) {
            $period = $this->periods()->whereKey($period)->lockForUpdate()->firstOrFail();
            abort_unless($period->status === 'open', 422, 'This stock period is no longer open.');
            $this->checkVersion($period, $request);
            $lines = $period->lines()->whereIn('id', array_keys($data['lines']))->lockForUpdate()->get()->keyBy('id');
            if ($lines->count() !== count($data['lines'])) throw ValidationException::withMessages(['lines' => 'A count line does not belong to this period.']);
            foreach ($data['lines'] as $id => $line) $lines->get($id)->update(['counted_quantity' => $line['quantity'] ?? null]);
            $period->increment('version');
        }, 3);
        $page = (int) $data['page'];
        if (!empty($data['next'])) $page++;
        return redirect()->route('stock-control.periods.show', ['period' => $period, 'page' => $page, 'count' => !empty($data['next']) ? 1 : null])->with('status', 'Count quantities saved. Stock is unchanged until you close and post this period.');
    }

    public function refresh(Request $request, int $period)
    {
        $changed = DB::transaction(function () use ($request, $period) {
            $period = $this->periods()->whereKey($period)->lockForUpdate()->firstOrFail();
            abort_unless($period->status === 'open', 422);
            $this->checkVersion($period, $request);
            $lines = $period->lines()->orderBy('product_id')->lockForUpdate()->get();
            $this->lockProducts($period, $lines);
            $stock = $this->stock($period, $lines->pluck('product_id'), true);
            $changed = 0;
            foreach ($lines as $line) {
                $current = (int) ($stock->get($line->product_id)?->sum('quantity') ?? 0);
                if ($current !== (int) $line->expected_quantity) { $line->update(['expected_quantity' => $current, 'counted_quantity' => null]); $changed++; }
            }
            $period->increment('version');
            return $changed;
        }, 3);
        return back()->with('status', $changed.' changed products refreshed. Recount those blank lines before posting. Other counts were kept.');
    }

    public function post(Request $request, int $period)
    {
        $request->validate(['confirmation' => ['accepted'], 'closing_notes' => ['nullable', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $period) {
            $period = $this->periods()->whereKey($period)->lockForUpdate()->firstOrFail();
            if ($period->status === 'closed') return;
            abort_unless($period->status === 'open', 422);
            $this->checkVersion($period, $request);
            $lines = $period->lines()->orderBy('product_id')->lockForUpdate()->get();
            if ($lines->isEmpty() || $lines->contains(fn ($line) => $line->counted_quantity === null)) throw ValidationException::withMessages(['period' => 'Save a physical quantity for every product before posting. Blank quantities are not treated as zero.']);
            $this->lockProducts($period, $lines);
            $stock = $this->stock($period, $lines->pluck('product_id'), true);
            foreach ($lines as $line) {
                if ((int) ($stock->get($line->product_id)?->sum('quantity') ?? 0) !== (int) $line->expected_quantity) {
                    throw ValidationException::withMessages(['period' => 'Stock changed for '.$line->product_name.'. Use Refresh changed stock, then recount affected products before posting.']);
                }
            }
            foreach ($lines as $line) {
                $before = (int) $line->expected_quantity;
                $after = (int) $line->counted_quantity;
                $variance = $after - $before;
                $line->update(['variance' => $variance]);
                if ($variance === 0) continue;
                $rows = $stock->get($line->product_id, collect());
                $primary = $rows->first() ?? ProductStock::create(['business_id' => $period->business_id, 'branch_id' => $period->branch_id, 'product_id' => $line->product_id, 'location' => $period->location, 'quantity' => 0]);
                $primary->update(['quantity' => $after]);
                foreach ($rows->skip(1) as $extra) $extra->update(['quantity' => 0]);
                StockAdjustment::create(['business_id' => $period->business_id, 'branch_id' => $period->branch_id, 'product_id' => $line->product_id, 'user_id' => auth()->id(),
                    'operation_token' => (string) Str::uuid(), 'location' => $period->location, 'mode' => 'count', 'reason' => 'Stock count', 'quantity_before' => $before,
                    'quantity_after' => $after, 'adjusted_at' => today(), 'notes' => $period->reference]);
                StockMovement::create(['business_id' => $period->business_id, 'branch_id' => $period->branch_id, 'product_id' => $line->product_id, 'user_id' => auth()->id(),
                    'location' => $period->location, 'type' => 'adjustment', 'quantity_change' => $variance, 'quantity_before' => $before, 'quantity_after' => $after,
                    'reference_type' => 'stock_period', 'reference_id' => $period->id, 'note' => 'Physical stock count '.$period->reference]);
            }
            $period->update(['status' => 'closed', 'active_slot' => null, 'closed_by' => auth()->id(), 'closed_at' => now(), 'closing_notes' => $request->input('closing_notes'), 'version' => $period->version + 1]);
        }, 3);
        return back()->with('status', 'Stock period closed. Count variances were posted to stock and movement history.');
    }

    public function cancel(Request $request, int $period)
    {
        $data = $request->validate(['closing_notes' => ['required', 'string', 'min:3', 'max:1000']]);
        DB::transaction(function () use ($request, $period, $data) {
            $period = $this->periods()->whereKey($period)->lockForUpdate()->firstOrFail();
            abort_unless($period->status === 'open', 422);
            $this->checkVersion($period, $request);
            $period->update(['status' => 'cancelled', 'active_slot' => null, 'closed_by' => auth()->id(), 'closed_at' => now(), 'closing_notes' => $data['closing_notes'], 'version' => $period->version + 1]);
        });
        return back()->with('status', 'Stock period cancelled. No stock quantities were changed.');
    }
}
