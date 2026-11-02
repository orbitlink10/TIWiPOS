@extends('layouts.app')
@section('title', 'Quotation Settings')
@push('styles')
<style>
.defaults-form {max-width:960px;display:grid;gap:18px;background:white;padding:24px;border-radius:12px}
.defaults-fields {display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.defaults-form label {display:grid;gap:6px;font-weight:600}
.defaults-form input,.defaults-form textarea,.defaults-form select {width:100%;box-sizing:border-box;padding:10px;border:1px solid #94a3b8;border-radius:6px;font:inherit}
.defaults-form textarea {min-height:120px}
@media(max-width:650px){.defaults-fields{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<h1>Quotation Settings</h1>
<p>Defaults apply to new quotations across your business. You can adjust the details while preparing each quotation. Saved quotations keep their existing details and logo.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="defaults-form" method="POST" enctype="multipart/form-data" action="{{ route('quotations.settings.update') }}">
@csrf @method('PUT')
<div class="defaults-fields">
<label>Business Name<input name="issuer_name" maxlength="255" required value="{{ old('issuer_name', $defaults['issuer_name'] ?? $business->name) }}"></label>
<label>Address, Contacts and Tax PIN<textarea name="issuer_details" maxlength="2000">{{ old('issuer_details', $defaults['issuer_details'] ?? '') }}</textarea></label>
<label>Contact for Quotation Queries<input name="contact" maxlength="255"  value="{{ old('contact', $defaults['contact'] ?? '') }}"></label>
<label>Authorised Signatory<input name="signatory" maxlength="255"  value="{{ old('signatory', $defaults['signatory'] ?? '') }}"></label>
<label>Incoterms / Delivery Basis<input name="incoterms" maxlength="255"  value="{{ old('incoterms', $defaults['incoterms'] ?? '') }}"></label>
<label>Terms & Conditions (one per line)<textarea name="terms" maxlength="10000">{{ old('terms', $defaults['terms'] ?? '') }}</textarea></label>
<label>Bank Details<textarea name="bank_details" maxlength="2000">{{ old('bank_details', $defaults['bank_details'] ?? '') }}</textarea></label>
<label>M-Pesa Details<textarea name="mpesa_details" maxlength="1000">{{ old('mpesa_details', $defaults['mpesa_details'] ?? '') }}</textarea></label>
<label>Currency<select name="currency">@foreach(['KES','USD','UGX','TZS','EUR','GBP'] as $currency)<option @selected(old('currency', $defaults['currency'] ?? 'KES') === $currency)>{{ $currency }}</option>@endforeach</select></label>
<label>VAT Rate (%)<input type="number" name="vat_rate" min="0" max="100" step="0.01" required value="{{ old('vat_rate', $defaults['vat_rate'] ?? 0) }}"></label>
</div>
<label>Business Logo<input type="file" name="logo" accept="image/png,image/jpeg"></label>
<p>PNG or JPEG, up to 1 MB and 2000 ? 2000 pixels. Appears on previews, printed quotations and PDF attachments.</p>
@if($logo = \App\Support\QuotationPdf::logoData($defaults['logo_path'] ?? null))
<img src="{{ $logo }}" alt="Current business logo" style="max-width:180px;max-height:100px">
<label style="display:flex;align-items:center"><input style="width:auto" type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))>Remove logo from new quotations</label>
@endif
<div><button class="btn primary" type="submit">Save Settings</button> <a class="btn" href="{{ route('quotations.index') }}">Back to Quotations</a></div>
</form>
@endsection
