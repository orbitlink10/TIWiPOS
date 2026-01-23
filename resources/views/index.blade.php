<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiwi POS | Quality Retail Experience</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0c0f2f;
            --accent: #d4232b;
            --bg: #f7f9fc;
            --muted: #6b7280;
            --border: #e5e7eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            color: #0f172a;
            background: var(--bg);
        }
        .top-bar {
            background: var(--accent);
            color: #fff;
            font-weight: 800;
            letter-spacing: 0.2px;
            padding: 10px 4vw;
            text-transform: uppercase;
            font-size: 13px;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        header {
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 4vw;
            box-shadow: 0 6px 18px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .brand { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 20px; }
        .nav { display: flex; gap: 22px; align-items: center; flex-wrap: wrap; }
        .nav a { text-decoration: none; color: #0f172a; font-weight: 700; }
        .actions { display: flex; gap: 12px; align-items: center; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; border-radius: 12px; border: none; cursor: pointer; font-weight: 700; text-decoration: none; }
        .btn.primary { background: var(--accent); color: #fff; }
        .btn.ghost { background: #fff; color: #0f172a; border: 1px solid var(--border); }
        .hero {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 24px;
            padding: 28px 4vw 40px;
            align-items: stretch;
        }
        .hero-left {
            background: var(--navy);
            color: #fff;
            padding: 32px;
            border-radius: 18px;
            box-shadow: 0 24px 70px rgba(0,0,0,0.16);
        }
        .hero-left h1 { margin: 12px 0 10px; font-size: 38px; line-height: 1.1; }
        .hero-left p { margin: 0 0 18px; color: rgba(255,255,255,0.85); font-size: 16px; }
        .hero-cta { margin-top: 12px; }
        .hero-right {
            background: #fff;
            border-radius: 18px;
            border: 1px solid var(--border);
            box-shadow: 0 18px 50px rgba(0,0,0,0.08);
            display: grid;
            place-items: center;
            padding: 14px;
        }
        .hero-right img { width: 100%; border-radius: 14px; object-fit: cover; }
        .pill { display:inline-flex; padding:7px 12px; background: rgba(255,255,255,0.12); border-radius:999px; font-weight:700; }
        .usp { margin-top: 10px; font-weight:700; }
        @media (max-width: 960px) {
            .hero { grid-template-columns: 1fr; }
            header { flex-direction: column; gap: 10px; }
            .nav { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="top-bar">Where POS meets creativity • Run sales fast • Keep stock accurate</div>
    <header>
        <div class="brand">
            <div style="width:48px;height:48px;border-radius:12px;background:var(--navy);display:grid;place-items:center;color:#fff;font-weight:800;">TP</div>
            <span>Tiwi POS</span>
        </div>
        <nav class="nav">
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('products') }}">Products</a>
            <a href="{{ route('sale') }}">POS</a>
            <a href="{{ route('content.index') }}">Blog</a>
            <a href="{{ route('summary') }}">Reports</a>
        </nav>
        <div class="actions">
            <a class="btn ghost" href="{{ route('login') }}">Login</a>
            <a class="btn primary" href="{{ route('register') }}">Start free</a>
        </div>
    </header>

    <section class="hero">
        <div class="hero-left">
            <div class="pill">Quality retail toolkit</div>
            <h1>Quality Printing Services Pick A Design & Print</h1>
            <p>Manage sales, print receipts, and track stock — all in one clean POS. Reach out to us on 0745 506 619.</p>
            <div class="usp">• Fast checkout • Accurate stock • Beautiful receipts</div>
            <div class="hero-cta">
                <a class="btn primary" href="{{ route('sale') }}">Start Designing</a>
            </div>
        </div>
        <div class="hero-right">
            <img src="https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1400&q=80" alt="POS hero">
        </div>
    </section>
</body>
</html>
