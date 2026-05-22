@php
    $siteHomeUrl = route('home', ['landing' => 1]);
    $activeSection = $activeSection ?? 'home';
    $useLocalAnchors = $useLocalAnchors ?? false;
    $sectionHref = function (string $anchor) use ($siteHomeUrl, $useLocalAnchors) {
        return $useLocalAnchors ? "#{$anchor}" : "{$siteHomeUrl}#{$anchor}";
    };
@endphp

<style>
    .znav {
        position: sticky;
        top: 0;
        z-index: 80;
        background: #fff;
        border-bottom: 1px solid rgba(10, 100, 188, 0.10);
        box-shadow: 0 2px 16px rgba(10, 100, 188, 0.07);
    }

    .znav__inner {
        margin: 0 auto;
        max-width: 1240px;
        padding: 0 24px;
        height: 64px;
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 24px;
        align-items: center;
    }

    /* Brand */
    .znav__brand {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: #1a2d3d;
    }

    .znav__logo {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: linear-gradient(135deg, #0a64bc 0%, #1a8ad4 100%);
        display: grid;
        place-items: center;
        color: #fff;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 0.03em;
        flex-shrink: 0;
    }

    .znav__wordmark {
        font-size: 17px;
        font-weight: 800;
        letter-spacing: -0.01em;
        color: #0a64bc;
    }

    .znav__wordmark span {
        color: #F05A22;
    }

    /* Links */
    .znav__links {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 2px;
        flex-wrap: wrap;
    }

    .znav__link {
        color: #374151;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        padding: 8px 12px;
        border-radius: 6px;
        transition: background 0.15s, color 0.15s;
        white-space: nowrap;
    }

    .znav__link:hover {
        background: rgba(10, 100, 188, 0.06);
        color: #0a64bc;
    }

    .znav__link.is-active {
        color: #0a64bc;
        background: rgba(10, 100, 188, 0.08);
    }

    /* Actions */
    .znav__actions {
        display: flex;
        align-items: center;
        gap: 10px;
        justify-content: flex-end;
    }

    .znav__signin {
        color: #374151;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        padding: 8px 12px;
        border-radius: 6px;
        transition: background 0.15s, color 0.15s;
    }

    .znav__signin:hover { background: #f3f4f6; color: #111827; }

    .znav__cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 38px;
        padding: 0 18px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        background: #F05A22;
        color: #fff;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(240, 90, 34, 0.30);
        transition: background 0.2s, transform 0.15s;
        white-space: nowrap;
    }

    .znav__cta:hover { background: #d44d1a; transform: translateY(-1px); }

    .znav__outline {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 38px;
        padding: 0 18px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        background: #fff;
        color: #1a2d3d;
        border: 1.5px solid rgba(10, 100, 188, 0.20);
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s;
        white-space: nowrap;
    }

    .znav__outline:hover { background: #f4f9ff; border-color: rgba(10, 100, 188, 0.35); }

    @media (max-width: 1080px) {
        .znav__inner {
            grid-template-columns: 1fr;
            height: auto;
            padding: 12px 20px;
            justify-items: center;
            gap: 10px;
        }

        .znav__actions { justify-content: center; }
    }

    @media (max-width: 640px) {
        .znav__links { display: none; }
    }
</style>

<header class="znav">
    <div class="znav__inner">
        <a href="{{ $siteHomeUrl }}" class="znav__brand">
            <span class="znav__logo">TP</span>
            <span class="znav__wordmark">Tiwi<span>POS</span></span>
        </a>

        <nav class="znav__links" aria-label="Main navigation">
            <a href="{{ $sectionHref('products') }}"   class="znav__link {{ $activeSection === 'products'   ? 'is-active' : '' }}">Products</a>
            <a href="{{ $sectionHref('pricing') }}"    class="znav__link {{ $activeSection === 'pricing'    ? 'is-active' : '' }}">Pricing</a>
            <a href="{{ $sectionHref('resources') }}"  class="znav__link {{ $activeSection === 'resources'  ? 'is-active' : '' }}">Resources</a>
            <a href="{{ $sectionHref('company') }}"    class="znav__link {{ $activeSection === 'company'    ? 'is-active' : '' }}">Company</a>
            <a href="{{ $sectionHref('app-center') }}" class="znav__link {{ $activeSection === 'app-center' ? 'is-active' : '' }}">App Center</a>
            <a href="{{ $sectionHref('enterprise') }}" class="znav__link {{ $activeSection === 'enterprise' ? 'is-active' : '' }}">Enterprise</a>
            <a href="{{ route('blog.index') }}"         class="znav__link {{ $activeSection === 'blog'       ? 'is-active' : '' }}">Blog</a>
        </nav>

        <div class="znav__actions">
            @guest
                <a href="{{ route('login') }}"    class="znav__signin">Sign in</a>
                <a href="{{ route('register') }}" class="znav__cta">Get started free</a>
            @else
                <a href="{{ route('sale') }}"    class="znav__outline">Open POS</a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="znav__cta" style="cursor:pointer;">Sign out</button>
                </form>
            @endguest
        </div>
    </div>
</header>
