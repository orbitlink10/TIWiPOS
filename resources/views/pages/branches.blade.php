@extends('layouts.app')

@section('title', 'Branches')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap');

    .branches-page {
        font-family: 'Plus Jakarta Sans', 'Manrope', 'Segoe UI', sans-serif;
        display: grid;
        gap: 14px;
    }

    .branches-lead {
        margin: 6px 0 0;
        color: #6b7b92;
        font-size: 14px;
        font-weight: 500;
    }

    .branches-status {
        padding: 11px 13px;
        border-radius: 12px;
        border: 1px solid #bbf7d0;
        background: #ecfdf3;
        color: #166534;
        font-weight: 700;
        font-size: 14px;
    }

    .branches-errors {
        padding: 11px 13px;
        border-radius: 12px;
        border: 1px solid rgba(220, 38, 38, 0.3);
        background: rgba(254, 226, 226, 0.7);
        color: #991b1b;
        font-weight: 700;
        font-size: 13px;
        display: grid;
        gap: 3px;
    }

    .branches-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 18px;
        align-items: start;
    }

    .branches-card,
    .branch-form-card {
        border-radius: 16px;
        border: 1px solid #dbe5f1;
        background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
        box-shadow: 0 10px 30px rgba(18, 37, 71, 0.08);
    }

    .branches-card-header,
    .branch-form-header {
        padding: 16px 18px;
        border-bottom: 1px solid #e6edf6;
    }

    .branches-card-title,
    .branch-form-title {
        margin: 0;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -0.01em;
        color: #152740;
    }

    .branches-card-subtitle,
    .branch-form-subtitle {
        margin: 6px 0 0;
        color: #6d7e95;
        font-size: 13px;
        font-weight: 500;
    }

    .branches-table-wrap {
        overflow: auto;
    }

    .branches-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 760px;
        font-size: 14px;
    }

    .branches-table th {
        text-align: left;
        padding: 11px 14px;
        background: #f0f5fb;
        color: #5f7189;
        font-size: 11px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-weight: 700;
    }

    .branches-table td {
        padding: 12px 14px;
        border-top: 1px solid #e8eef7;
        color: #13243a;
    }

    .branches-table .align-center {
        text-align: center;
    }

    .branch-name {
        font-weight: 700;
    }

    .branch-muted {
        color: #789;
        font-size: 13px;
    }

    .active-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 78px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #e6f9ef;
        color: #146c43;
        border: 1px solid #b6eccd;
        font-size: 12px;
        font-weight: 800;
    }

    .inactive-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 78px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #f4f6fa;
        color: #65758f;
        border: 1px solid #dce4ef;
        font-size: 12px;
        font-weight: 700;
    }

    .switch-btn {
        border: 1px solid #98b3d8;
        background: #f3f8ff;
        color: #1f4c86;
        border-radius: 10px;
        padding: 7px 11px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
    }

    .branch-form-body {
        padding: 16px 18px 18px;
    }

    .branch-form {
        display: grid;
        gap: 11px;
    }

    .branch-field {
        display: grid;
        gap: 6px;
    }

    .branch-field label {
        color: #4f5f77;
        font-size: 12px;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        font-weight: 700;
    }

    .branch-field input {
        width: 100%;
        padding: 11px 12px;
        border-radius: 10px;
        border: 1px solid #d8e1ee;
        background: #fff;
        color: #10243b;
        font-size: 14px;
        font-weight: 600;
        font-family: inherit;
    }

    .branch-field input:focus {
        outline: 2px solid rgba(14, 165, 233, 0.2);
        border-color: rgba(14, 165, 233, 0.45);
    }

    .field-error {
        color: #b42318;
        font-size: 12px;
        font-weight: 700;
    }

    .branch-submit {
        margin-top: 4px;
        width: 100%;
        border: none;
        border-radius: 11px;
        padding: 11px 12px;
        background: linear-gradient(135deg, #0f7fa7 0%, #0d6990 100%);
        color: #fff;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
    }

    @media (max-width: 1120px) {
        .branches-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('header')
<div class="header-row">
    <div>
        <h1>Branches</h1>
        <p class="branches-lead">Manage locations and switch the active branch for sales, stock, and reports.</p>
    </div>
</div>
@endsection

@section('content')
<div class="branches-page">
    @if(session('status'))
        <div class="branches-status">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="branches-errors">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="branches-grid">
        <section class="branches-card">
            <div class="branches-card-header">
                <h2 class="branches-card-title">Existing Branches</h2>
                <p class="branches-card-subtitle">Switch the active branch instantly from this table.</p>
            </div>

            <div class="branches-table-wrap">
                <table class="branches-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Location</th>
                            <th class="align-center">Status</th>
                            <th class="align-center">Switch</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                            <tr>
                                <td>
                                    <div class="branch-name">{{ $branch->name }}</div>
                                </td>
                                <td>{{ $branch->code ?: '-' }}</td>
                                <td>
                                    <span class="branch-muted">{{ $branch->location ?: '-' }}</span>
                                </td>
                                <td class="align-center">
                                    @if($branch->id === $current)
                                        <span class="active-chip">Current</span>
                                    @else
                                        <span class="inactive-chip">Inactive</span>
                                    @endif
                                </td>
                                <td class="align-center">
                                    @if($branch->id !== $current)
                                        <form method="POST" action="{{ route('branches.switch') }}">
                                            @csrf
                                            <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                            <button class="switch-btn" type="submit">Switch</button>
                                        </form>
                                    @else
                                        <span class="branch-muted">Selected</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="align-center">No branches yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="branch-form-card">
            <div class="branch-form-header">
                <h3 class="branch-form-title">Add Branch</h3>
                <p class="branch-form-subtitle">Create a new location for your business.</p>
            </div>

            <div class="branch-form-body">
                <form method="POST" action="{{ route('branches.store') }}" class="branch-form">
                    @csrf

                    <div class="branch-field">
                        <label for="name">Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                        @error('name')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="branch-field">
                        <label for="code">Code (Optional)</label>
                        <input id="code" name="code" type="text" value="{{ old('code') }}">
                        @error('code')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="branch-field">
                        <label for="location">Location</label>
                        <input id="location" name="location" type="text" value="{{ old('location') }}">
                    </div>

                    <div class="branch-field">
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}">
                    </div>

                    <div class="branch-field">
                        <label for="timezone">Timezone</label>
                        <input id="timezone" name="timezone" type="text" value="{{ old('timezone') }}" placeholder="Africa/Nairobi">
                    </div>

                    <button type="submit" class="branch-submit">Create Branch</button>
                </form>
            </div>
        </aside>
    </div>
</div>
@endsection
