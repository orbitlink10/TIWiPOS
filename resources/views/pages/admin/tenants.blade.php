@extends('layouts.app')

@section('title', 'Tenants')

@section('header')
    <div class="header-row">
        <h1>Tenants</h1>
        <a class="btn" href="{{ route('home') }}">Back</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>All businesses</h2>
        @if (session('status'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                {{ session('status') }}
            </div>
        @endif
        <div style="overflow:auto; margin-top:12px;">
            <table style="width:100%; min-width:720px; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="background:#f7f7fb;">
                        <th style="padding:10px; text-align:left;">Name</th>
                        <th style="padding:10px; text-align:left;">Status</th>
                        <th style="padding:10px; text-align:left;">Sub status</th>
                        <th style="padding:10px; text-align:left;">Period end</th>
                        <th style="padding:10px; text-align:left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tenants as $tenant)
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td style="padding:10px;">{{ $tenant->name }}</td>
                            <td style="padding:10px;">{{ $tenant->status }}</td>
                            <td style="padding:10px;">{{ $tenant->subscription_status }}</td>
                            <td style="padding:10px;">{{ optional($tenant->current_period_end)->toDateString() }}</td>
                            <td style="padding:10px; display:flex; gap:8px; flex-wrap:wrap;">
                                <form method="POST" action="{{ route('admin.tenants.activate', $tenant) }}">
                                    @csrf
                                    <button class="btn" style="padding:8px 12px; font-size:12px; background:#22c55e;">Activate</button>
                                </form>
                                <form method="POST" action="{{ route('admin.tenants.deactivate', $tenant) }}">
                                    @csrf
                                    <button class="btn" style="padding:8px 12px; font-size:12px; background:#ef4444;">Deactivate</button>
                                </form>
                                <form method="POST" action="{{ route('admin.tenants.impersonate', $tenant) }}">
                                    @csrf
                                    <button class="btn" style="padding:8px 12px; font-size:12px; background:#0ea5e9;">Impersonate</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
