<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiwi POS | Take control of your finances</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --nav-bg: #fdfaf4;
            --hero-bg: #e6ecef;
            --accent: #0c8d3d;
            --accent-dark: #0a7031;
            --text: #1f2a37;
            --muted: #4b5563;
            --card: #ffffff;
            --shadow: 0 25px 70px rgba(15, 45, 25, 0.12);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
            background: var(--hero-bg);
            color: var(--text);
            min-height: 100vh;
        }

        a { color: inherit; text-decoration: none; }

        .nav-bar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: var(--nav-bg);
            border-bottom: 1px solid rgba(15, 45, 25, 0.07);
            box-shadow: 0 8px 30px rgba(0,0,0,0.04);
        }

        .nav-inner {
            margin: 0 auto;
            max-width: 1180px;
            padding: 16px 24px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 24px;
            align-items: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-circle {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(135deg, #0c8d3d, #0f9d49);
            color: #fff;
            font-weight: 800;
            display: grid;
            place-items: center;
            letter-spacing: 0.5px;
        }

        .brand-name { font-weight: 800; font-size: 18px; letter-spacing: 0.01em; }
        .brand-sub { font-size: 12px; color: var(--muted); }

        nav { display: flex; gap: 18px; justify-content: center; align-items: center; flex-wrap: wrap; }

        .nav-link {
            font-weight: 600;
            padding: 10px 6px;
            position: relative;
            transition: color 0.2s ease;
            color: #2f3a47;
        }

        .nav-link.active::after,
        .nav-link:hover::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -4px;
            height: 3px;
            background: var(--accent);
            border-radius: 999px;
        }

        .actions { display: flex; gap: 12px; align-items: center; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease;
        }

        .btn:hover { transform: translateY(-1px); box-shadow: 0 14px 40px rgba(0,0,0,0.08); }

        .btn.primary { background: var(--accent); color: #fff; box-shadow: 0 10px 32px rgba(12, 141, 61, 0.25); }
        .btn.primary:active { background: var(--accent-dark); }

        .btn.outline { background: #fff; color: var(--text); border-color: rgba(15, 45, 25, 0.12); }

        .btn.ghost { background: var(--nav-bg); color: var(--text); border-color: rgba(15, 45, 25, 0.12); }

        main { padding: 0 24px 64px; }

        .hero {
            max-width: 1180px;
            margin: 0 auto;
            padding: 64px 0 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 48px;
            align-items: center;
        }

        .hero-copy { position: relative; z-index: 2; }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(12, 141, 61, 0.1);
            color: var(--accent);
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        h1 {
            margin: 20px 0 14px;
            font-family: 'Playfair Display', serif;
            font-size: clamp(36px, 5vw, 54px);
            line-height: 1.05;
            color: #17212b;
        }

        .lead {
            margin: 0 0 26px;
            color: var(--muted);
            font-size: 18px;
            max-width: 520px;
        }

        .cta { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }

        .store-badges { display: flex; gap: 14px; flex-wrap: wrap; }
        .store-badges img { height: 50px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }

        .hero-visual {
            position: relative;
            min-height: 460px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gradient-blob {
            position: absolute;
            inset: 0;
            border-radius: 32px;
            background: radial-gradient(circle at 30% 30%, rgba(12,141,61,0.10), transparent 45%),
                        radial-gradient(circle at 80% 20%, rgba(12,141,61,0.08), transparent 40%),
                        linear-gradient(135deg, #f7fafb 0%, #e4eaee 100%);
            filter: drop-shadow(0 24px 80px rgba(0,0,0,0.08));
        }

        .phone {
            position: absolute;
            width: min(68%, 380px);
            max-width: 420px;
            aspect-ratio: 9 / 18;
            background: var(--card);
            border-radius: 28px;
            border: 1px solid rgba(15,45,25,0.08);
            overflow: hidden;
            box-shadow: var(--shadow);
            transform: rotate(-6deg);
        }

        .phone img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .phone.main { transform: rotate(-8deg) translateX(-10%); z-index: 3; }
        .phone.top { width: min(44%, 240px); top: 8%; right: 4%; transform: rotate(6deg); z-index: 4; }
        .phone.bottom { width: min(48%, 260px); bottom: -4%; right: 18%; transform: rotate(10deg); z-index: 2; }

        footer {
            margin: 34px auto 0;
            max-width: 1180px;
            padding: 0 0 20px;
            color: var(--muted);
            font-size: 14px;
            text-align: center;
        }

        @media (max-width: 1024px) {
            .nav-inner { grid-template-columns: 1fr; justify-items: center; }
            .brand { justify-content: center; }
            .actions { justify-content: center; }
            nav { width: 100%; justify-content: center; }
            .hero { padding: 48px 0 20px; }
            .hero-visual { min-height: 400px; }
        }

        @media (max-width: 720px) {
            .nav-inner { padding: 12px 16px; }
            .hero { gap: 32px; }
            .phone.main { width: 78%; left: 10%; }
            .phone.top, .phone.bottom { display: none; }
        }
    </style>
</head>
<body>
    <header class="nav-bar">
        <div class="nav-inner">
            <div class="brand">
                <div class="logo-circle">TP</div>
                <div>
                    <div class="brand-name">Tiwi POS</div>
                    <div class="brand-sub">Retail · Finance</div>
                </div>
            </div>
            <nav>
                <a class="nav-link active" href="{{ route('home') }}">Home</a>
                <a class="nav-link" href="#about">About</a>
                <a class="nav-link" href="#features">Features</a>
                <a class="nav-link" href="#accounting">Accounting</a>
                <a class="nav-link" href="{{ auth()->check() ? route('content.index') : '#blog' }}">Blog</a>
                <a class="nav-link" href="#contact">Contact</a>
            </nav>
            <div class="actions">
                @guest
                    <a class="nav-link" href="{{ route('login') }}">Sign In</a>
                    <a class="btn primary" href="{{ route('register') }}">Sign Up</a>
                @else
                    <a class="btn ghost" href="{{ route('sale') }}">Open POS</a>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn outline">Log out</button>
                    </form>
                @endguest
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="hero-copy">
                <span class="eyebrow">Smart retail · Instant control</span>
                <h1>Take control of your finances with Tiwi POS.</h1>
                <p class="lead">Track every sale, reconcile stock, and see your cash flow in one serene dashboard. Try it yourself—no long onboarding calls or hidden fees.</p>
                <div class="cta">
                    <a class="btn primary" href="{{ route('register') }}">Start free</a>
                    <a class="btn outline" href="{{ route('login') }}">Sign in</a>
                </div>
                <div class="store-badges">
                    <a href="#"><img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Get it on Google Play"></a>
                    <a href="#"><img src="https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg" alt="Download on the App Store"></a>
                </div>
            </div>
            <div class="hero-visual" aria-hidden="true">
                <div class="gradient-blob"></div>
                <div class="phone main">
                    <img src="https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=900&q=80" alt="Finance app screen 1">
                </div>
                <div class="phone top">
                    <img src="https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=800&q=70&sat=-10" alt="Finance app screen 2">
                </div>
                <div class="phone bottom">
                    <img src="https://images.unsplash.com/photo-1556740768-90de374c12ad?auto=format&fit=crop&w=800&q=70" alt="Finance app screen 3">
                </div>
            </div>
        </section>
    </main>

    <footer>
        Need help getting started? Email support@tiwipos.test or sign in to continue to your dashboard.
    </footer>
</body>
</html>
