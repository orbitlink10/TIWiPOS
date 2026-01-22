<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiwi POS | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --panel: #0b1221;
            --muted: #94a3b8;
            --accent: #22d3ee;
            --accent-2: #f97316;
            --border: rgba(255,255,255,0.08);
            --error: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: radial-gradient(circle at 20% 20%, rgba(34,211,238,0.18), transparent 35%), radial-gradient(circle at 80% 10%, rgba(249,115,22,0.18), transparent 40%), var(--bg);
            color: #e2e8f0;
            font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
            padding: 16px;
        }
        .card {
            width: min(420px, 100%);
            background: var(--panel);
            border-radius: 18px;
            padding: 28px;
            border: 1px solid var(--border);
            box-shadow: 0 25px 70px rgba(0,0,0,0.4);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .brand .dot {
            width: 12px; height: 12px;
            background: linear-gradient(135deg, var(--accent-2), var(--accent));
            border-radius: 50%;
        }
        h1 { margin: 6px 0 4px; font-size: 26px; }
        p { margin: 0 0 18px; color: var(--muted); }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        .field {
            margin-bottom: 16px;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 14px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.03);
            color: #e2e8f0;
            font-size: 15px;
        }
        input:focus {
            outline: 2px solid rgba(34,211,238,0.5);
            border-color: rgba(34,211,238,0.4);
        }
        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent-2), #fb923c);
            color: #0f172a;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 12px 30px rgba(249,115,22,0.35);
        }
        .helper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 12px 0 4px;
            color: var(--muted);
            font-size: 14px;
        }
        .link { color: #e2e8f0; text-decoration: none; }
        .link:hover { color: #fff; }
        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--muted);
        }
        .divider {
            margin: 18px 0;
            border-bottom: 1px dashed var(--border);
        }
        .note {
            font-size: 13px;
            color: var(--muted);
            background: rgba(255,255,255,0.04);
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">
            <div class="dot"></div>
            <span>Tiwi POS</span>
        </div>
        <h1>Welcome back</h1>
        <p>Sign in to manage sales, inventory, and reports.</p>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" placeholder="you@store.com" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="••••••••" required>
            </div>
            <div class="helper">
                <label class="remember">
                    <input type="checkbox" name="remember" style="accent-color: #f97316;">
                    Remember me
                </label>
                <a class="link" href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Sign in</button>
            </div>
        </form>

        <div class="divider"></div>
        <div class="note">
            Demo only: wire this form to your auth logic or Laravel Breeze/Fortify. Submit will currently hit the /login POST endpoint.
        </div>
    </div>
</body>
</html>
