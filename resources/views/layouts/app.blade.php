<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiwi POS | @yield('title', 'Dashboard')</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f2f6fb;
            --bg-2: #f8fbff;
            --text: #0f1b2d;
            --muted: #5f6f86;
            --accent: #0f7fa7;
            --accent-2: #14b8a6;
            --border: #d8e1ee;
            --card: #ffffff;
            --success: #0f9d6f;
            --danger: #dc3545;
            --sidebar-text: #eaf1ff;
            --sidebar-muted: #b4c6e5;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            background:
                radial-gradient(90rem 50rem at -15% -20%, #dfeeff 0%, transparent 60%),
                radial-gradient(70rem 45rem at 115% -10%, #d7f4ef 0%, transparent 62%),
                linear-gradient(180deg, var(--bg-2) 0%, var(--bg) 100%);
            color: var(--text);
        }

        .layout {
            display: grid;
            grid-template-columns: 290px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(165deg, #0b1f3f 0%, #10305d 58%, #13427d 100%);
            color: var(--sidebar-text);
            padding: 22px 18px 18px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.08);
            box-shadow: 10px 0 35px rgba(7, 20, 45, 0.16);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .brand-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            text-decoration: none;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(145deg, #e2f2ff, #a9d7ff);
            display: grid;
            place-items: center;
            color: #0b2953;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.06em;
            border: 1px solid rgba(255,255,255,0.7);
        }

        .brand-title {
            display: block;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.01em;
            line-height: 1.1;
        }

        .brand-subtitle {
            display: block;
            color: var(--sidebar-muted);
            font-size: 12px;
            font-weight: 600;
            margin-top: 2px;
        }

        .sidebar-toggle {
            display: none;
            border: 1px solid rgba(255,255,255,0.28);
            border-radius: 11px;
            background: rgba(255,255,255,0.12);
            color: #fff;
            font-weight: 700;
            padding: 8px 11px;
            cursor: pointer;
        }

        .nav {
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            color: rgba(234,241,255,0.9);
            text-decoration: none;
            font-weight: 600;
            border-radius: 12px;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
            border: 1px solid transparent;
        }

        .nav-icon {
            min-width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(255,255,255,0.09);
            border: 1px solid rgba(255,255,255,0.14);
            color: #d9e8ff;
            display: grid;
            place-items: center;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .nav a.active {
            background: rgba(255,255,255,0.17);
            color: #fff;
            border-color: rgba(255,255,255,0.24);
            box-shadow: 0 8px 20px rgba(6, 24, 53, 0.22);
        }

        .nav a:hover {
            background: rgba(255,255,255,0.12);
            color: #fff;
            transform: translateX(2px);
        }

        .nav-group {
            display: grid;
            gap: 4px;
        }

        .nav-section {
            display: grid;
            gap: 3px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .nav-section:first-child { padding-top: 0; }
        .nav-section:last-child { border-bottom: 0; }

        .nav-heading {
            margin: 0 12px 5px;
            color: var(--sidebar-muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .nav a { padding: 8px 12px; font-size: 13px; }
        .nav a:focus-visible { outline: 2px solid #a9d7ff; outline-offset: 2px; }

        .nav-sub-link {
            margin-left: 28px;
            padding: 8px 12px 8px 22px;
            font-size: 13px;
            color: rgba(234,241,255,0.78);
            position: relative;
        }

        .nav-sub-link::before {
            content: "";
            position: absolute;
            left: 9px;
            top: 50%;
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: rgba(234,241,255,0.42);
            transform: translateY(-50%);
        }

        .spacer { flex: 1; }

        .sidebar-footer {
            display: grid;
            gap: 10px;
        }

        .branch-chip {
            padding: 10px 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 12px;
            color: #fff;
        }

        .branch-chip-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--sidebar-muted);
            font-weight: 700;
        }

        .branch-chip-name {
            margin-top: 4px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.2;
        }

        .branch-switch-form {
            margin-top: 10px;
            display: grid;
            gap: 8px;
        }

        .branch-switch-select {
            width: 100%;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.35);
            background: rgba(12, 32, 66, 0.55);
            color: #fff;
            padding: 9px 10px;
            font-size: 13px;
            font-weight: 700;
        }

        .branch-switch-select:focus {
            outline: 2px solid rgba(20,184,166,0.35);
            border-color: rgba(255,255,255,0.6);
        }

        .branch-switch-btn {
            width: 100%;
            border: 1px solid rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.15);
            color: #fff;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .logout {
            display: block;
            background: rgba(237, 71, 86, 0.14);
            color: #ffd9de;
            text-align: center;
            padding: 10px 12px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            border: 1px solid rgba(255, 187, 195, 0.4);
            cursor: pointer;
            width: 100%;
        }

        main {
            min-width: 0;
            padding: 30px;
            animation: fade-in-up 280ms ease-out;
        }

        .page-header { display:flex; flex-wrap:wrap; align-items:center; gap:16px; margin-bottom:16px; }
        .page-header-content { flex:1 1 320px; min-width:0; }
        .page-header-content .header-row { margin-bottom:0; }
        .account-bar { display:flex; flex-wrap:wrap; justify-content:flex-end; align-items:center; gap:10px; }
        .account-branch { display:flex; flex-wrap:wrap; align-items:center; gap:14px; padding:9px 14px; border:1px solid var(--border); border-radius:10px; background:#fff; }
        .account-branch-label { display:block; color:var(--muted); font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; }
        .account-branch-name { display:block; margin-top:2px; color:var(--text); font-size:13px; font-weight:800; overflow-wrap:anywhere; }
        .account-switch { display:flex; align-items:center; gap:6px; margin:0; }
        .account-switch select { min-width:0; max-width:200px; min-height:36px; padding:6px 9px; border:1px solid var(--border); border-radius:6px; background:#f8fbff; color:var(--text); font:inherit; font-size:12px; }
        .account-switch button { min-height:36px; padding:6px 10px; border:1px solid var(--border); border-radius:6px; background:#eef5fc; color:#1057b0; font:inherit; font-size:12px; font-weight:700; cursor:pointer; }
        .account-signout { margin:0; }
        .account-signout button { min-height:44px; padding:10px 16px; border:1px solid #fecdd3; border-radius:10px; background:#fff1f2; color:#b42318; font:inherit; font-size:13px; font-weight:700; cursor:pointer; }
        .account-signout button:hover { background:#ffe4e6; }
        .account-bar :is(button,select):focus-visible { outline:3px solid #8cbaf2; outline-offset:2px; }
        @media(max-width:800px) {
            .page-header { gap:12px; }
            .page-header-content { flex-basis:100%; }
            .account-bar { width:100%; gap:8px; }
            .account-branch { flex:1; min-width:0; gap:8px; }
            .account-branch > div { min-width:0; }
            .account-switch select,.account-switch button { min-height:44px; }
            .account-switch select { font-size:16px; }
        }
        @media(max-width:600px) {
            .account-switch { width:100%; }
            .account-switch select { flex:1; width:100%; max-width:none; }
        }

        .header-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        h1 {
            margin: 0;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--accent) 0%, #0f6f9d 100%);
            color: #fff;
            border: none;
            padding: 11px 15px;
            border-radius: 11px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 10px 24px rgba(15, 127, 167, 0.24);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(15, 127, 167, 0.3);
        }

        .content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 18px;
        }

        .panel {
            background: var(--card);
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 18px 45px rgba(12, 30, 60, 0.08);
            border: 1px solid var(--border);
        }

        .panel h2 {
            margin: 0 0 8px;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .cards {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .kpi-card {
            border-radius: 16px;
            padding: 20px;
            color: var(--text);
            text-align: center;
            font-weight: 700;
            box-shadow: 0 14px 30px rgba(0,0,0,0.08);
            background: #fff;
            border: 1px solid var(--border);
        }

        .kpi-card span {
            display: block;
            margin-top: 6px;
            font-weight: 600;
            color: var(--muted);
        }

        .green { background: #e4f7ec; color: #117a39; }
        .amber { background: #fff4e5; color: #b45b00; }
        .blue { background: #e8f5ff; color: #0b6fa4; }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font: inherit;
            color: var(--text);
            background: #fff;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: 2px solid rgba(20,184,166,0.22);
            border-color: rgba(20,184,166,0.45);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .app-pagination {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .app-pagination__meta {
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .app-pagination__meta strong {
            color: var(--text);
        }

        .app-pagination__controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .app-pagination__link,
        .app-pagination__current,
        .app-pagination__separator {
            min-width: 42px;
            height: 42px;
            padding: 0 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            font-size: 14px;
            font-weight: 800;
            line-height: 1;
            text-decoration: none;
        }

        .app-pagination__link {
            color: var(--text);
            transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
        }

        .app-pagination__link:hover {
            transform: translateY(-1px);
            border-color: #bfd0e6;
            background: var(--bg-2);
        }

        .app-pagination__link.is-disabled {
            color: var(--muted);
            background: #f8fbff;
            pointer-events: none;
            cursor: not-allowed;
            transform: none;
        }

        .app-pagination__current {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(135deg, var(--accent) 0%, #0f6f9d 100%);
            box-shadow: 0 10px 24px rgba(15, 127, 167, 0.24);
        }

        .app-pagination__separator {
            color: var(--muted);
            background: transparent;
            border-style: dashed;
        }

        @keyframes fade-in-up {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1100px) {
            .content { grid-template-columns: 1fr; }
        }

        @media (max-width: 980px) {
            .layout { grid-template-columns: 1fr; align-content: start; }

            .sidebar {
                position: static;
                height: auto;
                padding: 14px;
                border-right: none;
                border-bottom: 1px solid rgba(255,255,255,0.16);
            }

            .brand-row { margin-bottom: 0; }
            .sidebar-toggle { display: inline-flex; }

            .nav,
            .sidebar-footer { display: none; }

            .sidebar.open .nav {
                display: flex;
                margin-top: 12px;
            }

            .sidebar.open .sidebar-footer {
                display: grid;
                margin-top: 12px;
            }

            .spacer { display: none; }

            main { padding: 16px; }
            h1 { font-size: 26px; }
            .app-pagination__controls { width: 100%; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="layout">
        @unless(auth()->user()->isPosOnly())
        <aside class="sidebar" id="app-sidebar">
            <div class="brand-row">
                <a href="{{ route('home') }}" class="brand">
                    <span class="brand-mark">TP</span>
                    <span>
                        <span class="brand-title">Tiwi POS</span>
                        <span class="brand-subtitle">Retail Console</span>
                    </span>
                </a>
                <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle navigation" aria-controls="app-navigation" aria-expanded="false">Menu</button>
            </div>
            <nav class="nav" id="app-navigation" aria-label="Main navigation">
                <section class="nav-section" aria-labelledby="nav-overview">
                    <p class="nav-heading" id="nav-overview">Overview</p>
                    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}"><span class="nav-icon">HM</span><span>Dashboard</span></a>
                    <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"><span class="nav-icon">RT</span><span>Reports</span></a>
                </section>

                <section class="nav-section" aria-labelledby="nav-sales">
                    <p class="nav-heading" id="nav-sales">Sales</p>
                    <a href="{{ route('shifts.index') }}" class="{{ request()->routeIs('shifts.*') ? 'active' : '' }}"><span class="nav-icon">SH</span><span>Shifts</span></a>
                    <a href="{{ route('sale') }}" class="{{ request()->routeIs('sale', 'sale.store') ? 'active' : '' }}"><span class="nav-icon">SL</span><span>Point of Sale</span></a>
                    <a href="{{ route('sales.index') }}" class="{{ request()->routeIs('sales.*', 'sale.receipt') ? 'active' : '' }}"><span class="nav-icon">SH</span><span>Sales History</span></a>
                    <a href="{{ route('quotations.index') }}" class="{{ request()->routeIs('quotations.*') ? 'active' : '' }}"><span class="nav-icon">QT</span><span>Quotations</span></a>
                </section>

                <section class="nav-section" aria-labelledby="nav-inventory">
                    <p class="nav-heading" id="nav-inventory">Inventory</p>
                    <a href="{{ route('products') }}" class="{{ request()->routeIs('products*') ? 'active' : '' }}"><span class="nav-icon">PD</span><span>Products</span></a>
                    @if(auth()->user()->canAccessAbility('view_stock'))<a href="{{ route('stock-control.periods.index') }}" class="{{ request()->routeIs('stock-control.*') ? 'active' : '' }}"><span class="nav-icon">SC</span><span>Stock Control</span></a>@endif
                    @if(auth()->user()->canAccessAbility('manage_catalog'))
                        <div class="nav-group">
                            <a href="{{ route('categories.create') }}" class="{{ request()->routeIs('categories.*') ? 'active' : '' }}"><span class="nav-icon">CT</span><span>Categories</span></a>
                        </div>
                        @if(auth()->user()->canViewFinancials())
                            <a href="{{ route('suppliers.index') }}" class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}"><span class="nav-icon">SP</span><span>Suppliers</span></a>
                            <a href="{{ route('purchases.index') }}" class="{{ request()->routeIs('purchases.*') ? 'active' : '' }}"><span class="nav-icon">PU</span><span>Purchases</span></a>
                        @endif
                    @endif
                </section>

                <section class="nav-section" aria-labelledby="nav-operations">
                    <p class="nav-heading" id="nav-operations">Operations</p>
                    <a href="{{ route('services') }}" class="{{ request()->routeIs('services*', 'service-categories.*', 'service-workers.*', 'service-visits.*') ? 'active' : '' }}"><span class="nav-icon">SV</span><span>Delivery</span></a>
                    <a href="{{ route('todos.index') }}" class="{{ request()->routeIs('todos.*') ? 'active' : '' }}"><span class="nav-icon">TD</span><span>To-do List</span></a>
                </section>

                <section class="nav-section" aria-labelledby="nav-management">
                    <p class="nav-heading" id="nav-management">Management</p>
                    @if(auth()->user()->canAccessAbility('manage_staff'))
                        <a href="{{ route('staff.index') }}" class="{{ request()->routeIs('staff.*') ? 'active' : '' }}"><span class="nav-icon">TM</span><span>Staff & Roles</span></a>
                    @endif
                    <a href="{{ route('billing.show') }}" class="{{ request()->routeIs('billing.*', 'payments.*') ? 'active' : '' }}"><span class="nav-icon">BL</span><span>Billing</span></a>
                    @if(auth()->user()->canAccessAbility('manage_profile'))
                        <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}"><span class="nav-icon">SE</span><span>Settings</span></a>
                    @endif
                </section>

                @if(auth()->user()->is_super_admin)
                    <section class="nav-section" aria-labelledby="nav-platform">
                        <p class="nav-heading" id="nav-platform">Platform Admin</p>
                        <a href="{{ route('admin.tenants.index') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}"><span class="nav-icon">AD</span><span>Businesses</span></a>
                        <a href="{{ route('content.index') }}" class="{{ request()->routeIs('content.*') ? 'active' : '' }}"><span class="nav-icon">PG</span><span>Website Pages</span></a>
                    </section>
                @endif
            </nav>
            <div class="spacer"></div>
        </aside>
        @endunless
        <main>
            <div class="page-header">
                <div class="page-header-content">@yield('header')</div>
            @unless(auth()->user()->isPosOnly())
                @include('partials.account-bar')
            @endunless
            </div>
            @yield('content')
        </main>
    </div>
    <script>
        (function () {
            var sidebar = document.getElementById('app-sidebar');
            var toggle = document.getElementById('sidebar-toggle');
            if (!sidebar || !toggle) return;

            var activeLink = sidebar.querySelector('.nav a.active');
            if (activeLink) {
                activeLink.setAttribute('aria-current', 'page');
                requestAnimationFrame(function () {
                    if (!activeLink.getClientRects().length || sidebar.scrollHeight <= sidebar.clientHeight) return;

                    var sidebarBounds = sidebar.getBoundingClientRect();
                    var linkBounds = activeLink.getBoundingClientRect();
                    if (linkBounds.top < sidebarBounds.top || linkBounds.bottom > sidebarBounds.bottom) {
                        sidebar.scrollTop += linkBounds.top - sidebarBounds.top - (sidebar.clientHeight - linkBounds.height) / 2;
                    }
                });
            }

            toggle.addEventListener('click', function () {
                var expanded = sidebar.classList.toggle('open');
                toggle.setAttribute('aria-expanded', String(expanded));
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
