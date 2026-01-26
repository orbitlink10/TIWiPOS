<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('name')->get();
        $current = session('branch_id') ?? auth()->user()->branch_id;

        return view('pages.branches', compact('branches', 'current'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('branches', 'code')->where('business_id', Tenant::businessId())],
            'location' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'timezone' => ['nullable', 'string', 'max:60'],
        ]);

        $branch = Branch::create([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'location' => $data['location'] ?? null,
            'phone' => $data['phone'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'is_default' => false,
        ]);

        return redirect()->route('branches.index')->with('status', 'Branch created.');
    }

    public function switch(Request $request)
    {
        $branchId = $request->input('branch_id');
        $branch = Branch::where('id', $branchId)->where('business_id', Tenant::businessId())->firstOrFail();
        session(['branch_id' => $branch->id]);
        auth()->user()->forceFill(['branch_id' => $branch->id])->save();

        return redirect()->back()->with('status', 'Switched to branch: '.$branch->name);
    }
}
