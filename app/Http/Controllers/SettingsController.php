<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\Tenant;

class SettingsController extends Controller
{
    private function ensureOwner(): void
    {
        if (auth()->user()->role !== 'owner') {
            abort(403, 'Only admins can manage settings.');
        }
    }

    public function index()
    {
        $this->ensureOwner();

        $businessId = Tenant::businessId();
        $branchId = Tenant::branchId();

        $staff = User::with('branch')
            ->where('business_id', $businessId)
            ->where('role', '!=', 'owner')
            ->orderBy('name')
            ->get();

        $branches = Branch::where('business_id', $businessId)->orderBy('name')->get();

        $products = Product::with('category')
            ->withExists(['saleItems as has_sales' => function ($query) {
                $query->withoutGlobalScope('branch');
            }])
            ->withSum(['stocks as stock_on_hand' => function ($query) use ($branchId) {
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            }], 'quantity')
            ->orderBy('name')
            ->get();

        $categories = Category::withCount('products')
            ->with('parent')
            ->orderBy('name')
            ->get();

        return view('pages.settings', compact('staff', 'branches', 'products', 'categories'));
    }
}
