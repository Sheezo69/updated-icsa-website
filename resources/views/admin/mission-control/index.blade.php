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

    <section class="mission-neural" data-operations-graph>
        <header class="mission-neural-head">
            <div><span>LIVE OPERATIONS GRAPH</span><h2>Neural signal map</h2><p>See how campaigns become visits, course interest, inquiries and staff work. Select any signal to inspect its complete path.</p></div>
            <div class="mission-neural-status"><i></i><strong>{{ count($operationsMap['nodes']) }}</strong><span>signals linked</span></div>
        </header>
        <div class="mission-neural-toolbar">
            <div class="mission-map-filters" role="group" aria-label="Filter map signals">
                @foreach ([['all', 'All signals'], ['campaign', 'Campaigns'], ['visitor', 'Visitors'], ['course', 'Courses'], ['inquiry', 'Inquiries'], ['staff', 'Staff']] as [$type, $label])
                    <button type="button" class="{{ $type === 'all' ? 'is-active' : '' }}" data-map-filter="{{ $type }}"><i></i>{{ $label }}</button>
                @endforeach
            </div>
            <div class="mission-map-controls">
                <button type="button" data-map-zoom="out" aria-label="Zoom out"><i class="fas fa-minus"></i></button>
                <button type="button" data-map-reset aria-label="Reset map"><i class="fas fa-expand"></i></button>
                <button type="button" data-map-zoom="in" aria-label="Zoom in"><i class="fas fa-plus"></i></button>
            </div>
        </div>
        <div class="mission-neural-shell">
            <div class="mission-map-stage" data-map-stage>
                <div class="mission-map-grid" aria-hidden="true"></div>
                <div class="mission-map-scan" aria-hidden="true"></div>
                <svg data-map-svg viewBox="0 0 1200 650" role="img" aria-label="Interactive operations relationship map">
                    <defs>
                        <filter id="mission-node-glow" x="-100%" y="-100%" width="300%" height="300%"><feGaussianBlur stdDeviation="5" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                        <filter id="mission-line-glow" x="-30%" y="-30%" width="160%" height="160%"><feGaussianBlur stdDeviation="2" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                    </defs>
                    <g data-map-viewport><g data-map-edges></g><g data-map-nodes></g></g>
                </svg>
                <div class="mission-map-empty" data-map-empty hidden><i class="fas fa-satellite-dish"></i><strong>No matching signals</strong><span>Choose another layer to continue exploring.</span></div>
                <div class="mission-map-legend"><span class="is-campaign">Campaign</span><span class="is-visitor">Visitor</span><span class="is-course">Course</span><span class="is-inquiry">Inquiry</span><span class="is-staff">Staff</span></div>
                <small class="mission-map-hint"><i class="fas fa-computer-mouse"></i> Drag to move · Scroll to zoom · Select a node to inspect</small>
            </div>
            <aside class="mission-node-console" data-node-console aria-live="polite">
                <div class="mission-node-console-idle"><span><i class="fas fa-share-nodes"></i></span><small>SIGNAL INSPECTOR</small><h3>Select a node</h3><p>Choose any glowing signal to reveal its metrics, journey and available action.</p></div>
            </aside>
        </div>
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

    const graphRoot = document.querySelector('[data-operations-graph]');
    const graphData = {{ Illuminate\Support\Js::from($operationsMap) }};
    let renderOperationsGraph = () => {};
    if (graphRoot) {
        const svg = graphRoot.querySelector('[data-map-svg]');
        const viewport = graphRoot.querySelector('[data-map-viewport]');
        const edgeLayer = graphRoot.querySelector('[data-map-edges]');
        const nodeLayer = graphRoot.querySelector('[data-map-nodes]');
        const consoleNode = graphRoot.querySelector('[data-node-console]');
        const emptyNode = graphRoot.querySelector('[data-map-empty]');
        const ns = 'http://www.w3.org/2000/svg';
        const palette = { campaign:'#a78bfa', visitor:'#22d3ee', course:'#3b82f6', inquiry:'#fb923c', staff:'#34d399' };
        const glyphs = { campaign:'C', visitor:'V', course:'O', inquiry:'I', staff:'S' };
        const order = ['campaign', 'visitor', 'course', 'inquiry', 'staff'];
        const positions = new Map();
        const nodeElements = new Map();
        const edgeElements = [];
        let filter = 'all';
        let transform = { x:0, y:0, scale:1 };
        let drag = null;
        let selectedNodeId = null;

        const visibleNodes = () => graphData.nodes.filter(node => filter === 'all' || node.type === filter || graphData.edges.some(edge => (edge.from === node.id || edge.to === node.id) && graphData.nodes.find(item => item.id === (edge.from === node.id ? edge.to : edge.from))?.type === filter));
        const applyTransform = () => viewport.setAttribute('transform', `translate(${transform.x} ${transform.y}) scale(${transform.scale})`);
        const resetMap = () => { transform = { x:0, y:0, scale:1 }; applyTransform(); };

        const layout = () => {
            positions.clear();
            const active = visibleNodes();
            const types = order.filter(type => active.some(node => node.type === type));
            types.forEach((type, column) => {
                const list = active.filter(node => node.type === type);
                const x = types.length === 1 ? 600 : 100 + (1000 / (types.length - 1)) * column;
                list.forEach((node, index) => positions.set(node.id, { x, y:90 + (470 / Math.max(1, list.length - 1)) * index }));
            });
            types.forEach(type => { const list = active.filter(node => node.type === type); if (list.length === 1) positions.get(list[0].id).y = 325; });
            return active;
        };

        const inspect = node => {
            selectedNodeId = node.id;
            const color = palette[node.type];
            const metrics = (node.metrics || []).map(item => `<div><small>${escape(item.label)}</small><strong>${escape(item.value)}</strong></div>`).join('');
            const journey = (node.journey || []).map((step, index) => `<li><i>${index + 1}</i><span>${escape(step)}</span></li>`).join('');
            consoleNode.innerHTML = `<div class="mission-console-top" style="--node-color:${color}"><span>${glyphs[node.type]}</span><div><small>${escape(node.eyebrow)}</small><h3>${escape(node.label)}</h3></div><i></i></div><p>${escape(node.summary)}</p>${metrics ? `<div class="mission-console-metrics">${metrics}</div>` : ''}<div class="mission-console-journey"><small>CONNECTED JOURNEY</small><ol>${journey || '<li><span>No journey events yet.</span></li>'}</ol></div>${node.action ? `<a href="${escape(node.action.url)}">${escape(node.action.label)} <i class="fas fa-arrow-up-right-from-square"></i></a>` : '<span class="mission-console-passive"><i class="fas fa-shield-halved"></i> Observation signal · no direct action</span>'}`;
            nodeElements.forEach((element, id) => element.classList.toggle('is-selected', id === node.id));
            const neighbours = new Set([node.id]);
            graphData.edges.forEach(edge => { if (edge.from === node.id) neighbours.add(edge.to); if (edge.to === node.id) neighbours.add(edge.from); });
            nodeElements.forEach((element, id) => element.classList.toggle('is-dimmed', !neighbours.has(id)));
            edgeElements.forEach(({ element, edge }) => element.classList.toggle('is-dimmed', edge.from !== node.id && edge.to !== node.id));
        };

        const renderGraph = () => {
            edgeLayer.replaceChildren(); nodeLayer.replaceChildren(); nodeElements.clear(); edgeElements.length = 0;
            const active = layout();
            const activeIds = new Set(active.map(node => node.id));
            emptyNode.hidden = active.length > 0;
            if (!active.length) {
                selectedNodeId = null;
                consoleNode.innerHTML = '<div class="mission-node-console-idle"><span><i class="fas fa-satellite-dish"></i></span><small>SIGNAL INSPECTOR</small><h3>Layer awaiting data</h3><p>This signal type will appear automatically when matching live activity is recorded.</p></div>';
            }
            graphData.edges.filter(edge => activeIds.has(edge.from) && activeIds.has(edge.to)).forEach((edge, index) => {
                const from = positions.get(edge.from), to = positions.get(edge.to);
                if (!from || !to) return;
                const path = document.createElementNS(ns, 'path');
                const bend = Math.max(55, Math.abs(to.x - from.x) * .42);
                path.setAttribute('d', `M${from.x},${from.y} C${from.x + bend},${from.y} ${to.x - bend},${to.y} ${to.x},${to.y}`);
                path.setAttribute('class', 'mission-map-edge'); path.setAttribute('data-edge', index);
                edgeLayer.appendChild(path); edgeElements.push({ element:path, edge });
                const pulse = document.createElementNS(ns, 'circle');
                pulse.setAttribute('r', '2.6'); pulse.setAttribute('class', 'mission-map-particle');
                const motion = document.createElementNS(ns, 'animateMotion');
                motion.setAttribute('dur', `${2.5 + (index % 5) * .55}s`); motion.setAttribute('repeatCount', 'indefinite'); motion.setAttribute('path', path.getAttribute('d'));
                pulse.appendChild(motion); edgeLayer.appendChild(pulse);
            });
            active.forEach(node => {
                const point = positions.get(node.id), group = document.createElementNS(ns, 'g');
                group.setAttribute('class', `mission-map-node is-${node.type}`); group.setAttribute('transform', `translate(${point.x} ${point.y})`); group.setAttribute('tabindex', '0'); group.setAttribute('role', 'button'); group.setAttribute('aria-label', `${node.eyebrow}: ${node.label}`);
                const halo = document.createElementNS(ns, 'circle'); halo.setAttribute('class', 'mission-map-node-halo'); halo.setAttribute('r', `${31 + Math.min(12, Number(node.signal || 0) / 10)}`);
                const orbit = document.createElementNS(ns, 'circle'); orbit.setAttribute('class', 'mission-map-node-orbit'); orbit.setAttribute('r', '28');
                const core = document.createElementNS(ns, 'circle'); core.setAttribute('class', 'mission-map-node-core'); core.setAttribute('r', '19');
                const glyph = document.createElementNS(ns, 'text'); glyph.setAttribute('class', 'mission-map-node-glyph'); glyph.setAttribute('text-anchor', 'middle'); glyph.setAttribute('dy', '.35em'); glyph.textContent = glyphs[node.type];
                const label = document.createElementNS(ns, 'text'); label.setAttribute('class', 'mission-map-node-label'); label.setAttribute('text-anchor', 'middle'); label.setAttribute('y', '50'); label.textContent = node.label.length > 20 ? `${node.label.slice(0, 19)}…` : node.label;
                const type = document.createElementNS(ns, 'text'); type.setAttribute('class', 'mission-map-node-type'); type.setAttribute('text-anchor', 'middle'); type.setAttribute('y', '64'); type.textContent = node.type.toUpperCase();
                group.append(halo, orbit, core, glyph, label, type); group.addEventListener('click', event => { event.stopPropagation(); inspect(node); }); group.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); inspect(node); } });
                nodeLayer.appendChild(group); nodeElements.set(node.id, group);
            });
            const priority = active.find(node => node.id === selectedNodeId) || active.find(node => node.type === 'inquiry') || active[0]; if (priority) inspect(priority);
        };

        graphRoot.querySelectorAll('[data-map-filter]').forEach(button => button.addEventListener('click', () => { filter = button.dataset.mapFilter; graphRoot.querySelectorAll('[data-map-filter]').forEach(item => item.classList.toggle('is-active', item === button)); resetMap(); renderGraph(); }));
        graphRoot.querySelectorAll('[data-map-zoom]').forEach(button => button.addEventListener('click', () => { transform.scale = Math.max(.65, Math.min(2.2, transform.scale + (button.dataset.mapZoom === 'in' ? .18 : -.18))); applyTransform(); }));
        graphRoot.querySelector('[data-map-reset]').addEventListener('click', resetMap);
        svg.addEventListener('wheel', event => { event.preventDefault(); transform.scale = Math.max(.65, Math.min(2.2, transform.scale + (event.deltaY < 0 ? .1 : -.1))); applyTransform(); }, { passive:false });
        svg.addEventListener('pointerdown', event => { if (event.target.closest('.mission-map-node')) return; drag = { x:event.clientX, y:event.clientY, tx:transform.x, ty:transform.y }; svg.setPointerCapture(event.pointerId); });
        svg.addEventListener('pointermove', event => { if (!drag) return; transform.x = drag.tx + (event.clientX - drag.x) / transform.scale; transform.y = drag.ty + (event.clientY - drag.y) / transform.scale; applyTransform(); });
        svg.addEventListener('pointerup', () => drag = null); svg.addEventListener('pointercancel', () => drag = null);
        renderOperationsGraph = renderGraph;
        renderGraph();
    }
    const refresh = async () => {
        if (document.hidden) return;
        try {
            const response = await fetch(root.dataset.snapshotUrl, { headers:{ Accept:'application/json' }, credentials:'same-origin' });
            if (!response.ok) return;
            const data = await response.json();
            if (data.operations_map) {
                graphData.nodes = data.operations_map.nodes || [];
                graphData.edges = data.operations_map.edges || [];
                const signalCount = graphRoot?.querySelector('.mission-neural-status strong');
                if (signalCount) signalCount.textContent = graphData.nodes.length.toLocaleString();
                renderOperationsGraph();
            }
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
