@extends('layouts.app')
@section('title', 'Purchase '.$invoice->reference)
@section('header')
<div class="header-row"><div><h1>{{ $invoice->reference }}</h1><p class="supplier-account-subtitle">{{ $supplier->name }} &middot; {{ $currentBranch->name ?? 'Current branch' }}</p></div><div class="supplier-account-actions"><a class="supplier-account-button" href="{{ route('purchases.index') }}">&larr; Purchases</a><a class="supplier-account-button" href="{{ route('suppliers.show',$supplier->id) }}">Supplier account</a><button class="supplier-account-button" type="button" onclick="window.print()">Print invoice</button></div></div>
@endsection
@section('content')
<div class="supplier-area supplier-account">
@include('pages.suppliers.account-style')
<section class="panel">@include('pages.suppliers.invoice', ['expandedInvoice'=>true])</section>
</div>
@endsection
