<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiwi POS | Create Account</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #eef4ff;
            --card: #ffffff;
            --accent: #2563eb;
            --accent-2: #06b6d4;
            --muted: #5b6475;
            --border: #e3e8f4;
            --success: #16a34a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: radial-gradient(circle at 15% 20%, rgba(37,99,235,0.08), transparent 35%),
                        radial-gradient(circle at 80% 0%, rgba(6,182,212,0.10), transparent 35%),
                        var(--bg);
            color: #0f172a;
            min-height: 100vh;
            padding: 32px 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .shell {
            width: min(1100px, 100%);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            background: #f8fbff;
            border: 1px solid #e6edf7;
            box-shadow: 0 24px 80px rgba(15,23,42,0.10);
            border-radius: 22px;
            padding: 20px;
        }
        .panel {
            background: var(--card);
            border-radius: 18px;
            padding: 26px;
            border: 1px solid var(--border);
            box-shadow: 0 12px 35px rgba(15,23,42,0.05);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 19px;
            margin-bottom: 10px;
        }
        .logo-dot {
            width: 42px; height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg,#f97316,#f43f5e);
            display: grid; place-items: center;
            color: #fff; font-weight: 800;
            font-size: 18px;
        }
        h1 { margin: 6px 0 6px; font-size: 30px; }
        p.lead { margin: 0 0 18px; color: var(--muted); font-size: 15.5px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        .field { margin-bottom: 14px; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid #dbe3f0;
            background: #f9fbff;
            font-size: 15px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        input:focus {
            outline: none;
            border-color: rgba(37,99,235,0.5);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
        }
        .btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 14px 38px rgba(37,99,235,0.25);
            transition: transform 0.1s ease, box-shadow 0.2s ease;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 16px 42px rgba(37,99,235,0.28); }
        .note {
            font-size: 14px;
            color: var(--muted);
            background: #f4f6fb;
            padding: 11px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            margin-top: 14px;
        }
        .link { color: var(--accent); text-decoration: none; font-weight: 700; }
        .pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 7px 12px;
            background: #ecf0ff;
            border-radius: 999px;
            color: #1d4ed8;
            font-weight: 700; font-size: 13px;
        }
        .list { list-style: none; padding: 0; margin: 14px 0 0; display: grid; gap: 10px; }
        .list li { display: flex; gap: 10px; align-items: flex-start; color: #0f172a; }
        .check {
            width: 22px; height: 22px; border-radius: 8px;
            background: #e0f6ea; color: #166534;
            display: grid; place-items: center; font-weight: 800;
        }
        .stats {
            display: grid; grid-template-columns: repeat(auto-fit,minmax(160px,1fr)); gap: 10px; margin-top: 18px;
        }
        .stat-card {
            background: #0f172a;
            color: #fff;
            padding: 12px 14px;
            border-radius: 12px;
        }
        .stat-card small { display:block; color: #cbd5e1; margin-bottom: 6px; }
        .errors {
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #b91c1c;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 12px;
        }
        @media (max-width: 760px) {
            body { padding: 14px; }
            .shell { padding: 14px; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="panel">
            <div class="brand">
                <div class="logo-dot">TP</div>
                <span>Tiwi POS</span>
            </div>
            <h1>Create an account</h1>
            <p class="lead">Create your Tiwi POS workspace, business, and first branch.</p>

            @if ($errors->any())
                <div class="errors">
                    <strong>Please fix the following:</strong>
                    <ul style="margin:8px 0 0 16px; padding:0; color:#b91c1c;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}">
                @csrf
                <div class="field">
                    <label for="business_name">Business name</label>
                    <input id="business_name" name="business_name" type="text" value="{{ old('business_name') }}" required>
                    @error('business_name') <small style="color:#d14343;">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="branch_name">Branch name (optional)</label>
                    <input id="branch_name" name="branch_name" type="text" value="{{ old('branch_name') }}" placeholder="Main Branch">
                    @error('branch_name') <small style="color:#d14343;">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="industry">Industry (optional)</label>
                    <input id="industry" name="industry" type="text" value="{{ old('industry') }}" placeholder="Retail, electronics, etc.">
                </div>
                <div class="field">
                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    @error('name') <small style="color:#d14343;">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="email">Work email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    @error('email') <small style="color:#d14343;">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                    @error('password') <small style="color:#d14343;">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required>
                </div>
                <button class="btn" type="submit">Create account</button>
            </form>

            <div class="note">
                Already registered? <a class="link" href="{{ route('login') }}">Sign in</a>.
            </div>
        </div>

        <div class="panel" style="background: linear-gradient(135deg,#0f172a,#111827); color:#f8fafc; border: none;">
            <span class="pill">Why teams choose Tiwi POS</span>
            <h2 style="margin:12px 0 8px; font-size:24px; color:#fff;">Fast setup, confident control</h2>
            <p style="color:#cbd5e1; margin:0 0 14px;">Start selling in minutes, track every branch, and stay audit-ready with clean records.</p>
            <ul class="list">
                <li><span class="check">✓</span><span>Branch-aware inventory with live stock visibility.</span></li>
                <li><span class="check">✓</span><span>Realtime sales KPIs and profit tracking.</span></li>
                <li><span class="check">✓</span><span>Unlimited staff logins under your business.</span></li>
                <li><span class="check">✓</span><span>Exports for finance and audit in two clicks.</span></li>
            </ul>
            <div class="stats">
                <div class="stat-card">
                    <small>Avg. setup time</small>
                    <strong style="font-size:22px;">12 mins</strong>
                </div>
                <div class="stat-card" style="background:#1d4ed8;">
                    <small>Branches supported</small>
                    <strong style="font-size:22px;">Multi-site</strong>
                </div>
                <div class="stat-card" style="background:#0f766e;">
                    <small>Data exports</small>
                    <strong style="font-size:22px;">CSV / PDF</strong>
                </div>
            </div>
            <p style="margin-top:16px; color:#cbd5e1;">Need onboarding help? Email <a href="mailto:support@tiwi.co.ke" style="color:#fff; font-weight:700; text-decoration:none;">support@tiwi.co.ke</a>.</p>
        </div>
    </div>
</body>
</html>
