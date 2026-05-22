<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiwi POS | Business Software for Growing Teams</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --blue: #0a64bc;
            --blue-dark: #06407a;
            --orange: #F05A22;
            --orange-dark: #d44d1a;
            --navy: #1a2d3d;
            --bg: #f4f9ff;
            --muted: #5a7184;
            --border: rgba(10, 100, 188, 0.12);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #fff;
            color: var(--navy);
            min-height: 100vh;
            line-height: 1.6;
        }

        a { color: inherit; text-decoration: none; }

        /* ── Hero ── */
        .hero-section {
            background: linear-gradient(160deg, #062e5f 0%, #0a64bc 65%, #1a8ad4 100%);
            padding: 80px 24px 100px;
            color: #fff;
        }

        .hero-inner {
            max-width: 1180px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
            align-items: center;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.04em;
            margin-bottom: 20px;
        }

        .hero-title {
            font-size: clamp(32px, 4.5vw, 52px);
            font-weight: 800;
            line-height: 1.1;
            margin: 0 0 16px;
        }

        .hero-desc {
            font-size: 18px;
            color: rgba(255,255,255,0.82);
            margin: 0 0 32px;
            max-width: 480px;
        }

        .hero-cta { display: flex; gap: 14px; flex-wrap: wrap; }

        .btn-orange {
            display: inline-flex;
            align-items: center;
            padding: 14px 28px;
            background: var(--orange);
            color: #fff;
            border-radius: 6px;
            font-weight: 700;
            font-size: 15px;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 28px rgba(240, 90, 34, 0.38);
            transition: background 0.2s, transform 0.15s;
        }

        .btn-orange:hover { background: var(--orange-dark); transform: translateY(-1px); }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            padding: 14px 28px;
            background: rgba(255,255,255,0.12);
            color: #fff;
            border-radius: 6px;
            font-weight: 700;
            font-size: 15px;
            border: 1.5px solid rgba(255,255,255,0.38);
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }

        .btn-ghost:hover { background: rgba(255,255,255,0.22); transform: translateY(-1px); }

        .hero-stats {
            display: flex;
            gap: 32px;
            margin-top: 40px;
            padding-top: 32px;
            border-top: 1px solid rgba(255,255,255,0.15);
            flex-wrap: wrap;
        }

        .stat-item strong { display: block; font-size: 26px; font-weight: 800; }
        .stat-item span   { font-size: 13px; color: rgba(255,255,255,0.68); font-weight: 500; }

        /* Dashboard illustration */
        .hero-visual { display: flex; align-items: center; justify-content: center; }

        .dash-card {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 32px 80px rgba(0,0,0,0.28);
            width: 100%;
            max-width: 420px;
            color: var(--navy);
        }

        .dash-card__hdr {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
        }

        .dash-card__dot { width: 11px; height: 11px; border-radius: 50%; }

        .dash-card__title { font-size: 13px; font-weight: 700; margin-left: 4px; }

        .dash-bar-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }

        .dash-bar-label { font-size: 12px; color: var(--muted); width: 36px; text-align: right; }

        .dash-bar-track { flex: 1; height: 8px; background: #eef2f7; border-radius: 99px; overflow: hidden; }

        .dash-bar-fill { height: 100%; border-radius: 99px; }

        .dash-bar-val { font-size: 12px; font-weight: 700; width: 44px; }

        .dash-kpis {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #eef2f7;
            text-align: center;
        }

        .dash-kpi strong { display: block; font-size: 16px; font-weight: 800; color: var(--blue); }
        .dash-kpi span   { font-size: 11px; color: var(--muted); }

        /* ── Shared section styles ── */
        .section {
            max-width: 1180px;
            margin: 0 auto;
            padding: 80px 24px;
        }

        .section--alt { background: var(--bg); padding: 80px 24px; }
        .section--alt .section-inner { max-width: 1180px; margin: 0 auto; }

        .kicker {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            color: var(--orange);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .sec-title {
            font-size: clamp(26px, 3.5vw, 38px);
            font-weight: 800;
            line-height: 1.15;
            margin: 0 0 12px;
        }

        .sec-desc { color: var(--muted); font-size: 17px; margin: 0 0 44px; max-width: 680px; }

        /* Feature grid */
        .feat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .feat-card {
            background: #fff;
            border: 1px solid rgba(10, 100, 188, 0.10);
            border-radius: 12px;
            padding: 28px;
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .feat-card:hover { box-shadow: 0 12px 40px rgba(10, 100, 188, 0.12); transform: translateY(-2px); }

        .feat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: rgba(10, 100, 188, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 16px;
        }

        .feat-card h3 { margin: 0 0 8px; font-size: 17px; font-weight: 700; }
        .feat-card p  { margin: 0; color: var(--muted); font-size: 14px; line-height: 1.7; }

        /* ── Pricing ── */
        .pricing-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        .plan-card {
            background: #fff;
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 32px;
        }

        .plan-card--featured {
            background: linear-gradient(145deg, #062e5f 0%, #0a64bc 100%);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 24px 64px rgba(10, 100, 188, 0.22);
        }

        .plan-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--orange);
            margin-bottom: 10px;
        }

        .plan-card--featured .plan-label { color: rgba(255,255,255,0.65); }

        .plan-price { font-size: 40px; font-weight: 800; line-height: 1; margin: 10px 0 10px; }
        .plan-price small { font-size: 15px; font-weight: 500; opacity: 0.55; }

        .plan-desc { font-size: 15px; color: var(--muted); margin: 0 0 20px; line-height: 1.6; }
        .plan-card--featured .plan-desc { color: rgba(255,255,255,0.72); }

        .plan-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }

        .plan-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--muted);
        }

        .plan-card--featured .plan-list li { color: rgba(255,255,255,0.80); }

        .plan-list li::before {
            content: '✓';
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(10,100,188,0.10);
            color: var(--blue);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .plan-card--featured .plan-list li::before { background: rgba(255,255,255,0.18); color: #fff; }

        .plan-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-top: 24px;
            padding: 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            background: var(--orange);
            color: #fff;
            transition: background 0.2s;
        }

        .plan-cta:hover { background: var(--orange-dark); }

        .plan-cta--outline {
            background: #fff;
            color: var(--navy);
            border: 1.5px solid var(--border);
        }

        .plan-cta--outline:hover { background: var(--bg); }

        /* ── CTA banner ── */
        .cta-banner {
            background: linear-gradient(135deg, var(--orange) 0%, #d44014 100%);
            padding: 72px 24px;
            text-align: center;
        }

        .cta-banner__inner { max-width: 660px; margin: 0 auto; }

        .cta-banner h2 { font-size: clamp(26px, 4vw, 38px); font-weight: 800; color: #fff; margin: 0 0 14px; }
        .cta-banner p  { color: rgba(255,255,255,0.84); font-size: 17px; margin: 0 0 28px; }

        .btn-white {
            display: inline-flex;
            align-items: center;
            padding: 14px 34px;
            background: #fff;
            color: var(--orange);
            border-radius: 6px;
            font-weight: 800;
            font-size: 15px;
            box-shadow: 0 10px 32px rgba(0,0,0,0.16);
            transition: transform 0.15s, box-shadow 0.2s;
        }

        .btn-white:hover { transform: translateY(-2px); box-shadow: 0 18px 44px rgba(0,0,0,0.22); }

        /* ── Footer ── */
        footer {
            background: #0a2540;
            color: rgba(255,255,255,0.52);
            padding: 28px 24px;
            text-align: center;
            font-size: 14px;
        }

        footer a { color: rgba(255,255,255,0.72); }

        hr.divider { border: none; border-top: 1px solid rgba(10,100,188,0.08); margin: 0; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .hero-inner      { grid-template-columns: 1fr; gap: 40px; }
            .hero-visual     { display: none; }
            .pricing-grid    { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @include('partials.public_navigation', ['activeSection' => 'home', 'useLocalAnchors' => true])

    <!-- Hero -->
    <section class="hero-section">
        <div class="hero-inner">
            <div>
                <div class="hero-eyebrow">✦ Tiwi POS — Retail &amp; Finance Platform</div>
                <h1 class="hero-title">The operating system for your retail business</h1>
                <p class="hero-desc">Run sales, inventory, staff, and branches from one unified platform. No spreadsheets. No hidden fees. No long onboarding calls.</p>
                <div class="hero-cta">
                    <a class="btn-orange" href="{{ route('register') }}">Get started free</a>
                    <a class="btn-ghost"   href="{{ route('login') }}">Sign in</a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item"><strong>1 Platform</strong><span>All your tools</span></div>
                    <div class="stat-item"><strong>Real-time</strong><span>Stock &amp; sales</span></div>
                    <div class="stat-item"><strong>Multi-branch</strong><span>Ready to scale</span></div>
                </div>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <div class="dash-card">
                    <div class="dash-card__hdr">
                        <div class="dash-card__dot" style="background:#0a64bc"></div>
                        <div class="dash-card__dot" style="background:#F05A22"></div>
                        <div class="dash-card__dot" style="background:#34d399"></div>
                        <span class="dash-card__title">Sales Overview</span>
                    </div>
                    @foreach([['Jan','#0a64bc',72],['Feb','#F05A22',55],['Mar','#34d399',88],['Apr','#0a64bc',64],['May','#F05A22',91]] as [$m,$c,$p])
                    <div class="dash-bar-row">
                        <span class="dash-bar-label">{{ $m }}</span>
                        <div class="dash-bar-track">
                            <div class="dash-bar-fill" style="width:{{ $p }}%;background:{{ $c }}"></div>
                        </div>
                        <span class="dash-bar-val" style="color:{{ $c }}">{{ $p }}%</span>
                    </div>
                    @endforeach
                    <div class="dash-kpis">
                        <div class="dash-kpi"><strong>KES 0</strong><span>Today</span></div>
                        <div class="dash-kpi"><strong>0</strong><span>In stock</span></div>
                        <div class="dash-kpi"><strong>Active</strong><span>Status</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <main>
        <!-- Products -->
        <div class="section" id="products">
            <div class="kicker">Products</div>
            <h2 class="sec-title">Everything your retail team needs, in one place</h2>
            <p class="sec-desc">Tiwi POS brings together checkout, inventory, team management, and reporting so your business runs without gaps.</p>
            <div class="feat-grid">
                <div class="feat-card">
                    <div class="feat-icon">🛒</div>
                    <h3>Point of Sale</h3>
                    <p>Fast, clean checkout with customer capture, multiple payment methods, and instant receipts.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon">📦</div>
                    <h3>Inventory Control</h3>
                    <p>Track stock levels, set reorder alerts, and manage adjustments across all branches in real time.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon">👥</div>
                    <h3>Team &amp; Role Management</h3>
                    <p>Assign granular permissions to owners, managers, and staff with clear privilege boundaries.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon">📊</div>
                    <h3>Reports &amp; Analytics</h3>
                    <p>Daily sales, monthly performance, tax summaries, and profit tracking in one dashboard.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon">🏪</div>
                    <h3>Branch Management</h3>
                    <p>Switch locations, compare performance, and manage operations across every branch from HQ.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon">🔧</div>
                    <h3>Services Module</h3>
                    <p>Manage bookings, track visits, assign workers, and handle service billing alongside retail.</p>
                </div>
            </div>
        </div>

        <hr class="divider">

        <!-- App Center -->
        <div class="section--alt" id="app-center">
            <div class="section-inner">
                <div class="kicker">App Center</div>
                <h2 class="sec-title">One workspace. Every daily action.</h2>
                <p class="sec-desc">Your team shouldn't juggle five tools. The app center puts sales, stock, and admin in a single coherent interface.</p>
                <div class="feat-grid">
                    <div class="feat-card">
                        <div class="feat-icon">💻</div>
                        <h3>Sales Console</h3>
                        <p>Open POS fast, complete transactions, and share receipts from the same workspace.</p>
                    </div>
                    <div class="feat-card">
                        <div class="feat-icon">🗃️</div>
                        <h3>Stock Workspace</h3>
                        <p>Adjust quantities, watch thresholds, and keep branch-level accuracy without a separate tool.</p>
                    </div>
                    <div class="feat-card">
                        <div class="feat-icon">⚙️</div>
                        <h3>Admin Controls</h3>
                        <p>Manage billing, staff, branches, and settings with clear separation of access privileges.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="section" id="pricing">
            <div class="kicker">Pricing</div>
            <h2 class="sec-title">Transparent pricing. No surprise invoices.</h2>
            <p class="sec-desc">One plan covers POS, inventory, staff, branch oversight, and reporting. Billing is built into the platform.</p>
            <div class="pricing-grid">
                <div class="plan-card plan-card--featured">
                    <div class="plan-label">Standard Plan</div>
                    <div class="plan-price">Flexible <small>/ mo</small></div>
                    <p class="plan-desc">Everything your business needs to sell, manage stock, and run your team — on one subscription.</p>
                    <ul class="plan-list">
                        <li>Unlimited transactions</li>
                        <li>Branch-aware operations</li>
                        <li>Role-based user access</li>
                        <li>Inventory &amp; stock alerts</li>
                        <li>Sales reports &amp; analytics</li>
                        <li>Services &amp; booking module</li>
                    </ul>
                    <a href="{{ route('register') }}" class="plan-cta">Start free today</a>
                </div>
                <div class="plan-card">
                    <div class="plan-label">Enterprise</div>
                    <div class="plan-price" style="font-size:26px;color:var(--navy)">Custom quote</div>
                    <p class="plan-desc">Multi-branch rollouts, dedicated onboarding, and enterprise packaging for large operations.</p>
                    <ul class="plan-list">
                        <li>Dedicated account support</li>
                        <li>Custom onboarding plan</li>
                        <li>Multi-location coordination</li>
                        <li>Priority response</li>
                    </ul>
                    <a href="mailto:support@tiwi.co.ke" class="plan-cta plan-cta--outline">Contact sales</a>
                </div>
            </div>
        </div>

        <hr class="divider">

        <!-- Resources -->
        <div class="section--alt" id="resources">
            <div class="section-inner">
                <div class="kicker">Resources</div>
                <h2 class="sec-title">Guides, blog, and support to help you grow faster.</h2>
                <p class="sec-desc">Practical content on POS workflows, inventory discipline, and growth for Kenyan retail operators.</p>
                <div class="feat-grid">
                    <div class="feat-card">
                        <div class="feat-icon">📝</div>
                        <h3>Blog Articles</h3>
                        <p>Current posts on POS systems, business operations, and best practices for retail management.</p>
                    </div>
                    <div class="feat-card">
                        <div class="feat-icon">🚀</div>
                        <h3>Setup Guidance</h3>
                        <p>Get your first branch, products, and staff configured with step-by-step walkthroughs.</p>
                    </div>
                    <div class="feat-card">
                        <div class="feat-icon">💬</div>
                        <h3>Direct Support</h3>
                        <p>Reach the Tiwi team for rollout help, troubleshooting, or onboarding assistance.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Company -->
        <div class="section" id="company">
            <div class="kicker">Company</div>
            <h2 class="sec-title">Built for operators who need real control.</h2>
            <p class="sec-desc">Tiwi is designed for businesses that need clarity over sales, staff, inventory, and multi-branch execution — without enterprise-grade complexity.</p>
            <div class="feat-grid">
                <div class="feat-card">
                    <div class="feat-icon">🏆</div>
                    <h3>Retail-First Design</h3>
                    <p>Every workflow is shaped around the pace and accountability of day-to-day retail.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon">📈</div>
                    <h3>Built for Growth</h3>
                    <p>Scale from one shop to multiple branches without replacing your operating infrastructure.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon">🔍</div>
                    <h3>Operational Visibility</h3>
                    <p>Keep decision-makers close to the numbers that matter, without burying them in spreadsheets.</p>
                </div>
            </div>
        </div>

        <!-- Enterprise -->
        <div class="section--alt" id="enterprise">
            <div class="section-inner">
                <div class="kicker">Enterprise</div>
                <h2 class="sec-title">Scale multi-branch operations with confidence.</h2>
                <p class="sec-desc">Enterprise teams use Tiwi to standardize processes, centralize oversight, and reduce visibility gaps between HQ and field teams.</p>
                <div class="feat-grid">
                    <div class="feat-card">
                        <div class="feat-icon">🏗️</div>
                        <h3>Centralized Oversight</h3>
                        <p>Monitor performance, manage staff, and control inventory across every branch from one login.</p>
                    </div>
                    <div class="feat-card">
                        <div class="feat-icon">📋</div>
                        <h3>Standardized Processes</h3>
                        <p>Roll out consistent POS workflows, pricing rules, and access controls across all locations.</p>
                    </div>
                    <div class="feat-card">
                        <div class="feat-icon">📧</div>
                        <h3>Talk to Us</h3>
                        <p>Email <a href="mailto:support@tiwi.co.ke" style="color:var(--blue);font-weight:700;">support@tiwi.co.ke</a> to discuss onboarding, support, or enterprise requirements.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Banner -->
        <div class="cta-banner">
            <div class="cta-banner__inner">
                <h2>Ready to take control of your business?</h2>
                <p>Join businesses already using Tiwi POS to run smarter, faster, and with greater visibility.</p>
                <a class="btn-white" href="{{ route('register') }}">Start for free today</a>
            </div>
        </div>
    </main>

    <footer>
        &copy; {{ date('Y') }} Tiwi POS. All rights reserved. &nbsp;&middot;&nbsp;
        Need help? <a href="mailto:support@tiwi.co.ke">support@tiwi.co.ke</a>
    </footer>
</body>
</html>
