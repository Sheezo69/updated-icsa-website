@extends('admin.layout')

@section('title', 'Mission Control')
@section('title_icon', 'fas fa-satellite-dish')
@section('subtitle', 'Live operational intelligence for traffic, leads, staff and course demand.')

@php
    $staffMax = max(1, (int) $staff->max('active'));
    $demandMax = max(1, (int) $demand->max('signal'));
@endphp

@section('content')
    <section class="mission-cockpit" data-mission-root data-snapshot-url="{{ route('admin.mission-control.snapshot') }}">
        <div class="mission-cockpit-grid" aria-hidden="true"></div>
        <div class="mission-cockpit-copy">
            <span class="mission-system-state"><i></i> Intelligence systems online</span>
            <h2>ICSA <em>Mission Control</em></h2>
            <p>A live command surface joining anonymous traffic signals, inquiry intent, team workload and operational risk.</p>
        </div>
        <div class="mission-radar" aria-hidden="true"><span></span><i></i><b></b></div>
        <div class="mission-clock"><span>Kuwait operations time</span><strong data-mission-clock>{{ now()->format('H:i:s') }}</strong><small data-mission-updated>Live snapshot · just now</small></div>
    </section>

    <form method="GET" action="{{ route('admin.mission-control.index') }}" class="mission-command-bar">
        <span><i class="fas fa-terminal"></i></span>
        <label><small>COMMAND INTELLIGENCE</small><input name="command" value="{{ $command }}" placeholder="Ask: show hot leads, staff workload, course demand, traffic today..."></label>
        <kbd>ENTER</kbd>
        <button type="submit"><i class="fas fa-wand-magic-sparkles"></i> Run</button>
    </form>

    @if ($commandResult)
        <section class="mission-command-result">
            <span><i class="fas fa-sparkles"></i></span>
            <div><small>MISSION RESPONSE</small><h3>{{ $commandResult['title'] }}</h3><p>{{ $commandResult['message'] }}</p>
                @if ($commandResult['items'] !== [])
                    <div class="mission-command-items">
                        @foreach (array_slice($commandResult['items'], 0, 6) as $item)
                            <span><strong>{{ $item['name'] ?? $item['label'] ?? $item['course'] ?? 'Result' }}</strong><small>{{ isset($item['score']) ? $item['score'].' score' : (isset($item['active']) ? $item['active'].' active' : (isset($item['signal']) ? $item['signal'].' demand signal' : '')) }}</small></span>
                        @endforeach
                    </div>
                @endif
            </div>
            <a href="{{ route('admin.mission-control.index') }}" aria-label="Clear command"><i class="fas fa-xmark"></i></a>
        </section>
    @endif

    @if (! $analyticsReady)
        <div class="mission-offline-note"><i class="fas fa-triangle-exclamation"></i><span><strong>Traffic intelligence is awaiting analytics storage.</strong> Lead and team operations remain available.</span></div>
    @endif

    <section class="mission-kpis">
        @foreach ([
            ['key' => 'live_visitors', 'label' => 'Live visitors', 'value' => $stats['live_visitors'], 'note' => 'active in 15 minutes', 'icon' => 'fa-signal', 'tone' => 'cyan'],
            ['key' => 'views_today', 'label' => 'Views today', 'value' => $stats['views_today'], 'note' => ($stats['traffic_delta'] >= 0 ? '+' : '').$stats['traffic_delta'].'% last-hour velocity', 'icon' => 'fa-eye', 'tone' => 'blue'],
            ['key' => 'hot_leads', 'label' => 'Hot leads', 'value' => $stats['hot_leads'], 'note' => 'high-intent inquiries', 'icon' => 'fa-fire-flame-curved', 'tone' => 'orange'],
            ['key' => 'unassigned', 'label' => 'Unassigned', 'value' => $stats['unassigned'], 'note' => 'waiting for ownership', 'icon' => 'fa-user-clock', 'tone' => 'violet'],
        ] as $card)
            <article class="mission-kpi is-{{ $card['tone'] }}"><span><i class="fas {{ $card['icon'] }}"></i></span><div><small>{{ $card['label'] }}</small><strong data-mission-stat="{{ $card['key'] }}">{{ number_format($card['value']) }}</strong><p>{{ $card['note'] }}</p></div><b></b></article>
        @endforeach
    </section>

    <section class="mission-primary-grid">
        <article class="mission-panel mission-globe-panel">
            <header><div><span>GLOBAL PULSE</span><h2>Anonymous traffic orbit</h2><p>General country signals from the last 24 hours. No raw IP data.</p></div><i class="fas fa-earth-asia"></i></header>
            <div class="mission-globe-stage">
                <div class="mission-globe" aria-hidden="true"><i class="is-longitude one"></i><i class="is-longitude two"></i><i class="is-latitude one"></i><i class="is-latitude two"></i><span></span></div>
                <div class="mission-country-signals">
                    @forelse ($countries as $country)
                        <div><i></i><strong>{{ $country->country_code }}</strong><span>{{ number_format($country->visitors) }} visitors</span><b>{{ number_format($country->visits) }}</b></div>
                    @empty
                        <div class="is-empty"><i></i><strong>AWAITING SIGNAL</strong><span>Country data appears when supplied by the visitor network.</span></div>
                    @endforelse
                </div>
            </div>
        </article>

        <article class="mission-panel mission-feed-panel">
            <header><div><span>LIVE EVENT STREAM</span><h2>What is happening now</h2><p>Public visits, new inquiries and team operations.</p></div><i class="fas fa-wave-square"></i></header>
            <div class="mission-feed" data-mission-feed>
                @forelse ($feed as $event)
                    <div class="is-{{ $event['type'] }}"><span><i class="fas fa-{{ $event['type'] === 'visit' ? 'location-arrow' : ($event['type'] === 'inquiry' ? 'envelope-open-text' : 'user-shield') }}"></i></span><div><strong>{{ $event['title'] }}</strong><small>{{ $event['meta'] }}</small></div><time datetime="{{ $event['time']?->toIso8601String() }}">{{ $event['time']?->diffForHumans() }}</time></div>
                @empty
                    <div class="mission-empty"><i class="fas fa-satellite"></i> Waiting for the first operational signal.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mission-alert-strip" data-mission-alerts>
        @foreach ($alerts as $alert)
            <article class="is-{{ $alert['tone'] }}"><span><i class="fas {{ $alert['icon'] }}"></i></span><div><strong>{{ $alert['title'] }}</strong><small>{{ $alert['text'] }}</small></div></article>
        @endforeach
    </section>

    <section class="mission-panel mission-leads-panel">
        <header><div><span>LEAD RADAR</span><h2>Who is most likely to enroll?</h2><p>Scores use form intent and anonymous journey signals—not personal profiling.</p></div><a href="{{ route('admin.inquiries.index') }}">Open inquiries <i class="fas fa-arrow-right"></i></a></header>
        <div class="mission-lead-table">
            <div class="mission-lead-head"><span>Lead</span><span>Intent signal</span><span>Journey</span><span>Owner</span><span>Response target</span></div>
            @forelse ($leads as $lead)
                <a href="{{ route('admin.inquiries.index', ['open' => $lead['id']]) }}" class="mission-lead-row">
                    <span class="mission-lead-person"><i>{{ strtoupper(substr($lead['name'], 0, 1)) }}</i><span><strong>{{ $lead['name'] }}</strong><small>{{ $lead['course'] }}</small></span></span>
                    <span class="mission-score-cell"><i class="mission-score-ring is-{{ strtolower($lead['temperature']) }}" style="--score:{{ $lead['score'] * 3.6 }}deg"><b>{{ $lead['score'] }}</b></i><span><strong>{{ $lead['temperature'] }}</strong><small>intent score</small></span></span>
                    <span><strong>{{ $lead['views'] }} page views</strong><small>{{ $lead['source'] ?: 'Direct / unknown source' }}</small></span>
                    <span><strong>{{ $lead['assignee'] ?: 'Unassigned' }}</strong><small>{{ ucfirst(str_replace('_', ' ', $lead['status'])) }}</small></span>
                    <span class="mission-sla {{ $lead['sla_overdue'] > 0 ? 'is-overdue' : '' }}"><strong>{{ $lead['sla_overdue'] > 0 ? $lead['sla_overdue'].'m overdue' : $lead['sla_remaining'].'m remaining' }}</strong><small>30-minute response SLA</small></span>
                </a>
            @empty
                <div class="mission-empty"><i class="fas fa-crosshairs"></i> No active leads are waiting.</div>
            @endforelse
        </div>
    </section>

    <section class="mission-secondary-grid">
        <article class="mission-panel mission-journey-panel">
            <header><div><span>VISITOR JOURNEYS</span><h2>Paths through the website</h2><p>Anonymous page sequences from the last six hours.</p></div><i class="fas fa-route"></i></header>
            <div class="mission-journeys" data-mission-journeys>
                @forelse ($journeys as $journey)
                    <div><span class="mission-visitor-id"><i></i>{{ $journey['visitor'] }}</span><div class="mission-path">@foreach ($journey['pages'] as $page)<b>{{ $page }}</b>@if (! $loop->last)<i class="fas fa-chevron-right"></i>@endif @endforeach</div><small>{{ implode(' · ', array_filter([$journey['country'], $journey['device'], $journey['views'].' views'])) }} · {{ $journey['last_seen']?->diffForHumans() }}</small></div>
                @empty
                    <div class="mission-empty"><i class="fas fa-route"></i> Anonymous journeys will appear as visitors browse.</div>
                @endforelse
            </div>
        </article>

        <article class="mission-panel mission-briefing-panel">
            <header><div><span>OPERATIONAL BRIEFING</span><h2>Command summary</h2><p>A plain-language reading of current conditions.</p></div><i class="fas fa-brain"></i></header>
            <div class="mission-briefing-orb"><span><i class="fas fa-sparkles"></i></span></div>
            <blockquote>“{{ $briefing }}”</blockquote>
            <small>Generated deterministically from live site data · no external AI service</small>
        </article>
    </section>

    <section class="mission-tertiary-grid">
        <article class="mission-panel mission-demand-panel">
            <header><div><span>DEMAND FORECAST</span><h2>Courses gaining attention</h2><p>Weighted from 90-day page views and inquiry intent.</p></div><i class="fas fa-arrow-trend-up"></i></header>
            <div class="mission-demand-list">
                @forelse ($demand as $course)
                    <div><span><strong>{{ $course['label'] }}</strong><small>{{ $course['inquiries'] }} inquiries · {{ $course['views'] }} page views</small></span><i><b style="width:{{ ($course['signal'] / $demandMax) * 100 }}%"></b></i><em>{{ $course['signal'] }}</em></div>
                @empty
                    <div class="mission-empty">Demand signals need course visits or inquiries.</div>
                @endforelse
            </div>
        </article>

        <article class="mission-panel mission-staff-panel">
            <header><div><span>TEAM CAPACITY</span><h2>Staff workload radar</h2><p>Active assignments and overdue first responses.</p></div><i class="fas fa-users-gear"></i></header>
            <div class="mission-staff-list">
                @forelse ($staff as $member)
                    <div><span>{{ strtoupper(substr($member['name'], 0, 1)) }}</span><div><strong>{{ $member['name'] }}</strong><small>{{ $member['new'] }} new · {{ $member['overdue'] }} overdue</small><i><b style="width:{{ ($member['active'] / $staffMax) * 100 }}%"></b></i></div><em>{{ $member['active'] }} active</em></div>
                @empty
                    <div class="mission-empty">No staff accounts are configured.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mission-automation-zone">
        <header><div><span>AUTOMATION BAY</span><h2>Controlled one-click operations</h2><p>Every action requires confirmation and is recorded in the admin activity log.</p></div><i class="fas fa-gears"></i></header>
        <div>
            <form method="POST" action="{{ route('admin.mission-control.automate') }}" onsubmit="return confirm('Distribute all unassigned active inquiries evenly across staff?');">@csrf<input type="hidden" name="action" value="balance_workload"><span><i class="fas fa-scale-balanced"></i></span><div><strong>Balance workload</strong><small>Distribute unassigned inquiries to the least-loaded staff first.</small></div><button type="submit">Execute <i class="fas fa-bolt"></i></button></form>
            <form method="POST" action="{{ route('admin.mission-control.automate') }}" onsubmit="return confirm('Recalculate intelligence scores for every active inquiry?');">@csrf<input type="hidden" name="action" value="recalculate_scores"><span><i class="fas fa-crosshairs"></i></span><div><strong>Refresh lead intelligence</strong><small>Recalculate intent using the newest anonymous journey signals.</small></div><button type="submit">Execute <i class="fas fa-bolt"></i></button></form>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-mission-root]');
    if (!root) return;
    const clock = document.querySelector('[data-mission-clock]');
    const renderClock = () => { if (clock) clock.textContent = new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Kuwait', hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:false }).format(new Date()); };
    renderClock(); window.setInterval(renderClock, 1000);

    const icons = { visit:'location-arrow', inquiry:'envelope-open-text', admin:'user-shield' };
    const escape = value => { const node = document.createElement('div'); node.textContent = value ?? ''; return node.innerHTML; };
    const refresh = async () => {
        if (document.hidden) return;
        try {
            const response = await fetch(root.dataset.snapshotUrl, { headers:{ Accept:'application/json' }, credentials:'same-origin' });
            if (!response.ok) return;
            const data = await response.json();
            Object.entries(data.stats).forEach(([key,value]) => { const node = document.querySelector(`[data-mission-stat="${key}"]`); if (node && key !== 'traffic_delta') node.textContent = Number(value).toLocaleString(); });
            const feed = document.querySelector('[data-mission-feed]');
            if (feed && data.feed.length) feed.innerHTML = data.feed.map(event => `<div class="is-${event.type}"><span><i class="fas fa-${icons[event.type] || 'circle'}"></i></span><div><strong>${escape(event.title)}</strong><small>${escape(event.meta)}</small></div><time>${escape(event.ago)}</time></div>`).join('');
            document.querySelector('[data-mission-updated]').textContent = 'Live snapshot · updated just now';
        } catch (_) {}
    };
    window.setInterval(refresh, 10000);
})();
</script>
@endpush
