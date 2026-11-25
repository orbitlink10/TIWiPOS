@extends('layouts.app')
@section('title', 'New Purchase')
@push('styles')
<style>
main{min-width:0}.np-form{display:grid;grid-template-columns:minmax(0,1fr) 290px;gap:16px;align-items:start}.np-main{display:grid;gap:16px;min-width:0}.supplier-account .np-card{background:#fff;border:1px solid #e2e8ee;border-radius:10px;padding:20px;min-width:0}.np-card h2{margin:0 0 5px;font-size:16px}.np-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:18px}.np-wide{grid-column:span 2}.np-form .supplier-field{min-width:0}.np-form .supplier-field>span{font-size:11px;font-weight:700;color:#526473}.np-form .np-hint{display:block;font-size:11px;color:#718087;line-height:1.5;margin:6px 0 0}.np-summary{position:sticky;top:16px;display:grid;gap:16px}.np-summary-head{padding-bottom:16px;border-bottom:1px solid #edf0f4}.np-total{font-size:26px;font-weight:800;color:#16242b;margin-top:7px;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}.np-balance{display:flex;justify-content:space-between;font-size:12px;color:#526473;gap:10px;padding-top:12px;border-top:1px solid #edf0f4}.np-summary .supplier-account-button{width:100%;min-height:44px}.np-products-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.np-products-head input{max-width:260px}.supplier-account .np-lines{min-width:640px;table-layout:fixed;margin:16px 0 12px}.np-lines th:first-child{width:40%}.np-lines th:nth-child(2){width:13%}.np-lines th:nth-child(3){width:19%}.np-lines th:nth-child(4){width:20%}.np-lines th:last-child{width:8%}.supplier-account .np-lines td{padding:12px 6px}.supplier-account .np-lines th{padding:10px 6px}.np-lines .np-line-total{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums;font-size:12px}.np-lines .np-remove{border:0;background:none;color:#b42318;font:inherit;font-size:19px;cursor:pointer;padding:8px}.np-stock-choice{display:flex;gap:9px;align-items:start;font-size:12px!important;line-height:1.5}.np-stock-choice input{margin-top:2px;accent-color:#1769d1;flex-shrink:0}.np-notes summary{font-size:12px!important;padding:0!important;color:#526473}.np-notes textarea{margin-top:12px;resize:vertical}.np-form :is(input,select,textarea){font-family:inherit!important}.np-summary .np-payment-ref{margin-top:0}.np-card [hidden]{display:none!important}
@media(max-width:1250px){.np-form{grid-template-columns:minmax(0,1fr)}.np-summary{position:static}.np-summary-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}}
@media(max-width:650px){.supplier-account .np-card{padding:15px}.np-grid{grid-template-columns:minmax(0,1fr)}.np-wide{grid-column:auto}.np-summary-fields{grid-template-columns:minmax(0,1fr)}.np-products-head input{max-width:100%}.np-total{font-size:24px}}
</style>
@endpush
@section('header')
<div class="header-row"><div><h1>New purchase</h1><p class="supplier-account-subtitle">Supplier invoice &middot; {{ $currentBranch->name ?? 'Current branch' }}</p></div><a class="supplier-account-button" href="{{ route('purchases.index') }}">&larr; Purchases</a></div>
@endsection
@section('content')
<div class="supplier-area supplier-account">
@include('pages.suppliers.account-style')
@if($suppliers->isEmpty())
<section class="panel"><h2>Add a supplier first</h2><p class="supplier-muted">An active supplier is needed to record a purchase.</p><a class="supplier-account-button primary" href="{{ route('suppliers.create') }}">Add supplier</a></section>
@else
@include('pages.suppliers.purchase-form')
@include('pages.suppliers.purchase-script')
@endif
</div>
@endsection
