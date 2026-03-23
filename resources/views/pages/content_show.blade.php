<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="Tiwi Blog">
    <meta property="og:locale" content="en_KE">
    <meta property="og:image" content="{{ $imageMeta['url'] }}">
    <meta property="og:image:width" content="{{ $imageMeta['width'] }}">
    <meta property="og:image:height" content="{{ $imageMeta['height'] }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $imageMeta['url'] }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">

    @php
        $siteHomeUrl = route('home', ['landing' => 1]);
        $breadcrumbJsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => $siteHomeUrl,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Blog',
                    'item' => route('blog.index'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $post->page_title ?: ($post->meta_title ?: 'Post'),
                    'item' => $canonicalUrl,
                ],
            ],
        ];

        $articleJsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $seoTitle,
            'description' => $seoDescription,
            'image' => [[
                '@type' => 'ImageObject',
                'url' => $imageMeta['url'],
                'width' => $imageMeta['width'],
                'height' => $imageMeta['height'],
            ]],
            'author' => [
                '@type' => 'Person',
                'name' => 'Tiwi Editorial',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Tiwi Blog',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => url('/favicon.ico'),
                ],
            ],
            'datePublished' => optional($post->created_at)->toAtomString(),
            'dateModified' => optional($post->updated_at ?: $post->created_at)->toAtomString(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonicalUrl,
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($breadcrumbJsonLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/ld+json">{!! json_encode($articleJsonLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>

    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --hero: #b41543;
            --light-bg: #f8fafc;
            --card: #ffffff;
            --line: #e2e8f0;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Manrope", "Segoe UI", sans-serif;
            color: var(--ink);
            background: var(--light-bg);
        }
        .hero {
            background: var(--hero);
            color: #fff;
            padding: 42px 20px 36px;
        }
        .hero-inner {
            max-width: 1100px;
            margin: 0 auto;
        }
        .crumbs {
            color: rgba(255,255,255,0.92);
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        h1 {
            margin: 14px 0 12px;
            font-size: clamp(30px, 4.2vw, 54px);
            line-height: 1.1;
            letter-spacing: -0.03em;
            max-width: 920px;
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
            max-width: 1100px;
            margin: 18px auto;
            padding: 0 20px 24px;
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
            max-height: 480px;
            object-fit: cover;
            border-bottom: 1px solid var(--line);
            display: block;
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
            max-width: 1100px;
            margin: 0 auto 30px;
            padding: 0 20px;
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
            .hero {
                padding: 30px 14px 26px;
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
    @include('partials.public_navigation', ['activeSection' => 'blog'])

    <section class="hero">
        <div class="hero-inner">
            <div class="crumbs">
                <span>Tiwi</span>
                <span>&gt;</span>
                <a href="{{ route('blog.index') }}" style="color:inherit; text-decoration:none;">Blog</a>
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
            <img
                class="cover"
                src="{{ $imageMeta['url'] }}"
                alt="{{ $post->image_alt_text ?: ($post->page_title ?: 'Post image') }}"
                width="{{ $imageMeta['width'] }}"
                height="{{ $imageMeta['height'] }}"
                loading="eager"
                fetchpriority="high"
                decoding="async"
                sizes="(max-width: 1100px) 100vw, 1100px"
            >
            <div class="article">
                @if($post->heading_two)
                    <h2>{{ $post->heading_two }}</h2>
                @endif
                @if($post->meta_description)
                    <p><strong>{{ $post->meta_description }}</strong></p>
                @endif
                {!! $renderedBody ?? $post->body !!}
            </div>
        </article>
    </div>

    <div class="bottom-actions">
        <a class="action-link" href="{{ route('blog.index') }}">All Articles</a>
        @auth
            @if(auth()->user()->is_super_admin)
            <a class="action-link" href="{{ route('content.edit', $post) }}">Edit This Page</a>
            @endif
        @endauth
    </div>
</body>
</html>
