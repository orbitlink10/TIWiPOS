<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post->page_title ?? $post->meta_title ?? 'Page Preview' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --hero: #b41543;
            --dark-nav: #121826;
            --light-bg: #f8fafc;
            --card: #ffffff;
            --line: #e2e8f0;
            --accent: #ff6b35;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Manrope", "Segoe UI", sans-serif;
            color: var(--ink);
            background: var(--light-bg);
        }

        .top-promo {
            background: linear-gradient(90deg, #cdddf1 0%, #b3cde8 100%);
            padding: 10px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-weight: 700;
            font-size: 14px;
        }

        .top-promo .btn {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            color: #fff;
            border: 0;
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 800;
            cursor: pointer;
        }

        .dark-nav {
            background: var(--dark-nav);
            color: #94a3b8;
            padding: 14px 22px;
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            font-weight: 600;
            font-size: 14px;
        }

        .main-nav {
            background: #fff;
            border-bottom: 1px solid var(--line);
            padding: 16px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 24px;
            color: #111827;
        }

        .brand-badge {
            width: 34px;
            height: 24px;
            border-radius: 999px;
            background: linear-gradient(135deg, #ff8a3d 0%, #ff5e2a 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
        }

        .main-links {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            color: #1f2937;
            font-weight: 700;
            font-size: 15px;
        }

        .cta {
            background: linear-gradient(135deg, #ff7c33 0%, #ff5d2a 100%);
            color: #fff;
            border-radius: 10px;
            padding: 10px 14px;
            text-decoration: none;
            font-weight: 800;
        }

        .hero {
            background: var(--hero);
            color: #fff;
            padding: 44px 18px 40px;
        }

        .hero-inner {
            max-width: 1160px;
            margin: 0 auto;
        }

        .crumbs {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        h1 {
            margin: 14px 0 12px;
            font-size: clamp(30px, 4.2vw, 56px);
            line-height: 1.1;
            letter-spacing: -0.03em;
            max-width: 980px;
        }

        .meta {
            font-size: 15px;
            font-weight: 600;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.96);
        }

        .content-wrap {
            max-width: 1160px;
            margin: 22px auto;
            padding: 0 18px 24px;
        }

        .content-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 12px 28px rgba(2, 6, 23, 0.06);
            overflow: hidden;
        }

        .cover {
            width: 100%;
            max-height: 440px;
            object-fit: cover;
            border-bottom: 1px solid var(--line);
        }

        .article {
            padding: 20px;
            font-size: 18px;
            line-height: 1.65;
            color: #1e293b;
        }

        .article h2, .article h3, .article h4 {
            color: #0f172a;
            line-height: 1.2;
            margin: 24px 0 12px;
        }

        .article p {
            margin: 0 0 14px;
        }

        .article a {
            color: #0f69d9;
        }

        .article img, .article iframe, .article video {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .bottom-actions {
            max-width: 1160px;
            margin: 0 auto 34px;
            padding: 0 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-link {
            text-decoration: none;
            border: 1px solid #d3deed;
            background: #fff;
            color: #0f1f3a;
            border-radius: 10px;
            padding: 11px 14px;
            font-weight: 700;
        }

        @media (max-width: 840px) {
            .dark-nav {
                padding: 12px 14px;
                gap: 10px;
                font-size: 13px;
            }
            .main-nav {
                padding: 14px;
            }
            .brand {
                font-size: 20px;
            }
            .hero {
                padding: 34px 14px 30px;
            }
            .crumbs {
                font-size: 13px;
            }
            .meta {
                font-size: 13px;
            }
            .article {
                font-size: 16px;
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="top-promo">
        <span>Tiwi Pages: Performance-focused content experience</span>
        <button class="btn" type="button">Discover More</button>
    </div>

    <div class="dark-nav">
        <span>Products</span>
        <span>Pricing</span>
        <span>Resources</span>
        <span>Company</span>
        <span>App Center</span>
        <span>Enterprise</span>
    </div>

    <div class="main-nav">
        <div class="brand">
            <span class="brand-badge">TP</span>
            <span>Tiwi Blog</span>
        </div>
        <div class="main-links">
            <span>SEO</span>
            <span>Marketing</span>
            <span>News &amp; Research</span>
            <span>Product</span>
            <a class="cta" href="{{ route('content.index') }}">Back to Pages</a>
        </div>
    </div>

    <section class="hero">
        <div class="hero-inner">
            <div class="crumbs">
                <span>Tiwi</span>
                <span>&gt;</span>
                <span>Blog</span>
                <span>&gt;</span>
                <span>{{ ucfirst($post->type) }}</span>
            </div>
            <h1>{{ $post->page_title ?? $post->meta_title ?? 'Untitled Page' }}</h1>
            <div class="meta">
                <span>Author: Tiwi Editorial</span>
                <span>|</span>
                <span>{{ $readMinutes }} min read</span>
                <span>|</span>
                <span>{{ $post->created_at->format('M d, Y') }}</span>
            </div>
        </div>
    </section>

    <div class="content-wrap">
        <article class="content-card">
            @if($post->image_path)
                <img class="cover" src="{{ asset('storage/' . $post->image_path) }}" alt="{{ $post->image_alt_text ?: 'Post image' }}">
            @endif
            <div class="article">
                @if($post->heading_two)
                    <h2>{{ $post->heading_two }}</h2>
                @endif
                @if($post->meta_description)
                    <p><strong>{{ $post->meta_description }}</strong></p>
                @endif
                {!! $post->body !!}
            </div>
        </article>
    </div>

    <div class="bottom-actions">
        <a class="action-link" href="{{ route('content.index') }}">All Pages</a>
        <a class="action-link" href="{{ route('content.edit', $post) }}">Edit This Page</a>
    </div>
</body>
</html>
