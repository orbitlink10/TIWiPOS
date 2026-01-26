<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiwi POS | Start selling today</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f7fbff;
            --card: #ffffff;
            --accent: #00a5ff;
            --accent-dark: #0083cc;
            --text: #0f172a;
            --muted: #657089;
            --border: #e5edf5;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            color: var(--text);
            background: var(--bg);
        }
        a { color: inherit; text-decoration: none; }
        header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 5vw;
        }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 18px; }
        .logo-dot { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg,#00a5ff,#7dd3fc); display: grid; place-items: center; color: #fff; font-weight: 800; }
        nav { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
        nav a { font-weight: 600; color: #1f2937; padding: 8px 10px; border-radius: 10px; }
        nav a:hover { background: #f2f6fb; }
        .actions { display: flex; gap: 10px; align-items: center; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; border-radius: 12px; border: none; cursor: pointer; font-weight: 700; text-decoration: none; transition: transform 0.1s ease, box-shadow 0.2s ease; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(0,0,0,0.08); }
        .btn.primary { background: var(--accent); color: #fff; }
        .btn.primary:active { background: var(--accent-dark); }
        .btn.ghost { background: #fff; color: #0f172a; border: 1px solid var(--border); }
        .hero {
            padding: 56px 5vw 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 36px;
            align-items: center;
        }
        .hero-card {
            background: linear-gradient(135deg, #f4fffe 0%, #f7fbff 100%);
            border: 1px solid #dff5f8;
            border-radius: 28px;
            padding: 32px;
            box-shadow: 0 28px 90px rgba(0,165,255,0.12);
        }
        .pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: #e7f8ef; color: #168a4c; font-weight: 700; font-size: 13px; }
        h1 { margin: 16px 0 10px; font-size: 44px; line-height: 1.05; color: #0f172a; }
        .lead { margin: 0 0 20px; color: var(--muted); font-size: 17px; }
        .hero-buttons { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px; }
        .device-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 14px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.06);
        }
        .device-card img { width: 100%; border-radius: 14px; display: block; }
        .check-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap: 10px; margin-top: 18px; color: var(--muted); }
        .check-item { display: flex; align-items: center; gap: 8px; }
        .check-icon { color: #16a34a; }
        footer { padding: 26px 5vw 32px; text-align: center; color: var(--muted); font-size: 14px; }
        @media (max-width: 720px) { h1 { font-size: 36px; } header { padding: 12px 18px; } .hero { padding: 38px 18px; } }
    </style>
</head>
<body>
    <header>
        <div class="brand">
            <div class="logo-dot">TP</div>
            <span>Tiwi POS</span>
        </div>
        <nav>
            <a href="{{ route('home') }}">Features</a>
            @auth
                <a href="{{ route('products') }}">Products</a>
                <a href="{{ route('sale') }}">POS</a>
                <a href="{{ route('content.index') }}">Blog</a>
                <a href="{{ route('summary') }}">Reports</a>
            @endauth
        </nav>
        <div class="actions">
            @guest
                <a class="btn ghost" href="{{ route('login') }}">Sign in</a>
                <a class="btn primary" href="{{ route('register') }}">Start free</a>
            @else
                <a class="btn primary" href="{{ route('sale') }}">Open POS</a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn ghost">Log out</button>
                </form>
            @endguest
        </div>
    </header>

    <section class="hero">
        <div class="hero-card">
            <span class="pill">• Self-onboarding · 10–20 minutes</span>
            <h1>Start selling today with Tiwi POS</h1>
            <p class="lead">Online POS for retail shops, boutiques, phone & electronics stores, and takeaways. Create your account, add items, and start selling the same day—no training or setup calls.</p>
            <div class="hero-buttons">
                <a class="btn primary" href="{{ route('register') }}">Start free – No setup fees</a>
                <a class="btn ghost" href="{{ route('login') }}">Talk to us</a>
            </div>
            <div class="check-list">
                <div class="check-item"><span class="check-icon">✓</span><span>Sign up online</span></div>
                <div class="check-item"><span class="check-icon">✓</span><span>Self-onboard, no training</span></div>
                <div class="check-item"><span class="check-icon">✓</span><span>Receipts & sales in seconds</span></div>
                <div class="check-item"><span class="check-icon">✓</span><span>Stock levels stay accurate</span></div>
            </div>
        </div>
        <div class="device-card">
            <img src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1400&q=80" alt="POS device">
        </div>
    </section>

    <footer>
        Need help getting started? Email support@tiwipos.test or sign in to continue to your dashboard.
    </footer>
</body>
</html>
