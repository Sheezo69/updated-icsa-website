@extends('admin.layout')

@section('title', 'Website Analytics')
@section('title_icon', 'fas fa-chart-pie')
@section('subtitle', 'Privacy-first insight into how visitors discover and use the public website.')

@php
    $timelineMax = max(1, (int) $timeline->max('views'));
    $pageMax = max(1, (int) $pages->max('visits'));
    $deviceTotal = max(1, (int) $devices->sum('visits'));
    $deviceValues = $devices->keyBy(fn ($item) => strtolower($item['label']));
    $mobilePercent = (($deviceValues->get('mobile')['visits'] ?? 0) / $deviceTotal) * 100;
    $tabletPercent = (($deviceValues->get('tablet')['visits'] ?? 0) / $deviceTotal) * 100;
    $desktopPercent = (($deviceValues->get('desktop')['visits'] ?? 0) / $deviceTotal) * 100;
    $signedPercent = $summary['views'] > 0 ? round(($summary['signed_in'] / $summary['views']) * 100) : 0;
@endphp

@section('content')
    <section class="analytics-hero">
        <div class="analytics-hero-copy">
            <span class="analytics-kicker"><i class="fas fa-satellite-dish"></i> Live traffic intelligence</span>
            <h2>See the signal behind every visit.</h2>
            <p>Every number below uses anonymous visitor identification. Raw IP addresses are never stored.</p>
        </div>
        <div class="analytics-orbit" aria-hidden="true"><span></span><i></i><b></b></div>
        <div class="analytics-range-copy"><span>Current report</span><strong>{{ $rangeLabel }}</strong><small>{{ ['day' => 'Daily', 'month' => 'Monthly', 'year' => 'Yearly'][$bucket] }} activity</small></div>
    </section>

    <nav class="analytics-range-tabs" aria-label="Analytics reporting period">
        @foreach ($ranges as $value => $label)
            <a href="{{ route('admin.analytics.index', ['range' => $value, 'limit' => $limit]) }}" class="{{ $range === $value ? 'is-active' : '' }}">
                <span>{{ $label }}</span>@if ($range === $value)<i class="fas fa-circle-check"></i>@endif
            </a>
        @endforeach
    </nav>

    @if (! $analyticsAvailable)
        <div class="analytics-setup-state">
            <span><i class="fas fa-database"></i></span>
            <div><h2>Analytics storage is not ready yet</h2><p>Public pages are still working normally. Run the analytics migration to begin collecting anonymous traffic.</p></div>
        </div>
    @endif

    <section class="analytics-summary-grid" aria-label="Traffic summary">
        <article class="analytics-summary-card is-blue"><span class="analytics-summary-icon"><i class="fas fa-eye"></i></span><div><small>Total page views</small><strong>{{ number_format($summary['views']) }}</strong><p>Every tracked public page opening.</p></div><i class="fas fa-arrow-trend-up analytics-card-watermark"></i></article>
        <article class="analytics-summary-card is-cyan"><span class="analytics-summary-icon"><i class="fas fa-users-viewfinder"></i></span><div><small>Estimated visitors</small><strong>{{ number_format($summary['visitors']) }}</strong><p>Anonymous browser identifiers.</p></div><i class="fas fa-fingerprint analytics-card-watermark"></i></article>
        <article class="analytics-summary-card is-violet"><span class="analytics-summary-icon"><i class="fas fa-user-check"></i></span><div><small>Signed-in visits</small><strong>{{ number_format($summary['signed_in']) }}</strong><p>{{ $signedPercent }}% of tracked page views.</p></div><i class="fas fa-shield-halved analytics-card-watermark"></i></article>
        <article class="analytics-summary-card is-amber"><span class="analytics-summary-icon"><i class="fas fa-fire-flame-curved"></i></span><div><small>Most visited page</small><strong class="is-page">{{ $summary['top_page']['secondary'] ?? $summary['top_page']['label'] ?? 'Waiting for visits' }}</strong><p>{{ isset($summary['top_page']) ? number_format($summary['top_page']['visits']).' views' : 'No traffic in this range.' }}</p></div><i class="fas fa-ranking-star analytics-card-watermark"></i></article>
    </section>

    <section class="analytics-main-grid">
        <article class="analytics-panel analytics-trend-panel">
            <header class="analytics-panel-header"><div><span class="analytics-panel-label">Traffic trend</span><h2>What happened over time?</h2><p>Each column is one {{ $bucket }}. Taller columns mean more page views.</p></div><span class="analytics-live-chip"><i></i> {{ $timeline->where('views', '>', 0)->count() }} active periods</span></header>
            @if ($timeline->sum('views') > 0)
                <div class="analytics-bar-chart" style="--chart-columns: {{ $timeline->count() }}">
                    @foreach ($timeline as $point)
                        <div class="analytics-bar-column" title="{{ $point['label'] }}: {{ $point['views'] }} views, {{ $point['visitors'] }} visitors">
                            <span class="analytics-bar-value">{{ $point['views'] ?: '' }}</span>
                            <i style="--bar-height: {{ max(3, ($point['views'] / $timelineMax) * 100) }}%"></i>
                            <small>{{ $timeline->count() <= 15 || $loop->first || $loop->last || $loop->iteration % max(1, (int) ceil($timeline->count() / 7)) === 0 ? $point['label'] : '' }}</small>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="analytics-empty-chart"><i class="fas fa-chart-column"></i><strong>No visitor activity yet</strong><span>Real browser visits will appear here automatically.</span></div>
            @endif
        </article>

        <article class="analytics-panel analytics-device-panel">
            <header class="analytics-panel-header"><div><span class="analytics-panel-label">Devices</span><h2>How visitors opened the site</h2><p>Desktop, mobile and tablet usage.</p></div></header>
            <div class="analytics-donut-wrap">
                <div class="analytics-donut" style="--mobile: {{ $mobilePercent }}%; --tablet: {{ $mobilePercent + $tabletPercent }}%;"><span><strong>{{ number_format($devices->sum('visits')) }}</strong><small>views</small></span></div>
                <div class="analytics-donut-legend">
                    @foreach ([['Mobile', '#4c8dff'], ['Tablet', '#a855f7'], ['Desktop', '#2dd4bf']] as [$label, $color])
                        @php
                            $item = $deviceValues->get(strtolower($label));
                        @endphp
                        <div><i style="background:{{ $color }}"></i><span>{{ $label }}</span><strong>{{ number_format($item['visits'] ?? 0) }}</strong></div>
                    @endforeach
                </div>
            </div>
        </article>
    </section>

    <section class="analytics-panel analytics-pages-chart-panel">
        <header class="analytics-panel-header"><div><span class="analytics-panel-label">Content performance</span><h2>Which pages attract attention?</h2><p>The longest bars identify the strongest public pages in the selected range.</p></div><span class="analytics-count-chip">{{ $pages->count() }} pages shown</span></header>
        <div class="analytics-page-bars">
            @forelse ($pages as $page)
                <div class="analytics-page-bar"><div><strong>{{ $page['secondary'] ?: $page['label'] }}</strong><small>{{ $page['label'] }}</small></div><span><i style="width:{{ ($page['visits'] / $pageMax) * 100 }}%"></i></span><b>{{ number_format($page['visits']) }} views</b></div>
            @empty
                <div class="analytics-list-empty"><i class="fas fa-file-circle-question"></i> No pages recorded for this period.</div>
            @endforelse
        </div>
    </section>

    <section class="analytics-panel analytics-activity-panel">
        <header class="analytics-panel-header"><div><span class="analytics-panel-label">{{ strtoupper(['day' => 'Daily', 'month' => 'Monthly', 'year' => 'Yearly'][$bucket]) }}</span><h2>Exact activity breakdown</h2><p>The same traffic trend shown as precise page-view and visitor totals.</p></div></header>
        <div class="analytics-activity-list">
            @forelse ($timeline as $point)
                <div><time>{{ $point['label'] }}</time><span><i style="width:{{ ($point['views'] / $timelineMax) * 100 }}%"></i></span><strong>{{ number_format($point['views']) }} views</strong><small>{{ number_format($point['visitors']) }} unique</small></div>
            @empty
                <div class="analytics-list-empty">No activity to display.</div>
            @endforelse
        </div>
    </section>

    <div class="analytics-list-toolbar">
        <div><h2>Where traffic came from</h2><p>Ranked details explain the sources, technology and campaigns behind the charts.</p></div>
        <form method="GET" action="{{ route('admin.analytics.index') }}"><input type="hidden" name="range" value="{{ $range }}"><label>Rows per list <select name="limit" onchange="this.form.submit()">@foreach ([8, 15, 25, 50] as $size)<option value="{{ $size }}" @selected($limit === $size)>{{ $size }}</option>@endforeach</select></label></form>
    </div>

    <section class="analytics-rank-grid">
        @foreach ([
            ['title' => 'Top pages', 'subtitle' => 'Public pages with the most views.', 'icon' => 'fa-file-lines', 'items' => $pages, 'empty' => 'No page visits yet.'],
            ['title' => 'Referring websites', 'subtitle' => 'External sites that sent visitors.', 'icon' => 'fa-arrow-up-right-from-square', 'items' => $referrers, 'empty' => 'No external referrals in this period.'],
            ['title' => 'Devices', 'subtitle' => 'Screen categories used by visitors.', 'icon' => 'fa-mobile-screen', 'items' => $devices, 'empty' => 'No device data yet.'],
            ['title' => 'Browsers', 'subtitle' => 'Web browsers used to open the site.', 'icon' => 'fa-compass', 'items' => $browsers, 'empty' => 'No browser data yet.'],
            ['title' => 'Operating systems', 'subtitle' => 'Platforms powering visitor devices.', 'icon' => 'fa-laptop-code', 'items' => $systems, 'empty' => 'No operating-system data yet.'],
            ['title' => 'Campaigns', 'subtitle' => 'UTM campaign names found in links.', 'icon' => 'fa-bullhorn', 'items' => $campaigns, 'empty' => 'No tagged campaigns in this period.'],
            ['title' => 'Countries', 'subtitle' => 'General location when supplied by the network.', 'icon' => 'fa-earth-asia', 'items' => $countries, 'empty' => 'No country information available.'],
        ] as $card)
            <article class="analytics-rank-card">
                <header><span><i class="fas {{ $card['icon'] }}"></i></span><div><h3>{{ $card['title'] }}</h3><p>{{ $card['subtitle'] }}</p></div></header>
                <div class="analytics-rank-list">
                    @forelse ($card['items'] as $item)
                        <div><span><strong>{{ $item['secondary'] ?: $item['label'] }}</strong>@if ($item['secondary'])<small>{{ $item['label'] }}</small>@endif</span><b>{{ number_format($item['visits']) }} <small>views</small></b></div>
                    @empty
                        <div class="analytics-rank-empty">{{ $card['empty'] }}</div>
                    @endforelse
                </div>
            </article>
        @endforeach
        <article class="analytics-privacy-card"><span><i class="fas fa-user-shield"></i></span><h3>Privacy by design</h3><p>Visitor estimates use a random first-party identifier stored only as a one-way hash. Raw IP addresses never enter analytics storage.</p><small><i class="fas fa-check"></i> Bots filtered</small><small><i class="fas fa-check"></i> Admins excluded</small><small><i class="fas fa-check"></i> Failure-safe tracking</small></article>
    </section>
@endsection
