@php
    $siteHomeUrl = route('home', ['landing' => 1]);
    $activeSection = $activeSection ?? 'home';
    $useLocalAnchors = $useLocalAnchors ?? false;
    $sectionHref = function (string $anchor) use ($siteHomeUrl, $useLocalAnchors) {
        return $useLocalAnchors ? "#{$anchor}" : "{$siteHomeUrl}#{$anchor}";
    };
@endphp

<style>
    .public-nav {
        position: sticky;
        top: 0;
        z-index: 80;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(18px);
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }

    .public-nav__inner {
        margin: 0 auto;
        max-width: 1240px;
        padding: 12px 24px;
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 24px;
        align-items: center;
    }

    .public-nav__brand {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        color: #1f2937;
        text-decoration: none;
    }

    .public-nav__mark {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0c8d3d 0%, #0f9d49 100%);
        color: #fff;
        display: inline-grid;
        place-items: center;
        font-size: 16px;
        font-weight: 800;
        letter-spacing: 0.04em;
        box-shadow: 0 14px 32px rgba(12, 141, 61, 0.22);
    }

    .public-nav__brand-text {
        display: grid;
        gap: 2px;
    }

    .public-nav__title {
        font-size: 18px;
        font-weight: 800;
        letter-spacing: 0.01em;
    }

    .public-nav__subtitle {
        font-size: 12px;
        color: #6b7280;
        font-weight: 600;
    }

    .public-nav__links {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .public-nav__link {
        color: #374151;
        text-decoration: none;
        font-size: 15px;
        font-weight: 700;
        padding: 10px 12px;
        border-radius: 12px;
        transition: background 0.18s ease, color 0.18s ease;
    }

    .public-nav__link:hover {
        background: #f3f4f6;
        color: #111827;
    }

    .public-nav__link.is-active {
        color: #7c3f67;
        background: rgba(124, 63, 103, 0.10);
    }

    .public-nav__actions {
        display: flex;
        align-items: center;
        gap: 12px;
        justify-content: flex-end;
    }

    .public-nav__signin {
        color: #374151;
        text-decoration: none;
        font-size: 15px;
        font-weight: 700;
        padding: 10px 8px;
    }

    .public-nav__cta,
    .public-nav__secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        border-radius: 12px;
        padding: 0 18px;
        font-size: 15px;
        font-weight: 800;
        text-decoration: none;
        border: 1px solid transparent;
    }

    .public-nav__cta {
        background: #7c3f67;
        color: #fff;
        box-shadow: 0 14px 34px rgba(124, 63, 103, 0.20);
    }

    .public-nav__secondary {
        background: #fff;
        color: #1f2937;
        border-color: rgba(15, 23, 42, 0.10);
    }

    @media (max-width: 1080px) {
        .public-nav__inner {
            grid-template-columns: 1fr;
            justify-items: center;
        }

        .public-nav__actions {
            justify-content: center;
        }
    }

    @media (max-width: 720px) {
        .public-nav__inner {
            padding: 12px 16px;
            gap: 14px;
        }

        .public-nav__links,
        .public-nav__actions {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<header class="public-nav">
    <div class="public-nav__inner">
        <a href="{{ $siteHomeUrl }}" class="public-nav__brand">
            <span class="public-nav__mark">TP</span>
            <span class="public-nav__brand-text">
                <span class="public-nav__title">Tiwi POS</span>
                <span class="public-nav__subtitle">Retail · Finance</span>
            </span>
        </a>

        <nav class="public-nav__links" aria-label="Public navigation">
            <a href="{{ $sectionHref('products') }}" class="public-nav__link {{ $activeSection === 'products' ? 'is-active' : '' }}">Products</a>
            <a href="{{ $sectionHref('pricing') }}" class="public-nav__link {{ $activeSection === 'pricing' ? 'is-active' : '' }}">Pricing</a>
            <a href="{{ $sectionHref('resources') }}" class="public-nav__link {{ $activeSection === 'resources' ? 'is-active' : '' }}">Resources</a>
            <a href="{{ $sectionHref('company') }}" class="public-nav__link {{ $activeSection === 'company' ? 'is-active' : '' }}">Company</a>
            <a href="{{ $sectionHref('app-center') }}" class="public-nav__link {{ $activeSection === 'app-center' ? 'is-active' : '' }}">App Center</a>
            <a href="{{ $sectionHref('enterprise') }}" class="public-nav__link {{ $activeSection === 'enterprise' ? 'is-active' : '' }}">Enterprise</a>
            <a href="{{ route('blog.index') }}" class="public-nav__link {{ $activeSection === 'blog' ? 'is-active' : '' }}">Blog</a>
        </nav>

        <div class="public-nav__actions">
            @guest
                <a href="{{ route('login') }}" class="public-nav__signin">Sign in</a>
                <a href="{{ route('register') }}" class="public-nav__cta">Try it free</a>
            @else
                <a href="{{ route('sale') }}" class="public-nav__secondary">Open POS</a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="public-nav__cta" style="cursor:pointer;">Sign out</button>
                </form>
            @endguest
        </div>
    </div>
</header>
