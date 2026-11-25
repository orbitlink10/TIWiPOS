@extends('layouts.app')
@section('title', 'New Purchase')
@section('header')
<div class="header-row"><div><h1>New purchase</h1><p class="supplier-account-subtitle">Record a supplier invoice &middot; {{ $currentBranch->name ?? 'Current branch' }}</p></div><a class="supplier-account-button" href="{{ route('purchases.index') }}">&larr; Purchases</a></div>
@endsection
@section('content')
<div class="supplier-area supplier-account">
@include('pages.suppliers.account-style')
@if(!$supplier)
<section class="panel"><h2>Choose a supplier</h2><p class="supplier-muted">Select the supplier for this purchase before entering products and payment details.</p>
    @if($suppliers->isNotEmpty())
    <form method="GET" action="{{ route('purchases.create') }}"><div class="supplier-grid"><label class="supplier-field">Supplier<select name="supplier_id" required><option value="">Select supplier</option>@foreach($suppliers as $choice)<option value="{{ $choice->id }}">{{ $choice->name }}</option>@endforeach</select></label></div><button class="supplier-account-button primary" type="submit">Continue</button></form>
    @else<p class="supplier-muted">Add an active supplier to record a purchase.</p><a class="supplier-account-button primary" href="{{ route('suppliers.create') }}">Add supplier</a>@endif
</section>
@else
<section class="panel"><div class="supplier-history-head"><div><h2>{{ $supplier->name }}</h2><p class="supplier-muted">{{ $supplier->phone ?: 'No phone on file' }}@if($supplier->email) &middot; {{ $supplier->email }}@endif</p></div><a class="supplier-account-button" href="{{ route('purchases.create') }}">Change supplier</a></div></section>
<section class="panel"><h2>Invoice & products</h2>@include('pages.suppliers.purchase-form')</section>
@include('pages.suppliers.purchase-script')
@endif
</div>
@endsection
