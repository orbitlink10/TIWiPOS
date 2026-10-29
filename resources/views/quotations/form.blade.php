@extends('layouts.app')
@section('title', $quotation->exists ? 'Edit Quotation' : 'Prepare Quotation')
@push('styles')
<style>
    .quote-form {max-width:1100px; display:grid; gap:20px}
    .quote-form fieldset {border:1px solid var(--border); border-radius:12px; padding:20px; background:white; min-width:0}
    .quote-form legend {font-size:18px; font-weight:700; padding:0 8px}
    .quote-fields {display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px}
    .quote-form label {display:grid; gap:6px; font-weight:600; align-content:start}
    .quote-form input,.quote-form select,.quote-form textarea {width:100%; box-sizing:border-box; padding:10px; border:1px solid #94a3b8; border-radius:6px; font:inherit; background:white; color:#0f172a}
    .quote-form textarea {min-height:105px; resize:vertical}
    .quote-items {overflow:auto}
    .quote-items table {width:100%; min-width:720px}
    .quote-items th {text-align:left}
    .quote-items td {padding:6px 3px; vertical-align:top}
    .quote-items input {min-width:80px}
    .quote-items .description {min-width:240px}
    .quote-errors {background:#fef2f2; color:#991b1b; padding:16px; border-radius:8px}
    @media(max-width:650px) {.quote-fields {grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@php
    $value = fn($key, $default = '') => old($key, $document[$key] ?? $default);
    $submittedRows = old('items', $document['items']);
    $rows = is_array($submittedRows) && count($submittedRows) ? array_values($submittedRows) : [['description' => '', 'quantity' => 1, 'unit' => 'PCS', 'unit_price' => 0]];
@endphp
<h1>{{ $quotation->exists ? 'Edit Quotation' : 'Prepare Quotation' }}</h1>
<p>Enter your business and customer details. Save to preview, print or save as PDF.</p>
@if($errors->any())
<div class="quote-errors" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form class="quote-form" method="POST" action="{{ $quotation->exists ? route('quotations.update', $quotation) : route('quotations.store') }}">
    @csrf
    @if($quotation->exists) @method('PUT') @endif
    <fieldset><legend>Business Details</legend><div class="quote-fields">
        <label>Business Name<input name="issuer_name" required maxlength="255" value="{{ $value('issuer_name') }}"></label>
        <label>Contact for Quotation Queries<input name="contact" maxlength="255" value="{{ $value('contact') }}" placeholder="Email or phone"></label>
        <label>Address, Contacts and Tax PIN<textarea name="issuer_details" maxlength="2000">{{ $value('issuer_details') }}</textarea></label>
        <label>Authorised Signatory Name<input name="signatory" maxlength="255" value="{{ $value('signatory') }}"></label>
    </div></fieldset>
    <fieldset><legend>Customer and Quotation Details</legend><div class="quote-fields">
        <label>Customer Name<input name="customer_name" required maxlength="255" value="{{ old('customer_name', $quotation->customer_name) }}"></label>
        <label>Quotation No.<input name="number" required maxlength="80" value="{{ old('number', $quotation->number ?? 'QT-'.now('Africa/Nairobi')->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(6))) }}"></label>
        <label>Date<input type="date" name="quotation_date" required value="{{ old('quotation_date', $quotation->quotation_date?->format('Y-m-d') ?? now('Africa/Nairobi')->toDateString()) }}"></label>
        <label>Valid Until<input type="date" name="valid_until" required value="{{ old('valid_until', $quotation->valid_until?->format('Y-m-d') ?? now('Africa/Nairobi')->addDays(14)->toDateString()) }}"></label>
        <label>Customer Address, Registration / Tax No. and Attention<textarea name="customer_details" maxlength="2000">{{ $value('customer_details') }}</textarea></label>
        <label>Quotation Subject<input name="title" maxlength="255" value="{{ $value('title') }}" placeholder="Quotation for goods or services"></label>
        <label>Currency<select name="currency">@foreach(['KES','USD','UGX','TZS','EUR','GBP'] as $currency)<option @selected($value('currency') === $currency)>{{ $currency }}</option>@endforeach</select></label>
        <label>Incoterms / Delivery Basis<input name="incoterms" maxlength="255" value="{{ $value('incoterms') }}" placeholder="e.g. FOB Kenya"></label>
    </div></fieldset>
    <fieldset><legend>Items</legend>
        <div class="quote-items"><table><thead><tr><th>Description</th><th>Quantity</th><th>Unit</th><th>Unit Price</th><th></th></tr></thead><tbody id="quotation-items">
            @foreach($rows as $index => $item)
                <tr>
                    <td><input class="description" aria-label="Item description" name="items[{{ $index }}][description]" required maxlength="1000" value="{{ $item['description'] ?? '' }}"></td>
                    <td><input aria-label="Quantity" type="number" step="0.001" min="0.001" max="1000000" name="items[{{ $index }}][quantity]" required value="{{ $item['quantity'] ?? 1 }}"></td>
                    <td><input aria-label="Unit" name="items[{{ $index }}][unit]" required maxlength="30" value="{{ $item['unit'] ?? 'PCS' }}"></td>
                    <td><input aria-label="Unit price" type="number" step="0.01" min="0" max="999999999" name="items[{{ $index }}][unit_price]" required value="{{ $item['unit_price'] ?? 0 }}"></td>
                    <td><button type="button" class="btn remove-item" aria-label="Remove item">Remove</button></td>
                </tr>
            @endforeach
        </tbody></table></div>
        <button class="btn" type="button" id="add-quotation-item">Add Item</button>
        <p>Totals are calculated when you save. VAT is added after discount. Use 0% for a quotation exclusive of VAT.</p>
        <div class="quote-fields">
            <label>Discount Amount<input name="discount" type="number" min="0" max="999999999" step="0.01" required value="{{ $value('discount', 0) }}"></label>
            <label>VAT Rate (%)<input name="vat_rate" type="number" min="0" max="100" step="0.01" required value="{{ $value('vat_rate', 0) }}"></label>
        </div>
    </fieldset>
    <fieldset><legend>Terms and Payment Details</legend><div class="quote-fields">
        <label>Terms &amp; Conditions (one per line)<textarea name="terms" maxlength="10000" style="min-height:180px">{{ $value('terms') }}</textarea></label>
        <label>Bank Transfer Details<textarea name="bank_details" maxlength="2000" placeholder="Account Name:&#10;Account No.:&#10;Bank:&#10;Branch:&#10;Currency:&#10;SWIFT Code:">{{ $value('bank_details') }}</textarea></label>
        <label>M-Pesa Payment Details<textarea name="mpesa_details" maxlength="1000" placeholder="Paybill / Till:&#10;Account No.:">{{ $value('mpesa_details') }}</textarea></label>
    </div></fieldset>
    <div><button class="btn primary" type="submit">Save &amp; Preview Quotation</button> <a class="btn" href="{{ route('quotations.index') }}">Back to Quotations</a></div>
</form>
<template id="quotation-item-template"><tr>
    <td><input class="description" aria-label="Item description" data-field="description" required maxlength="1000"></td>
    <td><input aria-label="Quantity" data-field="quantity" type="number" min="0.001" max="1000000" step="0.001" value="1" required></td>
    <td><input aria-label="Unit" data-field="unit" value="PCS" maxlength="30" required></td>
    <td><input aria-label="Unit price" data-field="unit_price" type="number" min="0" max="999999999" step="0.01" value="0" required></td>
    <td><button type="button" class="btn remove-item" aria-label="Remove item">Remove</button></td>
</tr></template>
@endsection
@push('scripts')
<script>
(() => {
    const body = document.getElementById('quotation-items');
    let nextIndex = {{ count($rows) ? max(array_keys($rows)) + 1 : 0 }};
    document.getElementById('add-quotation-item').addEventListener('click', () => {
        if (body.rows.length >= 100) return;
        const row = document.getElementById('quotation-item-template').content.cloneNode(true);
        row.querySelectorAll('[data-field]').forEach(input => input.name = `items[${nextIndex}][${input.dataset.field}]`);
        nextIndex++;
        body.appendChild(row);
        body.lastElementChild.querySelector('input').focus();
    });
    body.addEventListener('click', event => {
        const button = event.target.closest('.remove-item');
        if (button && body.rows.length > 1) button.closest('tr').remove();
    });
})();
</script>
@endpush
