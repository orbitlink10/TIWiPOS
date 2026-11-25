@extends('layouts.app')
@section('title', 'Suppliers')
@push('styles')
<style>
main{min-width:0}
.sup-intro{margin:6px 0 0;color:#667885;font-size:12px;line-height:1.6}
.sup-button{display:inline-flex;align-items:center;justify-content:center;padding:10px 15px;border:1px solid #ccd8e5;border-radius:7px;background:#fff;color:#334155;font:inherit;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;white-space:nowrap}.sup-button.primary{background:#1769d1;border-color:#1769d1;color:#fff}.sup-button:hover{filter:brightness(.97)}
.sup-page{display:grid;gap:18px;min-width:0}.sup-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.sup-card{background:#fff;border:1px solid #e2e8ee;border-radius:10px;padding:17px 20px}.sup-card-label{display:block;color:#718087;font-size:11px;font-weight:700}.sup-card strong{display:block;margin-top:7px;color:#16242b;font-size:23px;font-variant-numeric:tabular-nums;letter-spacing:-.4px}.sup-card.warning strong{color:#b42318}.sup-card-note{display:block;margin-top:6px;color:#85919b;font-size:11px}
.sup-list{min-width:0;background:#fff;border:1px solid #e2e8ee;border-radius:10px;overflow:hidden}.sup-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:18px 20px;border-bottom:1px solid #e9eef3}.sup-toolbar h2{margin:0;font-size:16px}.sup-count{display:inline-block;margin-left:7px;padding:3px 7px;border-radius:5px;color:#667885;background:#f0f4f7;font-size:11px;font-weight:600}.sup-search{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.sup-search input{width:300px;max-width:100%;padding:10px 12px;border:1px solid #cdd8e3;border-radius:7px;font:inherit;font-size:12px;color:#16242b;background:#fff}.sup-search input:focus{outline:2px solid #a7c9f4;outline-offset:1px}.sup-scroll{overflow-x:auto}.sup-table{width:100%;min-width:650px;border-collapse:collapse;font-size:13px}.sup-table th{padding:12px 20px;background:#f7f9fb;text-align:left;color:#718087;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap}.sup-table td{padding:16px 20px;border-top:1px solid #edf0f4;vertical-align:middle}.sup-table tbody tr:hover{background:#fafcfe}.sup-table .sup-money{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}.sup-name{color:#16242b;text-decoration:none;font-weight:700}.sup-name:hover{color:#1769d1}.sup-secondary{display:block;margin-top:4px;font-size:11px;color:#718087;overflow-wrap:anywhere}.sup-status{display:inline-block;padding:4px 9px;border-radius:5px;font-size:10px;font-weight:700;background:#edf8ef;color:#166534}.sup-status.inactive{background:#f0f3f6;color:#667885}.sup-account{display:inline-block;padding:7px 11px;border:1px solid #dbe5ef;border-radius:6px;color:#1057b0;background:#f5f9ff;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap}.sup-table .sup-action{text-align:right}.sup-table .sup-empty{text-align:center;padding:44px 20px;color:#718087;font-size:13px;line-height:1.8}.sup-pagination{padding:0 20px}.sup-message{padding:12px 16px;background:#edf8ef;color:#166534;border:1px solid #c7e7ce;border-radius:8px;font-size:13px}.sup-message.error{background:#fff0ee;color:#b42318;border-color:#f1ccc6}
@media(max-width:1050px){.sup-search{width:100%}.sup-search input{flex:1;min-width:140px}}
@media(max-width:600px){.sup-metrics{grid-template-columns:minmax(0,1fr);gap:8px}.sup-card{padding:13px 16px}.sup-card strong{font-size:20px}.sup-card-note{margin-top:4px}.sup-toolbar{padding:15px}.sup-search input{font-size:16px}.sup-table th,.sup-table td{padding:12px 15px}.header-row{gap:12px;flex-wrap:wrap}}
</style>
@endpush
@section('header')
<div class="header-row">
    <div><h1>Suppliers</h1><p class="sup-intro">Contacts, purchases and payments · {{ $currentBranch->name ?? 'Current branch' }}</p></div>
    <a class="sup-button primary" href="{{ route('suppliers.create') }}">+ Add Supplier</a>
</div>
@endsection
@section('content')
<div class="sup-page">
    @if(session('status'))<div class="sup-message" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="sup-message error" role="alert">{{ $errors->first() }}</div>@endif
    <div class="sup-metrics">
        <div class="sup-card"><span class="sup-card-label">Total supplied</span><strong>KES {{ number_format($total / 100, 2) }}</strong><span class="sup-card-note">All recorded purchases</span></div>
        <div class="sup-card"><span class="sup-card-label">Outstanding balance</span><strong>KES {{ number_format($balance / 100, 2) }}</strong><span class="sup-card-note">Remaining supplier payments</span></div>
        <div class="sup-card {{ $overdue > 0 ? 'warning' : '' }}"><span class="sup-card-label">Overdue balance</span><strong>KES {{ number_format($overdue / 100, 2) }}</strong><span class="sup-card-note">Past the invoice due date</span></div>
    </div>
    <section class="sup-list" aria-labelledby="supplier-list-title">
        <div class="sup-toolbar">
            <h2 id="supplier-list-title">Supplier directory <span class="sup-count">{{ number_format($suppliers->total()) }}</span></h2>
            <form method="GET" action="{{ route('suppliers.index') }}" class="sup-search" role="search">
                <input type="search" name="q" value="{{ $search }}" maxlength="100" aria-label="Search suppliers" placeholder="Name, contact person or phone…">
                <button class="sup-button primary" type="submit">Search</button>
                @if($search !== '')<a class="sup-button" href="{{ route('suppliers.index') }}">Reset</a>@endif
            </form>
        </div>
        <div class="sup-scroll">
            <table class="sup-table">
                <thead><tr><th scope="col">Supplier</th><th scope="col">Phone / email</th><th scope="col">Status</th><th scope="col" class="sup-money">Outstanding (KES)</th><th scope="col" class="sup-action">Account</th></tr></thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr>
                        <td><a class="sup-name" href="{{ route('suppliers.show', $supplier->id) }}">{{ $supplier->name }}</a>@if($supplier->contact_name)<span class="sup-secondary">{{ $supplier->contact_name }}</span>@endif</td>
                        <td>{{ $supplier->phone ?: '—' }}@if($supplier->email)<span class="sup-secondary">{{ $supplier->email }}</span>@endif</td>
                        <td><span class="sup-status {{ $supplier->is_active ? '' : 'inactive' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="sup-money">{{ number_format(($balances[$supplier->id] ?? 0) / 100, 2) }}</td>
                        <td class="sup-action"><a class="sup-account" href="{{ route('suppliers.show', $supplier->id) }}">View account &rarr;</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="sup-empty">@if($search !== '')No suppliers match your search. Try another name or reset the search.@else<strong>No suppliers yet</strong><br>Add your first supplier to record purchases and payments.@endif</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sup-pagination">{{ $suppliers->links('pagination.app') }}</div>
    </section>
</div>
@endsection
