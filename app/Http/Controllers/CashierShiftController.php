<?php

namespace App\Http\Controllers;

use App\Models\{CashierShift, User};
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashierShiftController extends Controller
{
    private function scope()
    {
        abort_unless(Tenant::businessId() && Tenant::branchId(), 403, 'A business and branch are required.');
        return CashierShift::where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId())
            ->when(!auth()->user()->canViewAllSales(), fn ($query) => $query->where('user_id', auth()->id()));
    }

    private function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public function index(Request $request)
    {
        $query = $this->scope();
        $data = $request->validate(['status' => ['nullable', 'in:open,closed']]);
        $currentShift = CashierShift::currentQuery()->first();
        $currentSummary = $currentShift?->summary();
        $otherShift = CashierShift::where('active_user_id', auth()->id())->with('branch')->first();
        $shifts = $query->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->with('user:id,name')->orderByDesc('opened_at')->orderByDesc('id')->paginate(20)->withQueryString();
        return view('pages.shifts.index', compact('currentShift', 'currentSummary', 'otherShift', 'shifts'));
    }

    public function open(Request $request)
    {
        $this->scope();
        $data = $request->validate(['opening_cash' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $shift = DB::transaction(function () use ($data) {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $open = CashierShift::where('active_user_id', auth()->id())->first();
            if ($open) {
                if ((int) $open->business_id === (int) Tenant::businessId() && (int) $open->branch_id === (int) Tenant::branchId()) return $open;
                throw ValidationException::withMessages(['opening_cash' => 'Close your existing shift in its branch before opening another.']);
            }
            return CashierShift::create(['business_id' => Tenant::businessId(), 'branch_id' => Tenant::branchId(), 'user_id' => auth()->id(),
                'active_user_id' => auth()->id(), 'status' => 'open', 'opening_cash_cents' => $this->cents($data['opening_cash']),
                'opening_notes' => $data['notes'] ?? null, 'opened_at' => now()]);
        }, 3);
        return redirect()->route('sale')->with('status', 'Shift #'.$shift->id.' is open. You can start selling.');
    }

    public function show(int $shift)
    {
        $shift = $this->scope()->with('user:id,name')->findOrFail($shift);
        $summary = $shift->summary();
        $sales = $shift->salesQuery()->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString();
        return view('pages.shifts.show', compact('shift', 'summary', 'sales'));
    }

    public function close(Request $request, int $shift)
    {
        $this->scope()->findOrFail($shift);
        $data = $request->validate(['counted_cash' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/'], 'closing_notes' => ['nullable', 'string', 'max:1000'], 'confirmation' => ['accepted'],
            'expected_sales_count' => ['required', 'integer', 'min:0'], 'expected_cash_cents' => ['required', 'integer', 'min:0']]);
        DB::transaction(function () use ($shift, $data) {
            $shift = $this->scope()->whereKey($shift)->lockForUpdate()->firstOrFail();
            if ($shift->status === 'closed') return;
            $summary = $shift->summary();
            if ($summary['sales_count'] !== (int) $data['expected_sales_count'] || $summary['expected_cash_cents'] !== (int) $data['expected_cash_cents']) {
                throw ValidationException::withMessages(['counted_cash' => 'Shift totals changed while this page was open. Review the updated totals and confirm the cash count again.']);
            }
            $counted = $this->cents($data['counted_cash']);
            $shift->update(['status' => 'closed', 'active_user_id' => null, 'closed_by' => auth()->id(), 'closed_at' => now(),
                'expected_cash_cents' => $summary['expected_cash_cents'], 'counted_cash_cents' => $counted,
                'cash_variance_cents' => $counted - $summary['expected_cash_cents'], 'sales_total_cents' => $summary['sales_total_cents'],
                'sales_count' => $summary['sales_count'], 'payment_totals' => $summary['payments'], 'closing_notes' => $data['closing_notes'] ?? null]);
        }, 3);
        return redirect()->route('shifts.show', $shift)->with('status', 'Shift closed. Cash totals and the counted difference have been saved.');
    }
}
