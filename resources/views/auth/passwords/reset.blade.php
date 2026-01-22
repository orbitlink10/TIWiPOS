<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiwi POS | Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#0f172a; --panel:#0b1221; --muted:#94a3b8; --accent:#22d3ee; --accent-2:#f97316; --border:rgba(255,255,255,0.08); }
        * { box-sizing: border-box; }
        body {
            margin:0; min-height:100vh; display:grid; place-items:center;
            background: radial-gradient(circle at 20% 20%, rgba(34,211,238,0.18), transparent 35%), radial-gradient(circle at 80% 10%, rgba(249,115,22,0.18), transparent 40%), var(--bg);
            color:#e2e8f0; font-family:'Space Grotesk','Segoe UI',sans-serif; padding:16px;
        }
        .card { width:min(420px,100%); background:var(--panel); border-radius:18px; padding:28px; border:1px solid var(--border); box-shadow:0 25px 70px rgba(0,0,0,0.4); }
        .brand { display:flex; align-items:center; gap:10px; font-weight:700; letter-spacing:0.5px; margin-bottom:6px; }
        .brand .dot { width:12px; height:12px; background:linear-gradient(135deg,var(--accent-2),var(--accent)); border-radius:50%; }
        h1 { margin:8px 0 6px; font-size:24px; }
        p { margin:0 0 18px; color:var(--muted); }
        label { display:block; margin-bottom:6px; font-weight:600; }
        input[type="email"], input[type="password"] {
            width:100%; padding:14px; border-radius:12px; border:1px solid var(--border);
            background:rgba(255,255,255,0.03); color:#e2e8f0; font-size:15px;
        }
        input:focus { outline:2px solid rgba(34,211,238,0.5); border-color:rgba(34,211,238,0.4); }
        .btn { width:100%; padding:14px; border:none; border-radius:12px; background:linear-gradient(135deg,var(--accent-2),#fb923c); color:#0f172a; font-weight:700; cursor:pointer; box-shadow:0 12px 30px rgba(249,115,22,0.35); margin-top:12px; }
        .errors { margin:10px 0; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.4); background:rgba(239,68,68,0.08); color:#fecdd3; font-size:14px; }
        .status { margin:10px 0; padding:10px 12px; border-radius:10px; border:1px solid rgba(34,211,238,0.4); background:rgba(34,211,238,0.08); color:#a5f3fc; font-size:14px; }
        .link { color:#e2e8f0; text-decoration:none; }
        .link:hover { color:#fff; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand"><div class="dot"></div><span>Tiwi POS</span></div>
        <h1>Reset password</h1>
        <p>Set a new password for your account.</p>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">

            <label for="password" style="margin-top:12px;">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">

            <label for="password_confirmation" style="margin-top:12px;">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

            <button class="btn" type="submit">Update password</button>
        </form>

        <p style="margin-top:14px; text-align:center;">
            <a class="link" href="{{ route('login') }}">Back to login</a>
        </p>
    </div>
</body>
</html>
