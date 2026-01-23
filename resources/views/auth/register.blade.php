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
            --bg: #f7fbff;
            --card: #ffffff;
            --accent: #00a5ff;
            --accent-dark: #0083cc;
            --muted: #657089;
            --border: #e5edf5;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: var(--bg);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            color: #0f172a;
            padding: 18px;
        }
        .card {
            width: min(480px, 100%);
            background: var(--card);
            border-radius: 16px;
            padding: 26px;
            border: 1px solid var(--border);
            box-shadow: 0 18px 48px rgba(0,0,0,0.06);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 18px;
            margin-bottom: 6px;
        }
        .logo-dot {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg,#ffb000,#ff4b72);
            display: grid; place-items: center;
            color: #fff; font-weight: 800;
        }
        h1 { margin: 6px 0 4px; font-size: 26px; }
        p { margin: 0 0 18px; color: var(--muted); }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        .field { margin-bottom: 16px; }
        input[type=\"text\"], input[type=\"email\"], input[type=\"password\"] {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #f9fbfd;
            font-size: 15px;
        }
        input:focus {
            outline: 2px solid rgba(0,165,255,0.35);
            border-color: rgba(0,165,255,0.45);
        }
        .btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 12px;
            background: var(--accent);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 12px 30px rgba(0,165,255,0.35);
            transition: transform 0.1s ease, box-shadow 0.2s ease;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 14px 32px rgba(0,165,255,0.4); }
        .note {
            font-size: 13px;
            color: var(--muted);
            background: #f4f7fb;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            margin-top: 14px;
        }
        .link { color: #0f172a; text-decoration: none; font-weight: 600; }
        .link:hover { color: var(--accent); }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">
            <div class="logo-dot">TP</div>
            <span>Tiwi POS</span>
        </div>
        <h1>Create an account</h1>
        <p>Set up your access to the Tiwi POS dashboard.</p>

        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <div class="field">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                @error('name') <small style=\"color:#d14343;\">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                @error('email') <small style=\"color:#d14343;\">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
                @error('password') <small style=\"color:#d14343;\">{{ $message }}</small> @enderror
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
</body>
</html>
