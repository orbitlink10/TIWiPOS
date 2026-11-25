@extends('layouts.app')
@section('title', 'Reports')
@push('styles')
<style>
main{min-width:0}
.report-main{grid-template-columns:minmax(0,1fr)}.report-main>*{min-width:0}
.reports-shell{display:grid;grid-template-columns:220px minmax(0,1fr);gap:16px;align-items:start}.report-nav,.report-panel{background:#fff;border:1px solid #e2e8ee;border-radius:10px;padding:16px}.report-nav{max-height:calc(100dvh - 40px);overflow-y:auto;position:sticky;top:16px;padding:10px}.report-nav input{width:100%;padding:9px;border:1px solid #cdd8e3;border-radius:6px;font:inherit;font-size:12px}.report-nav h2{font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:#718087;margin:18px 8px 6px}.report-nav a{display:block;padding:9px;border-radius:6px;color:#334155;text-decoration:none;font-size:12px;font-weight:700}.report-nav a:hover{background:#eef4fb}.report-nav a.active{background:#1769d1;color:white}.report-main{min-width:0;display:grid;gap:14px}.report-heading{display:flex;justify-content:space-between;gap:12px;align-items:start;flex-wrap:wrap}.report-heading h2{margin:0;font-size:20px}.report-note{font-size:12px;color:#667885;line-height:1.7;margin:6px 0 0}.report-tools{display:flex;gap:8px}.report-button{display:inline-block;padding:9px 13px;border:1px solid #ccd8e5;border-radius:6px;background:#fff;color:#19344e;text-decoration:none;font:inherit;font-size:12px;font-weight:700;cursor:pointer}.report-button.primary{background:#1769d1;border-color:#1769d1;color:#fff}.report-filters{display:flex;align-items:end;gap:10px;flex-wrap:wrap;margin-top:18px}.report-filters label{display:grid;gap:5px;font-size:11px;font-weight:700;color:#667885}.report-filters input{padding:9px;border:1px solid #cdd8e3;border-radius:6px;font:inherit;color:#16242b;min-width:0}.report-filters .report-query{flex:1;min-width:140px}.report-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}.report-kpi{background:#fff;border:1px solid #e2e8ee;border-radius:10px;padding:15px}.report-kpi span{display:block;font-size:11px;color:#718087}.report-kpi strong{display:block;font-size:20px;margin-top:5px;font-variant-numeric:tabular-nums}.report-table-wrap{overflow-x:auto}.report-table{width:100%;border-collapse:collapse;font-size:12px}.report-table th{padding:11px 10px;background:#f5f7fa;color:#667885;font-size:10px;text-transform:uppercase;letter-spacing:.04em;text-align:left;white-space:nowrap}.report-table td{padding:12px 10px;border-bottom:1px solid #edf0f4;vertical-align:top}.report-table .numeric{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}.report-empty{text-align:center!important;padding:40px!important;color:#718087}.report-error{padding:12px;background:#fff0ee;color:#b42318;border-radius:8px;margin-bottom:14px}.report-pagination{margin-top:16px}.reports-shell [hidden]{display:none!important}.print-context{display:none}.report-filters select{min-height:38px;max-width:220px;padding:8px 10px;border:1px solid #cdd8e3;border-radius:6px;background:#fff;color:var(--text);font:inherit;font-size:13px}
.report-menu-toggle{display:none}
@media(max-width:1100px){.reports-shell{grid-template-columns:1fr}.report-nav{position:static;max-height:none}.report-menu-toggle{display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;min-height:44px;padding:10px;border:0;background:#fff;color:#334155;font:inherit;text-align:left;cursor:pointer}.report-menu-toggle strong{color:#1769d1;font-size:12px}.report-nav:not(.is-open) #report_nav_content{display:none}.report-nav.is-open #report_nav_content{margin-top:10px}.report-nav-links{display:flex;gap:4px;flex-wrap:wrap}.report-nav h2{width:100%;margin-top:10px}.report-nav input{max-width:350px}}
@media(max-width:600px){.report-panel{padding:12px}.report-filters label{flex:1 1 130px}.report-filters input,.report-filters select{width:100%;max-width:none;min-height:44px;font-size:16px}.report-tools{width:100%}.report-button{min-height:44px;display:inline-flex;align-items:center;justify-content:center}.report-kpi strong{font-size:17px}.report-heading h2{font-size:18px}}
@media print{body{background:#fff!important;color:#000}.layout{display:block}.sidebar,.page-header,.account-bar,.report-nav,.report-filters,.report-tools,.report-pagination,.header-row{display:none!important}main{padding:0!important}.reports-shell{display:block}.report-main{display:block}.report-panel,.report-kpi{border:1px solid #ccc;box-shadow:none;break-inside:avoid}.report-panel{margin-bottom:12px}.report-kpis{margin:12px 0}.report-table thead{display:table-header-group}.report-table tr{break-inside:avoid}.print-context{display:block;font-size:11px;margin-top:8px}.report-table-wrap{overflow:visible}.report-table{font-size:10px}}
</style>
@endpush
@section('header')
<div class="header-row"><div><h1>Reports</h1><p class="report-note">Sales, stock and purchasing · {{ $reportBranchName }}</p></div></div>
@endsection
@section('content')
@if($errors->any())<div class="report-error" role="alert">{{ $errors->first() }}</div>@endif
<div class="reports-shell">
    <aside class="report-nav" aria-label="Available reports">
        <button type="button" id="report_menu_toggle" class="report-menu-toggle" aria-controls="report_nav_content" aria-expanded="false"><span>Choose a report</span><strong>{{ $reports[$report][0] }}</strong></button>
        <div id="report_nav_content">
        <input type="search" id="report_search" aria-label="Find a report" placeholder="Find a report…">
        <div class="report-nav-links">
        @foreach(collect($reports)->groupBy(fn($item) => $item[1], preserveKeys: true) as $group => $items)
            <h2>{{ $group }}</h2>
            @foreach($items as $key => $item)
                <a href="{{ route('reports.index', ['report'=>$key, 'branch_id'=>$branchFilter, 'from'=>$from, 'to'=>$to]) }}" class="{{ $report === $key ? 'active' : '' }}" @if($report === $key) aria-current="page" @endif>{{ $item[0] }}</a>
            @endforeach
        @endforeach
        @if(auth()->user()->canAccessAbility('view_stock'))<h2>Stock Control</h2><a href="{{ route('stock-control.variance') }}">Stock variance report</a>@endif
        </div>
        <p id="no_reports" class="report-note" hidden>No matching reports.</p>
        </div>
    </aside>
    <div class="report-main">
        <section class="report-panel">
            <div class="report-heading">
                <div><h2>{{ $reports[$report][0] }}</h2><p class="report-note">{{ $reports[$report][2] }}</p></div>
                <div class="report-tools">
                    <a class="report-button" href="{{ route('reports.index', ['report'=>$report, 'branch_id'=>$branchFilter, 'from'=>$from, 'to'=>$to, 'q'=>$search, 'format'=>'csv']) }}">Export CSV</a>
                    <button class="report-button" type="button" onclick="window.print()">Print page</button>
                </div>
            </div>
            <p class="report-note">{{ $currentStock ? 'Current stock snapshot · '.now()->format('d M Y, H:i') : \Carbon\Carbon::parse($from)->format('d M Y').' – '.\Carbon\Carbon::parse($to)->format('d M Y') }} · {{ $reportBranchName }}@if(!auth()->user()->canViewAllSales() && in_array($report, ['sales','daily-sales','product-sales','payments'])) · Your sales only @endif</p>
            <form class="report-filters" action="{{ route('reports.index') }}" method="GET">
                <input type="hidden" name="report" value="{{ $report }}">
                <label>Branch<select name="branch_id"><option value="all" @selected($branchFilter === 'all')>All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) $branchFilter === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></label>
                @if(!$currentStock)
                    <label>From<input type="date" name="from" value="{{ $from }}" required></label>
                    <label>To<input type="date" name="to" value="{{ $to }}" required></label>
                @endif
                @if(!in_array($report, ['daily-sales','payments']))<label class="report-query">Search<input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Search this report…"></label>@endif
                <button type="submit" class="report-button primary">Apply filters</button>
                <a class="report-button" href="{{ route('reports.index', ['report'=>$report, 'branch_id'=>$branchFilter]) }}">Reset</a>
            </form>
            <p class="print-context">Printed {{ now()->format('d M Y H:i') }} · Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }} · {{ auth()->user()->business->name ?? config('app.name') }}</p>
        </section>
        <div class="report-kpis">
            <div class="report-kpi"><span>{{ $report === 'sales' ? 'Receipts' : 'Report rows' }}</span><strong>{{ number_format($totals->row_count) }}</strong></div>
            @foreach($metrics as $key => $label)<div class="report-kpi"><span>{{ $label }}</span><strong>{{ $formatValue($totals->{$key}, $columns[$key][1]) }}</strong></div>@endforeach
        </div>
        <section class="report-panel">
            <p class="report-note" style="margin:0 0 12px">{{ number_format($rows->total()) }} results · Totals cover all matching rows. CSV exports all matching rows.</p>
            <div class="report-table-wrap">
                <table class="report-table">
                    <thead><tr>@foreach($columns as [$label,$type])<th scope="col" class="{{ in_array($type,['money','integer']) ? 'numeric' : '' }}">{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>@foreach($columns as $key => [$label,$type])<td class="{{ in_array($type,['money','integer']) ? 'numeric' : '' }}">@if($report === 'sales' && $key === 'sale_number')<a href="{{ route('sale.receipt', $row->id) }}">{{ $row->{$key} }}</a>@else{{ $formatValue($row->{$key},$type) }}@endif</td>@endforeach</tr>
                        @empty<tr><td class="report-empty" colspan="{{ count($columns) }}">No records match these filters.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
            <div class="report-pagination">{{ $rows->links('pagination.app') }}</div>
        </section>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('report_menu_toggle').addEventListener('click', function () {
    const open = this.closest('.report-nav').classList.toggle('is-open');
    this.setAttribute('aria-expanded', String(open));
});
document.getElementById('report_search').addEventListener('input', function () {
    const term = this.value.trim().toLowerCase();
    const links = Array.from(document.querySelectorAll('.report-nav-links a'));
    links.forEach(link => { link.hidden = !link.textContent.toLowerCase().includes(term); });
    document.querySelectorAll('.report-nav-links h2').forEach(heading => {
        let item = heading.nextElementSibling;
        let visible = false;
        while (item && item.tagName !== 'H2') { if (!item.hidden) visible = true; item = item.nextElementSibling; }
        heading.hidden = !visible;
    });
    document.getElementById('no_reports').hidden = links.some(link => !link.hidden);
});
</script>
@endpush
