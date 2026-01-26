<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiwi POS | @yield('title', 'Dashboard')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar: #ffffff;
            --sidebar-active: #e8f5ff;
            --accent: #00a5ff;
            --text: #0f172a;
            --muted: #6b7280;
            --bg: #f7fbff;
            --card: #ffffff;
            --border: #e5edf5;
            --success: #16a34a;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }
        .sidebar {
            background: var(--sidebar);
            color: var(--text);
            padding: 24px 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border);
        }
        .brand {
            font-size: 20px;
            font-weight: 800;
            padding: 0 24px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
            text-decoration: none;
        }
        .brand .icon { font-size: 22px; }
        .nav { margin-top: 8px; display: flex; flex-direction: column; gap: 4px; }
        .nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: var(--text);
            text-decoration: none;
            font-weight: 600;
            border-radius: 10px;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .nav a.active { background: var(--sidebar-active); color: var(--text); }
        .nav a:hover { background: #f2f6fb; }
        .spacer { flex: 1; }
        .logout {
            margin: 16px 24px 0;
            display: block;
            background: var(--danger);
            color: #fff;
            text-align: center;
            padding: 12px 0;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
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
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 12px 16px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }
        .content { display: grid; grid-template-columns: 2fr 1fr; gap: 18px; }
        .panel {
            background: var(--card);
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
        }
        .panel h2 { margin: 0 0 8px; font-size: 22px; }
        .kpi {
            background: #e8f5ff;
            border-radius: 12px;
            padding: 18px;
            margin-top: 12px;
            font-weight: 700;
            color: var(--text);
            font-size: 28px;
        }
        .kpi small { display: block; font-size: 16px; color: var(--muted); margin-top: 6px; }
        .cards { display: flex; flex-direction: column; gap: 12px; }
        .kpi-card {
            border-radius: 14px;
            padding: 20px;
            color: var(--text);
            text-align: center;
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            background: #fff;
            border: 1px solid var(--border);
        }
        .kpi-card span { display: block; margin-top: 6px; font-weight: 600; color: var(--muted); }
        .purple { background: #e9ddff; color: #4b3aa7; }
        .green { background: #e4f7ec; color: #117a39; }
        .amber { background: #fff4e5; color: #b45b00; }
        .blue { background: #e8f5ff; color: #0b6fa4; }
        @media (max-width: 1100px) { .content { grid-template-columns: 1fr; } }
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
            <a href="{{ route('home') }}" class="brand"><span class="icon">🛒</span> <span>POS SYSTEM</span></a>
            <nav class="nav">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
                <a href="{{ route('stock') }}" class="{{ request()->routeIs('stock') ? 'active' : '' }}">Stock</a>
                <a href="{{ route('sale') }}" class="{{ request()->routeIs('sale') ? 'active' : '' }}">Make a Sale</a>
                <a href="{{ route('products') }}" class="{{ request()->routeIs('products') ? 'active' : '' }}">Products</a>
                <a href="{{ route('branches.index') }}" class="{{ request()->routeIs('branches.*') ? 'active' : '' }}">Branches</a>
                <a href="{{ route('content.index') }}" class="{{ request()->routeIs('content.index') || request()->routeIs('content.create') ? 'active' : '' }}">Content</a>
            </nav>
            @isset($currentBranch)
                <div style="margin:12px 24px; padding:10px 12px; background:#f2f6fb; border-radius:10px; color:#0f172a;">
                    <div style="font-size:12px; text-transform:uppercase; letter-spacing:0.6px; color:#6b7280;">Active branch</div>
                    <div style="font-weight:700;">{{ $currentBranch->name ?? 'Unknown' }}</div>
                </div>
            @endisset
            <div class="spacer"></div>
            <form method="POST" action="{{ route('logout') }}" style="margin:0 24px 0;">
                @csrf
                <button type="submit" class="logout">Logout</button>
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
