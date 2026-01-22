<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiwi POS | @yield('title', 'Dashboard')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar: #0f172a;
            --sidebar-active: #6153f8;
            --bg: #f4f7fb;
            --card: #ffffff;
            --muted: #6b7280;
            --success: #16a34a;
            --danger: #ef4444;
            --info: #4f46e5;
            --accent: #f59e0b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background: var(--bg);
            color: #0f172a;
        }
        .layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }
        .sidebar {
            background: var(--sidebar);
            color: #e5e7eb;
            padding: 24px 0;
            display: flex;
            flex-direction: column;
        }
        .brand {
            font-size: 20px;
            font-weight: 700;
            padding: 0 24px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
        }
        .brand .icon { font-size: 22px; }
        .nav { margin-top: 8px; display: flex; flex-direction: column; gap: 4px; }
        .nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: #e5e7eb;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.15s ease;
        }
        .nav a.active {
            background: var(--sidebar-active);
            color: #fff;
            border-radius: 10px;
        }
        .nav a:hover { background: rgba(255,255,255,0.06); }
        .spacer { flex: 1; }
        .logout {
            margin: 16px 24px 0;
            display: block;
            background: #dc2626;
            color: #fff;
            text-align: center;
            padding: 12px 0;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        main { padding: 26px 28px; }
        .header-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        h1 { margin: 0; font-size: 26px; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--sidebar-active);
            color: #fff;
            border: none;
            padding: 12px 16px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .content { display: grid; grid-template-columns: 2fr 1fr; gap: 18px; }
        .panel {
            background: var(--card);
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .panel h2 { margin: 0 0 8px; font-size: 22px; }
        .kpi {
            background: #edeff6;
            border-radius: 12px;
            padding: 18px;
            margin-top: 12px;
            font-weight: 700;
            color: var(--success);
            font-size: 28px;
        }
        .kpi small { display: block; font-size: 16px; color: #0f172a; margin-top: 6px; }
        .cards { display: flex; flex-direction: column; gap: 12px; }
        .kpi-card {
            border-radius: 14px;
            padding: 20px;
            color: #fff;
            text-align: center;
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        .kpi-card span { display: block; margin-top: 6px; font-weight: 600; }
        .purple { background: #6153f8; }
        .green { background: #10b981; }
        .amber { background: #f59e0b; }
        .blue { background: #2563eb; }
        @media (max-width: 1100px) {
            .content { grid-template-columns: 1fr; }
        }
        @media (max-width: 880px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar {
                flex-direction: row;
                align-items: center;
                gap: 10px;
                padding: 12px;
                overflow-x: auto;
            }
            .brand { padding: 0; margin-right: 8px; }
            .nav { flex-direction: row; flex-wrap: nowrap; }
            .nav a { padding: 10px 12px; border-radius: 8px; }
            .spacer { display: none; }
            main { padding: 18px; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand"><span class="icon">🛒</span> <span>POS SYSTEM</span></div>
            <nav class="nav">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">📊 Dashboard</a>
                <a href="{{ route('stock') }}" class="{{ request()->routeIs('stock') ? 'active' : '' }}">📦 Stock</a>
                <a href="{{ route('sale') }}" class="{{ request()->routeIs('sale') ? 'active' : '' }}">💰 Make a Sale</a>
                <a href="{{ route('products') }}" class="{{ request()->routeIs('products') ? 'active' : '' }}">📦 Products</a>
            </nav>
            <div class="spacer"></div>
            <form method="POST" action="{{ route('logout') }}" style="margin:0 24px 0;">
                @csrf
                <button type="submit" class="logout">🔒 Logout</button>
            </form>
        </aside>
        <main>
            @yield('header')
            @yield('content')
        </main>
    </div>
    @stack('scripts')
</body>
</html>
