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

        .sections {
            max-width: 1180px;
            margin: 0 auto;
            display: grid;
            gap: 18px;
            padding-top: 8px;
        }

        .section-block {
            scroll-margin-top: 110px;
            background: rgba(255,255,255,0.82);
            border: 1px solid rgba(15, 45, 25, 0.08);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 18px 45px rgba(15, 45, 25, 0.08);
            backdrop-filter: blur(8px);
        }

        .section-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(12, 141, 61, 0.1);
            color: var(--accent);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .section-head {
            display: grid;
            gap: 8px;
            margin-bottom: 18px;
        }

        .section-head h2 {
            margin: 0;
            font-family: 'Playfair Display', serif;
            font-size: clamp(28px, 4vw, 40px);
            line-height: 1.08;
        }

        .section-head p {
            margin: 0;
            color: var(--muted);
            max-width: 760px;
            font-size: 16px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }

        .info-card {
            background: #fff;
            border-radius: 18px;
            padding: 18px;
            border: 1px solid rgba(15, 45, 25, 0.08);
            box-shadow: 0 12px 26px rgba(15, 45, 25, 0.06);
        }

        .info-card h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        .info-card p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .pricing-row {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
            gap: 16px;
        }

        .pricing-panel {
            background: linear-gradient(135deg, #123524 0%, #0c8d3d 100%);
            color: #fff;
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 20px 48px rgba(12, 141, 61, 0.2);
        }

        .pricing-panel strong {
            display: block;
            font-size: 42px;
            line-height: 1;
            margin: 12px 0 10px;
        }

        .pricing-panel p,
        .contact-card p {
            margin: 0;
            line-height: 1.6;
        }

        .pricing-list {
            margin: 16px 0 0;
            padding-left: 18px;
            display: grid;
            gap: 8px;
        }

        .contact-card {
            background: #fff;
            border-radius: 22px;
            padding: 24px;
            border: 1px solid rgba(15, 45, 25, 0.08);
        }

        footer {
            margin: 34px auto 0;
            max-width: 1180px;
            padding: 0 0 20px;
            color: var(--muted);
            font-size: 14px;
            text-align: center;
        }

        @media (max-width: 1024px) {
            .hero { padding: 48px 0 20px; }
            .hero-visual { min-height: 400px; }
            .pricing-row { grid-template-columns: 1fr; }
        }

        @media (max-width: 720px) {
            .hero { gap: 32px; }
            .phone.main { width: 78%; left: 10%; }
            .phone.top, .phone.bottom { display: none; }
        }
    </style>
</head>
<body>
    @include('partials.public_navigation', ['activeSection' => 'home', 'useLocalAnchors' => true])

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

        <div class="sections">
            <section id="products" class="section-block">
                <div class="section-head">
                    <span class="section-kicker">Products</span>
                    <h2>Retail tools built to keep sales, stock, and finance aligned.</h2>
                    <p>Tiwi POS combines checkout, stock control, branch oversight, and reporting in one operating layer for growing businesses.</p>
                </div>
                <div class="info-grid">
                    <article class="info-card">
                        <h3>Point of sale</h3>
                        <p>Handle fast checkout flows, customer details, and receipt-ready sales from a clean interface.</p>
                    </article>
                    <article class="info-card">
                        <h3>Inventory control</h3>
                        <p>Track stock levels, alerts, adjustments, and branch movement without running a separate stock tool.</p>
                    </article>
                    <article class="info-card">
                        <h3>Team access</h3>
                        <p>Assign controlled access to owners, managers, and staff while keeping sensitive admin tools restricted.</p>
                    </article>
                </div>
            </section>

            <section id="pricing" class="section-block">
                <div class="section-head">
                    <span class="section-kicker">Pricing</span>
                    <h2>Simple pricing for stores that want clarity, not negotiation.</h2>
                    <p>Run your branches on a predictable plan with billing visibility and subscription controls built into the platform.</p>
                </div>
                <div class="pricing-row">
                    <div class="pricing-panel">
                        <span>Standard plan</span>
                        <strong>Flexible</strong>
                        <p>One subscription for POS, stock, branch management, user access, and reporting.</p>
                        <ul class="pricing-list">
                            <li>Branch-aware operations</li>
                            <li>Role-based user management</li>
                            <li>Billing and activation controls</li>
                        </ul>
                    </div>
                    <div class="contact-card">
                        <h3 style="margin:0 0 10px;">Need a quote?</h3>
                        <p>Talk to the Tiwi team for onboarding support, rollout planning, or enterprise packaging for multi-location operations.</p>
                    </div>
                </div>
            </section>

            <section id="resources" class="section-block">
                <div class="section-head">
                    <span class="section-kicker">Resources</span>
                    <h2>Use the blog and guides to improve operations faster.</h2>
                    <p>Browse practical content on POS workflows, inventory discipline, reporting, and business growth across Kenyan retail environments.</p>
                </div>
                <div class="info-grid">
                    <article class="info-card">
                        <h3>Blog articles</h3>
                        <p>Read current posts on POS systems, business systems, and operational best practices.</p>
                    </article>
                    <article class="info-card">
                        <h3>Setup guidance</h3>
                        <p>Get your first branch, products, and staff accounts configured with less friction.</p>
                    </article>
                    <article class="info-card">
                        <h3>Support access</h3>
                        <p>Reach the team directly when you need rollout help or troubleshooting assistance.</p>
                    </article>
                </div>
            </section>

            <section id="company" class="section-block">
                <div class="section-head">
                    <span class="section-kicker">Company</span>
                    <h2>Tiwi is focused on practical systems for real operators.</h2>
                    <p>The platform is designed for businesses that need clearer control over sales, staff, inventory, and branch execution.</p>
                </div>
                <div class="info-grid">
                    <article class="info-card">
                        <h3>Retail-first</h3>
                        <p>Every workflow is shaped around the pace and accountability demands of day-to-day retail operations.</p>
                    </article>
                    <article class="info-card">
                        <h3>Built for growth</h3>
                        <p>Move from a single shop to multiple branches without replacing your operating stack.</p>
                    </article>
                    <article class="info-card">
                        <h3>Operational visibility</h3>
                        <p>Keep decision-makers close to the numbers that matter without burying them in spreadsheets.</p>
                    </article>
                </div>
            </section>

            <section id="app-center" class="section-block">
                <div class="section-head">
                    <span class="section-kicker">App Center</span>
                    <h2>One control point for selling, stocking, and administration.</h2>
                    <p>The app center experience brings together the daily actions your team needs instead of scattering them across disconnected tools.</p>
                </div>
                <div class="info-grid">
                    <article class="info-card">
                        <h3>Sales console</h3>
                        <p>Open POS fast, complete transactions, and issue receipts from the same workspace.</p>
                    </article>
                    <article class="info-card">
                        <h3>Stock workspace</h3>
                        <p>Adjust quantities, watch thresholds, and maintain branch-level accuracy from one place.</p>
                    </article>
                    <article class="info-card">
                        <h3>Admin controls</h3>
                        <p>Manage billing, staff, branches, and business settings with clear separation of privileges.</p>
                    </article>
                </div>
            </section>

            <section id="enterprise" class="section-block">
                <div class="section-head">
                    <span class="section-kicker">Enterprise</span>
                    <h2>Support multi-branch operations with tighter control.</h2>
                    <p>Enterprise teams can coordinate branches, manage wider teams, and keep a clearer view of activity across the business.</p>
                </div>
                <div class="pricing-row">
                    <div class="contact-card">
                        <h3 style="margin:0 0 10px;">Enterprise rollout</h3>
                        <p>Use Tiwi to standardize branch processes, centralize oversight, and reduce visibility gaps between HQ and field teams.</p>
                    </div>
                    <div class="contact-card">
                        <h3 style="margin:0 0 10px;">Contact</h3>
                        <p>Email <a href="mailto:support@tiwi.co.ke" style="font-weight:700; color:var(--accent);">support@tiwi.co.ke</a> to discuss onboarding, support, or enterprise requirements.</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer>
        Need help getting started? Email support@tiwipos.test or sign in to continue to your dashboard.
    </footer>
</body>
</html>
