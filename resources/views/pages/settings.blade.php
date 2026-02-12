@extends('layouts.app')

@section('title', 'Settings')

@section('header')
    <div class="header-row">
        <h1>Settings</h1>
        <a class="btn" href="{{ route('home') }}">Back to Dashboard</a>
    </div>
@endsection

@push('styles')
    <style>
        .settings-grid {
            display: grid;
            gap: 16px;
        }

        .settings-card {
            border-radius: 16px;
            border: 1px solid var(--border);
            background: #fff;
            box-shadow: 0 10px 28px rgba(12, 30, 60, 0.06);
            padding: 18px;
        }

        .settings-card h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .settings-subtext {
            color: var(--muted);
            margin: 6px 0 0;
            font-size: 14px;
        }

        .settings-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .settings-table-wrap {
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }

        .settings-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
            font-size: 14px;
        }

        .settings-table th {
            text-align: left;
            padding: 10px;
            background: #f4f7fb;
            color: #4b5c76;
            font-size: 12px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .settings-table td {
            padding: 10px;
            border-top: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .status-pill.active {
            color: #065f46;
            background: #d1fae5;
        }

        .status-pill.inactive {
            color: #991b1b;
            background: #fee2e2;
        }

        .btn-inline {
            border-radius: 9px;
            border: 1px solid transparent;
            padding: 7px 11px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }

        .btn-danger {
            border-color: #fecaca;
            background: #fff1f2;
            color: #b91c1c;
        }

        .btn-muted {
            border-color: #d1d5db;
            background: #f8fafc;
            color: #334155;
        }

        .settings-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-weight: 700;
            font-size: 13px;
        }

        .quick-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .quick-links a {
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #f8fbff;
            color: #0f1b2d;
            text-decoration: none;
            padding: 9px 12px;
            font-weight: 700;
            font-size: 13px;
        }
    </style>
@endpush

@section('content')
    <div class="settings-grid">
        @if (session('status'))
            <div style="padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div style="padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div style="padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <section class="settings-card">
            <div class="settings-toolbar">
                <div>
                    <h2>Staff Controls</h2>
                    <p class="settings-subtext">Add new staff accounts and activate/deactivate access.</p>
                </div>
            </div>

            <div class="settings-table-wrap">
                <table class="settings-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Branch</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staff as $member)
                            <tr>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->email }}</td>
                                <td>{{ $member->branch->name ?? 'Default' }}</td>
                                <td>{{ ucfirst($member->role) }}</td>
                                <td>
                                    <span class="status-pill {{ $member->is_active ? 'active' : 'inactive' }}">
                                        {{ $member->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <form method="POST" action="{{ route('staff.status', $member) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $member->is_active ? 0 : 1 }}">
                                        <input type="hidden" name="redirect_to" value="settings.index">
                                        <button type="submit" class="btn-inline {{ $member->is_active ? 'btn-danger' : 'btn-muted' }}">
                                            {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center; color:var(--muted);">No staff yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form method="POST" action="{{ route('staff.store') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="settings.index">
                <div class="settings-form-grid">
                    <label class="field">
                        Full name
                        <input name="name" type="text" value="{{ old('name') }}" required>
                    </label>
                    <label class="field">
                        Email
                        <input name="email" type="email" value="{{ old('email') }}" required>
                    </label>
                    <label class="field">
                        Branch
                        <select name="branch_id">
                            <option value="">Use current branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        Role
                        <select name="role">
                            <option value="staff" @selected(old('role', 'staff') === 'staff')>Staff</option>
                            <option value="manager" @selected(old('role') === 'manager')>Manager</option>
                        </select>
                    </label>
                    <label class="field">
                        Password
                        <input name="password" type="password" required>
                    </label>
                    <label class="field">
                        Confirm password
                        <input name="password_confirmation" type="password" required>
                    </label>
                </div>
                <div style="margin-top:12px;">
                    <button class="btn" type="submit">Add Staff</button>
                </div>
            </form>
        </section>

        <section class="settings-card">
            <div class="settings-toolbar">
                <div>
                    <h2>Category Controls</h2>
                    <p class="settings-subtext">Deleting a category will also delete all products in that category.</p>
                </div>
                <a class="btn" href="{{ route('categories.create') }}">Add Category</a>
            </div>

            <div class="settings-table-wrap">
                <table class="settings-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Parent</th>
                            <th>Products</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td>{{ optional($category->parent)->name ?? 'None' }}</td>
                                <td>{{ $category->products_count }}</td>
                                <td style="text-align:center;">
                                    <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Delete this category and all products under it?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="settings.index">
                                        <button type="submit" class="btn-inline btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center; color:var(--muted);">No categories yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="settings-card">
            <div class="settings-toolbar">
                <div>
                    <h2>Product Controls</h2>
                    <p class="settings-subtext">Delete unwanted products and keep the catalog clean.</p>
                </div>
                <a class="btn" href="{{ route('products.create') }}">Add Product</a>
            </div>

            <div class="settings-table-wrap">
                <table class="settings-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category->name ?? 'Uncategorized' }}</td>
                                <td>KES {{ number_format((float) $product->price, 2) }}</td>
                                <td>{{ (int) ($product->stock_on_hand ?? 0) }}</td>
                                <td style="text-align:center;">
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="settings.index">
                                        <button type="submit" class="btn-inline btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center; color:var(--muted);">No products yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="settings-card">
            <h2>Other Controls</h2>
            <p class="settings-subtext">Quick access to other administrative controls.</p>
            <div class="quick-links">
                <a href="{{ route('branches.index') }}">Manage Branches</a>
                <a href="{{ route('stock.adjust') }}">Stock Adjustment</a>
                <a href="{{ route('billing.show') }}">Billing & Plan</a>
                <a href="{{ route('sales.index') }}">Sales History</a>
            </div>
        </section>
    </div>
@endsection
