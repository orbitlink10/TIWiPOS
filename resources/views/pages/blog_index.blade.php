<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiwi Blog | POS, Retail, and Growth Insights</title>
    <meta name="description" content="Read Tiwi Blog for practical guides on POS systems, retail operations, payments, and business growth.">
    <link rel="canonical" href="{{ route('blog.index') }}">
    <meta name="robots" content="index,follow,max-image-preview:large">

    <meta property="og:type" content="website">
    <meta property="og:title" content="Tiwi Blog | POS, Retail, and Growth Insights">
    <meta property="og:description" content="Read Tiwi Blog for practical guides on POS systems, retail operations, payments, and business growth.">
    <meta property="og:url" content="{{ route('blog.index') }}">
    <meta property="og:site_name" content="Tiwi Blog">
    <meta property="og:locale" content="en_KE">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Tiwi Blog | POS, Retail, and Growth Insights">
    <meta name="twitter:description" content="Read Tiwi Blog for practical guides on POS systems, retail operations, payments, and business growth.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --hero: #b41543;
            --dark-nav: #121826;
            --line: #e2e8f0;
            --card: #fff;
            --bg: #f8fafc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Manrope", "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--ink);
        }
        .dark-nav {
            background: var(--dark-nav);
            color: #94a3b8;
            padding: 12px 20px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 14px;
            font-weight: 600;
        }
        .dark-nav a {
            color: inherit;
            text-decoration: none;
        }
        .dark-nav a:hover {
            color: #fff;
        }
        .main-nav {
            background: #fff;
            border-bottom: 1px solid var(--line);
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 800;
        }
        .badge {
            width: 34px;
            height: 24px;
            border-radius: 999px;
            display: inline-grid;
            place-items: center;
            background: linear-gradient(135deg, #ff8a3d 0%, #ff5e2a 100%);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
        }
        .hero {
            background: linear-gradient(140deg, #b41543 0%, #8e1238 100%);
            color: #fff;
            padding: 34px 20px;
        }
        .hero-inner {
            max-width: 1140px;
            margin: 0 auto;
        }
        .hero h1 {
            margin: 8px 0 6px;
            font-size: clamp(28px, 4vw, 50px);
            line-height: 1.1;
            letter-spacing: -0.02em;
        }
        .hero p {
            margin: 0;
            font-size: 15px;
            color: rgba(255,255,255,0.9);
        }
        .wrap {
            max-width: 1140px;
            margin: 18px auto;
            padding: 0 20px 24px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 14px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 10px 24px rgba(2,6,23,0.05);
        }
        .card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid var(--line);
            display: block;
        }
        .card-body {
            padding: 12px;
        }
        .kicker {
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 800;
        }
        .title {
            margin: 6px 0 0;
            font-size: 18px;
            line-height: 1.25;
            font-weight: 800;
            color: #0f172a;
        }
        .meta {
            margin-top: 8px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
        }
        .pager {
            margin-top: 16px;
        }
        @media (max-width: 700px) {
            .brand { font-size: 20px; }
        }
    </style>
</head>
<body>
    <div class="dark-nav">
        <a href="{{ route('site.home') }}#products">Products</a>
        <a href="{{ route('site.home') }}#pricing">Pricing</a>
        <a href="{{ route('site.home') }}#resources">Resources</a>
        <a href="{{ route('site.home') }}#company">Company</a>
        <a href="{{ route('site.home') }}#app-center">App Center</a>
        <a href="{{ route('site.home') }}#enterprise">Enterprise</a>
    </div>
    <div class="main-nav">
        <a href="{{ route('site.home') }}" class="brand" style="text-decoration:none; color:inherit;"><span class="badge">TP</span><span>Tiwi Blog</span></a>
        <a href="{{ route('site.home') }}" style="font-weight:700; color:#0f172a; text-decoration:none;">Main Site</a>
    </div>

    <section class="hero">
        <div class="hero-inner">
            <h1>Retail, POS, and Growth Insights</h1>
            <p>Practical articles to help businesses improve sales, operations, and customer experience.</p>
        </div>
    </section>

    <main class="wrap">
        <div class="grid">
            @forelse($posts as $post)
                @php
                    $title = $post->page_title ?: ($post->meta_title ?: \Illuminate\Support\Str::of((string) $post->slug)->replace('-', ' ')->title());
                    $desc = \Illuminate\Support\Str::of((string) ($post->meta_description ?: strip_tags($post->body ?? '')))->squish()->limit(120);
                @endphp
                <a href="{{ route('post.show', ['post' => $post->slug]) }}" class="card">
                    <img
                        src="{{ route('post.image', ['post' => $post]) }}"
                        alt="{{ $post->image_alt_text ?: $title }}"
                        loading="lazy"
                        decoding="async"
                        width="1200"
                        height="630"
                    >
                    <div class="card-body">
                        <div class="kicker">{{ strtoupper($post->type) }}</div>
                        <p class="title">{{ $title }}</p>
                        <div class="meta">{{ $post->created_at->format('M d, Y') }}</div>
                        <div class="meta">{{ $desc }}</div>
                    </div>
                </a>
            @empty
                <div style="grid-column:1/-1; background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px; color:#64748b; font-weight:600;">
                    No published posts yet.
                </div>
            @endforelse
        </div>

        <div class="pager">
            {{ $posts->links() }}
        </div>
    </main>
</body>
</html>
