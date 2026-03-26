@extends('layouts.app')

@section('title', 'Services')

@push('styles')
<style>
    .services-shell {
        display: grid;
        gap: 16px;
    }

    .services-hero {
        background:
            radial-gradient(circle at top left, rgba(20, 184, 166, 0.22), transparent 38%),
            radial-gradient(circle at bottom right, rgba(244, 114, 182, 0.18), transparent 34%),
            linear-gradient(135deg, #fff7ed 0%, #ffffff 48%, #f0fdfa 100%);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 18px 45px rgba(12, 30, 60, 0.08);
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(280px, 1fr);
        gap: 18px;
        align-items: start;
    }

    .services-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(15, 127, 167, 0.1);
        color: #0f6f9d;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .services-title {
        margin: 14px 0 0;
        font-size: 34px;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .services-subtitle {
        margin: 10px 0 0;
        color: var(--muted);
        font-size: 15px;
        line-height: 1.7;
        max-width: 680px;
    }

    .services-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .services-tag {
        display: inline-flex;
        align-items: center;
        padding: 9px 12px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid rgba(148, 163, 184, 0.28);
        color: #24415f;
        font-size: 13px;
        font-weight: 700;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
    }

    .services-sidecard {
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid rgba(216, 225, 238, 0.88);
        border-radius: 18px;
        padding: 18px;
        backdrop-filter: blur(10px);
    }

    .services-sidecard h3 {
        margin: 0 0 6px;
        font-size: 18px;
        font-weight: 800;
    }

    .services-sidecard p {
        margin: 0;
        color: var(--muted);
        font-size: 14px;
        line-height: 1.6;
    }

    .services-sidecard ul {
        margin: 14px 0 0;
        padding: 0;
        list-style: none;
        display: grid;
        gap: 10px;
    }

    .services-sidecard li {
        padding: 10px 12px;
        border-radius: 12px;
        background: #ffffff;
        border: 1px solid rgba(216, 225, 238, 0.92);
        color: #24415f;
        font-size: 13px;
        font-weight: 700;
    }

    .services-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: minmax(0, 1.6fr) minmax(300px, 1fr);
    }

    .services-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: 0 18px 45px rgba(12, 30, 60, 0.08);
        overflow: hidden;
    }

    .services-card-head {
        padding: 20px 22px 14px;
        border-bottom: 1px solid rgba(216, 225, 238, 0.72);
    }

    .services-card-head h2,
    .services-card-head h3 {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .services-card-head p {
        margin: 6px 0 0;
        color: var(--muted);
        font-size: 14px;
    }

    .service-category-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        padding: 18px 22px 22px;
    }

    .service-category {
        border: 1px solid rgba(216, 225, 238, 0.92);
        border-radius: 16px;
        padding: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }

    .service-category-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .service-category h3 {
        margin: 0;
        font-size: 17px;
        font-weight: 800;
    }

    .service-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        border-radius: 10px;
        background: rgba(15, 127, 167, 0.1);
        color: #0f6f9d;
        font-weight: 800;
        font-size: 13px;
    }

    .service-category p {
        margin: 10px 0 0;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.6;
    }

    .service-anchor {
        margin-top: 12px;
        color: #0f1b2d;
        font-size: 13px;
        font-weight: 700;
    }

    .services-table-wrap {
        padding: 0 22px 22px;
        overflow: auto;
    }

    .services-table {
        width: 100%;
        min-width: 620px;
        border-collapse: collapse;
        font-size: 14px;
    }

    .services-table th {
        text-align: left;
        padding: 12px;
        background: #f7f9fc;
        color: #41546e;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .services-table td {
        padding: 14px 12px;
        border-top: 1px solid rgba(226, 232, 240, 0.88);
    }

    .services-table td.right {
        text-align: right;
        white-space: nowrap;
    }

    .service-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 10px;
        border-radius: 999px;
        background: #ecfeff;
        color: #0f766e;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .bundle-list,
    .standards-list {
        display: grid;
        gap: 12px;
        padding: 18px 22px 22px;
    }

    .bundle-item,
    .standard-item {
        padding: 14px 16px;
        border-radius: 16px;
        border: 1px solid rgba(216, 225, 238, 0.92);
        background: #f8fbff;
    }

    .bundle-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .bundle-item strong {
        font-size: 15px;
    }

    .bundle-price {
        color: #0f6f9d;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .bundle-copy,
    .standard-item {
        margin: 8px 0 0;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.6;
    }

    @media (max-width: 1024px) {
        .services-hero,
        .services-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 720px) {
        .services-title {
            font-size: 28px;
        }

        .services-card-head,
        .service-category-grid,
        .services-table-wrap,
        .bundle-list,
        .standards-list {
            padding-left: 16px;
            padding-right: 16px;
        }
    }
</style>
@endpush

@section('header')
    <div class="header-row">
        <h1>Services</h1>
    </div>
@endsection

@section('content')
    <div class="services-shell">
        <section class="services-hero">
            <div>
                <span class="services-kicker">Salon / Spa Service Desk</span>
                <h2 class="services-title">Build your service catalog for beauty, grooming, and wellness.</h2>
                <p class="services-subtitle">
                    Keep salon and spa treatments organized by category, duration, and price so front-desk teams can quote faster,
                    schedule smarter, and keep premium packages visible at the point of sale.
                </p>
                <div class="services-tags">
                    <span class="services-tag">Hair styling</span>
                    <span class="services-tag">Nails and polish</span>
                    <span class="services-tag">Facials and skin care</span>
                    <span class="services-tag">Massage therapy</span>
                    <span class="services-tag">Waxing and grooming</span>
                </div>
            </div>

            <div class="services-sidecard">
                <h3>Service snapshot</h3>
                <p>Use this section as the salon/spa command center for your most bookable treatments and bundled sessions.</p>
                <ul>
                    <li>{{ $serviceStats['active_services'] }} active services ready for checkout</li>
                    <li>{{ $serviceStats['signature_packages'] }} signature packages for upsell bundles</li>
                    <li>{{ $serviceStats['avg_visit_time'] }} average visit time across premium treatments</li>
                    <li>{{ $serviceStats['median_ticket'] }} median ticket for wellness bookings</li>
                </ul>
            </div>
        </section>

        <div class="services-grid">
            <section class="services-card">
                <div class="services-card-head">
                    <h2>Salon & Spa Categories</h2>
                    <p>Group treatments by room type and technician skill so the service catalog stays easy to manage.</p>
                </div>
                <div class="service-category-grid">
                    @foreach($serviceCategories as $category)
                        <article class="service-category">
                            <div class="service-category-top">
                                <h3>{{ $category['name'] }}</h3>
                                <span class="service-count">{{ $category['count'] }}</span>
                            </div>
                            <p>{{ $category['lead'] }}</p>
                            <div class="service-anchor">{{ $category['anchor'] }}</div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="services-card">
                <div class="services-card-head">
                    <h3>Package Ideas</h3>
                    <p>Bundle fast-moving services to lift average spend without slowing down the desk team.</p>
                </div>
                <div class="bundle-list">
                    @foreach($serviceBundles as $bundle)
                        <article class="bundle-item">
                            <div class="bundle-top">
                                <strong>{{ $bundle['name'] }}</strong>
                                <span class="bundle-price">{{ $bundle['price'] }}</span>
                            </div>
                            <p class="bundle-copy">{{ $bundle['items'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="services-grid">
            <section class="services-card">
                <div class="services-card-head">
                    <h2>Featured Service Menu</h2>
                    <p>Core treatment list for a salon/spa front desk, with durations and pricing ready for quoting.</p>
                </div>
                <div class="services-table-wrap">
                    <table class="services-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Category</th>
                                <th>Duration</th>
                                <th class="right">Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($featuredServices as $service)
                                <tr>
                                    <td><strong>{{ $service['name'] }}</strong></td>
                                    <td>{{ $service['category'] }}</td>
                                    <td>{{ $service['duration'] }}</td>
                                    <td class="right">KES {{ number_format($service['price'], 2) }}</td>
                                    <td><span class="service-status">{{ $service['status'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="services-card">
                <div class="services-card-head">
                    <h3>Service Standards</h3>
                    <p>Operational notes that help salon and spa teams maintain smooth turnover and consistent client care.</p>
                </div>
                <div class="standards-list">
                    @foreach($serviceStandards as $standard)
                        <article class="standard-item">{{ $standard }}</article>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
@endsection
