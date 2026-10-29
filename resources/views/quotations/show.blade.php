<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quotation {{ $quotation->number }}</title>
    <style>
        * {box-sizing:border-box} body {margin:0; background:#edf2f7; color:#171717; font:14px 'Times New Roman',serif}
        .toolbar {max-width:900px; margin:20px auto; display:flex; gap:12px; align-items:center; flex-wrap:wrap; padding:0 16px; font:14px Arial,sans-serif}
        .toolbar a,.toolbar button {display:inline-block; padding:10px 14px; background:white; border:1px solid #cbd5e1; border-radius:6px; color:#0f172a; text-decoration:none; cursor:pointer; font:inherit}
        .sheet {max-width:900px; margin:20px auto; padding:44px 54px; background:white; box-shadow:0 2px 12px #0001}
        .letterhead {display:flex; justify-content:space-between; gap:24px; align-items:start; padding-bottom:24px; border-bottom:2px solid #00b050}
        .business-name {font:bold 26px Arial,sans-serif; max-width:48%; overflow-wrap:anywhere; color:#111}
        .issuer {text-align:right; max-width:50%}
        .multiline {white-space:pre-wrap; overflow-wrap:anywhere}
        h1 {text-align:center; color:#00a94f; font-size:28px; margin:10px 0}
        .details {display:grid; grid-template-columns:1fr 1fr; gap:26px}
        .customer {border:1px solid #999; padding:9px; overflow-wrap:anywhere}
        table {width:100%; border-collapse:collapse; table-layout:fixed}
        th,td {border:1px solid #aaa; padding:5px 8px; overflow-wrap:anywhere; vertical-align:top}
        th {text-align:left} .metadata th {background:#f0f0f0; width:40%}
        .subject {text-align:center; font-size:17px; margin:14px 0 5px}
        .items thead {background:#00b050; color:white} .items {font-size:13px}
        .right {text-align:right} .center {text-align:center}
        .totals {margin-top:20px} .totals th {width:68%; text-align:right}
        .grand {background:#00b050; color:white; font-weight:bold}
        h2 {font-size:17px; color:#00a94f; border-bottom:1px solid #00b050; margin:14px 0 6px; padding-bottom:2px}
        h3 {font-size:14px; margin:8px 0 4px}
        ol {padding-left:24px; margin:6px 0} li {padding-left:5px; margin-bottom:5px; white-space:pre-wrap; overflow-wrap:anywhere}
        .payment {border:1px solid #aaa; padding:8px; min-height:35px}
        .approvals {display:grid; grid-template-columns:1fr 1fr; margin-top:20px; border:1px solid #aaa; break-inside:avoid}
        .approvals>div {padding:9px; min-height:115px} .approvals>div+div {border-left:1px solid #aaa}
        .approvals h3 {font-size:12px; color:#555; margin:0 0 45px}
        footer {font:italic 12px Arial,sans-serif; text-align:center; margin-top:18px; overflow-wrap:anywhere}
        tr {break-inside:avoid} thead {display:table-header-group}
        @page {size:A4; margin:14mm}
        @media print {body {background:white; font-size:11px} .toolbar {display:none} .sheet {margin:0; padding:0; max-width:none; box-shadow:none} h1 {font-size:25px} .business-name {font-size:23px} .letterhead {padding-bottom:18px} .items {font-size:10px} th,td {padding:4px 6px} h2 {font-size:14px} .totals {margin-top:14px} .approvals>div {min-height:95px} footer {font-size:10px} * {-webkit-print-color-adjust:exact; print-color-adjust:exact}}
        @media screen and (max-width:650px) {.sheet {padding:22px 16px} .details {grid-template-columns:1fr; gap:12px} .items {font-size:11px} th,td {padding:4px} .business-name {font-size:20px}}
    </style>
</head>
<body>
@php($money = fn($cents) => $document['currency'].' '.number_format($cents / 100, 2))
<div class="toolbar">
    <a href="{{ route('quotations.index') }}">Back to Quotations</a>
    <a href="{{ route('quotations.edit', $quotation) }}">Edit Quotation</a>
    <button type="button" onclick="window.print()">Print / Save as PDF</button>
    @if(session('status'))<span role="status">{{ session('status') }}</span>@endif
</div>
<main class="sheet">
    <header class="letterhead"><div class="business-name">{{ $document['issuer_name'] }}</div><div class="issuer multiline">{{ $document['issuer_details'] ?? '' }}</div></header>
    <h1>QUOTATION</h1>
    <section class="details">
        <div class="customer"><div>QUOTATION FOR</div><strong>{{ $quotation->customer_name }}</strong><div class="multiline">{{ $document['customer_details'] ?? '' }}</div></div>
        <table class="metadata"><tbody>
            <tr><th>Quotation No.</th><td>{{ $quotation->number }}</td></tr>
            <tr><th>Date</th><td>{{ $quotation->quotation_date->format('d/m/Y') }}</td></tr>
            <tr><th>Valid Until</th><td>{{ $quotation->valid_until->format('d/m/Y') }}</td></tr>
            <tr><th>Currency</th><td>{{ $document['currency'] }}</td></tr>
            @if($document['incoterms'] ?? null)<tr><th>Incoterms</th><td>{{ $document['incoterms'] }}</td></tr>@endif
        </tbody></table>
    </section>
    <h3 class="subject">{{ $document['title'] ?? 'Quotation for Goods / Services' }}</h3>
    <table class="items"><colgroup><col style="width:5%"><col style="width:39%"><col style="width:9%"><col style="width:9%"><col style="width:19%"><col style="width:19%"></colgroup>
        <thead><tr><th>#</th><th>Item Description</th><th class="center">Qty</th><th class="center">Unit</th><th class="right">Unit Price ({{ $document['currency'] }})</th><th class="right">Total ({{ $document['currency'] }})</th></tr></thead>
        <tbody>@foreach($document['items'] as $item)<tr><td>{{ $loop->iteration }}</td><td class="multiline">{{ $item['description'] }}</td><td class="center">{{ $item['quantity'] }}</td><td class="center">{{ $item['unit'] }}</td><td class="right">{{ number_format($item['unit_price'], 2) }}</td><td class="right"><strong>{{ number_format($item['total_cents'] / 100, 2) }}</strong></td></tr>@endforeach</tbody>
    </table>
    <table class="totals"><tbody>
        <tr><th>Subtotal</th><td class="right">{{ $money($document['subtotal_cents']) }}</td></tr>
        <tr><th>Discount</th><td class="right">{{ $money($document['discount_cents']) }}</td></tr>
        <tr><th>VAT {{ (float)$document['vat_rate'] > 0 ? '('.$document['vat_rate'].'%)' : '' }}</th><td class="right">{{ (float)$document['vat_rate'] > 0 ? $money($document['vat_cents']) : 'Exclusive' }}</td></tr>
        <tr class="grand"><th>GRAND TOTAL ({{ (float)$document['vat_rate'] > 0 ? 'Inclusive' : 'Exclusive' }} of VAT)</th><td class="right">{{ $money($document['total_cents']) }}</td></tr>
    </tbody></table>
    @if($document['terms'] ?? null)<h2>Terms &amp; Conditions</h2><ol>@foreach(preg_split('/\R/', $document['terms'], -1, PREG_SPLIT_NO_EMPTY) as $term)<li>{{ $term }}</li>@endforeach</ol>@endif
    @if(($document['bank_details'] ?? null) || ($document['mpesa_details'] ?? null))
        <h2>Payment Details</h2>
        @if($document['bank_details'] ?? null)<h3>Bank Transfer</h3><div class="payment multiline">{{ $document['bank_details'] }}</div>@endif
        @if($document['mpesa_details'] ?? null)<h3>M-Pesa Payment</h3><div class="payment multiline">{{ $document['mpesa_details'] }}</div>@endif
    @endif
    <section class="approvals">
        <div><h3>COMPANY STAMP &amp; AUTHORISED SIGNATURE</h3><div>Name: {{ $document['signatory'] ?? '' }} &nbsp; Date: {{ $quotation->quotation_date->format('d/m/Y') }}</div></div>
        <div><h3>CUSTOMER APPROVAL</h3><div>Name / Signature / Date: ____________________</div></div>
    </section>
    <footer>Thank you for your business.@if($document['contact'] ?? null) For any queries on this quotation, contact {{ $document['contact'] }}.@endif</footer>
</main>
</body></html>
