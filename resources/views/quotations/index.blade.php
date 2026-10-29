@extends('layouts.app')
@section('title', 'Quotation')
@section('content')
<div class="card" style="padding:24px; overflow:auto;">
    <h1>Quotation</h1>
    <p>Prepare, save and print customer quotations.</p>
    <a class="btn primary" href="{{ route('quotations.create') }}">Prepare Quotation</a>
    <table style="width:100%; border-collapse:collapse; margin-top:24px;">
        <thead><tr><th scope="col">Quotation No.</th><th scope="col">Customer</th><th scope="col">Date</th><th scope="col">Valid Until</th><th scope="col">Total</th><th scope="col">Actions</th></tr></thead>
        <tbody>
        @forelse($quotations as $quotation)
            <tr>
                <td>{{ $quotation->number }}</td><td>{{ $quotation->customer_name }}</td>
                <td>{{ $quotation->quotation_date->format('d/m/Y') }}</td><td>{{ $quotation->valid_until->format('d/m/Y') }}</td>
                <td>{{ $quotation->document['currency'] }} {{ number_format($quotation->document['total_cents'] / 100, 2) }}</td>
                <td><a href="{{ route('quotations.show', $quotation) }}">View / Print</a> · <a href="{{ route('quotations.edit', $quotation) }}">Edit</a></td>
            </tr>
        @empty
            <tr><td colspan="6" style="padding:24px;">No quotations yet. Prepare your first quotation above.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $quotations->links() }}
</div>
@endsection
