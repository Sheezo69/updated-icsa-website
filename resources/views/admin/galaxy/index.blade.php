@extends('admin.layout')

@section('title', 'Galaxy Mode')
@section('subtitle', 'Navigate the entire ICSA enrollment universe in real time.')
@section('title_icon', 'fas fa-meteor')

@section('content')
    <div class="galaxy-page" data-galaxy-root>
        <section class="galaxy-command-bar">
            <div class="galaxy-command-title">
                <span class="galaxy-command-orb"><i></i><b></b></span>
                <div><span>ICSA UNIVERSE</span><h2>Galaxy Command Deck</h2></div>
            </div>
            <div class="galaxy-live-status"><i></i><span><strong>LIVE SYSTEM</strong><small data-galaxy-updated>Synchronizing now</small></span></div>
            <button type="button" class="galaxy-fullscreen-button" data-galaxy-fullscreen><i class="fas fa-expand"></i><span>Command mode</span></button>
        </section>

        <section class="galaxy-stat-strip">
            <article><span class="is-cyan"><i class="fas fa-earth-americas"></i></span><div><strong data-galaxy-stat="courses">{{ $galaxy['stats']['courses'] }}</strong><small>Course planets</small></div></article>
            <article><span class="is-orange"><i class="fas fa-meteor"></i></span><div><strong data-galaxy-stat="active">{{ $galaxy['stats']['active'] }}</strong><small>Inquiry comets</small></div></article>
            <article><span class="is-red"><i class="fas fa-satellite-dish"></i></span><div><strong data-galaxy-stat="unassigned">{{ $galaxy['stats']['unassigned'] }}</strong><small>Unassigned signals</small></div></article>
            <article><span class="is-violet"><i class="fas fa-star"></i></span><div><strong data-galaxy-stat="enrolled">{{ $galaxy['stats']['enrolled'] }}</strong><small>Enrollment stars</small></div></article>
        </section>

        <section class="galaxy-deck">
            <div class="galaxy-canvas-panel">
                <div class="galaxy-filter-rail" role="group" aria-label="Galaxy object filters">
                    <button type="button" class="is-active" data-galaxy-filter="all"><i class="fas fa-atom"></i><span>All signals</span></button>
                    <button type="button" data-galaxy-filter="course"><i class="fas fa-earth-americas"></i><span>Courses</span></button>
                    <button type="button" data-galaxy-filter="inquiry"><i class="fas fa-meteor"></i><span>Inquiries</span></button>
                    <button type="button" data-galaxy-filter="staff"><i class="fas fa-shuttle-space"></i><span>Staff</span></button>
                    <button type="button" data-galaxy-filter="star"><i class="fas fa-star"></i><span>Enrolled</span></button>
                </div>

                <div class="galaxy-viewport" data-galaxy-viewport>
                    <canvas data-galaxy-canvas aria-label="Interactive ICSA galaxy map. Use the object list and inspector for accessible navigation."></canvas>
                    <div class="galaxy-nebula galaxy-nebula-one"></div>
                    <div class="galaxy-nebula galaxy-nebula-two"></div>
                    <div class="galaxy-center-label" data-galaxy-center-label><i class="fas fa-graduation-cap"></i><strong>ICSA</strong><span>KNOWLEDGE CORE</span></div>
                    <div class="galaxy-drag-hint"><i class="fas fa-hand-pointer"></i><span>Drag an inquiry comet onto a staff ship to assign it, or a course planet to qualify it.</span></div>
                    <div class="galaxy-zoom-controls">
                        <button type="button" data-galaxy-zoom="in" aria-label="Zoom in"><i class="fas fa-plus"></i></button>
                        <button type="button" data-galaxy-zoom="out" aria-label="Zoom out"><i class="fas fa-minus"></i></button>
                        <button type="button" data-galaxy-zoom="reset" aria-label="Reset view"><i class="fas fa-crosshairs"></i></button>
                    </div>
                    <div class="galaxy-cursor-card" data-galaxy-cursor-card hidden></div>
                </div>

                <div class="galaxy-timeline">
                    <div><span>TIME NAVIGATOR</span><strong data-galaxy-time-label>Live universe</strong></div>
                    <input type="range" min="0" max="30" value="30" step="1" data-galaxy-timeline aria-label="Replay up to 30 days ago">
                    <div class="galaxy-timeline-scale"><span>30 days ago</span><span>15 days</span><span>Live now</span></div>
                </div>
            </div>

            <aside class="galaxy-inspector" data-galaxy-inspector>
                <header><span>OBJECT INSPECTOR</span><i class="fas fa-satellite"></i></header>
                <div class="galaxy-inspector-visual is-core" data-galaxy-inspector-visual><i class="fas fa-atom" data-galaxy-inspector-icon></i><span></span><b></b></div>
                <span class="galaxy-inspector-type" data-galaxy-inspector-type>COMMAND CORE</span>
                <h2 data-galaxy-inspector-title>ICSA Knowledge Core</h2>
                <p data-galaxy-inspector-copy>Select any planet, comet, ship, or star to reveal its live operational details.</p>
                <div class="galaxy-inspector-metrics" data-galaxy-inspector-metrics>
                    <div><strong>{{ $galaxy['stats']['courses'] }}</strong><span>Planets</span></div>
                    <div><strong>{{ $galaxy['stats']['active'] }}</strong><span>Signals</span></div>
                </div>
                <a href="{{ route('admin.mission-control.index') }}" class="galaxy-inspector-action" data-galaxy-inspector-action>Open Mission Control <i class="fas fa-arrow-right"></i></a>
                <div class="galaxy-object-feed">
                    <header><strong>Active signals</strong><span data-galaxy-object-count>{{ count($galaxy['inquiries']) }}</span></header>
                    <div data-galaxy-object-list></div>
                </div>
            </aside>
        </section>

        <div class="galaxy-action-toast" data-galaxy-toast hidden><i class="fas fa-check"></i><span></span></div>
        <p class="galaxy-sr-status" data-galaxy-status aria-live="polite"></p>
    </div>
@endsection

@push('scripts')
    <script>
        window.ICSA_GALAXY = {
            payload: {{ Illuminate\Support\Js::from($galaxy) }},
            snapshotUrl: {{ Illuminate\Support\Js::from(route('admin.galaxy.snapshot')) }},
            actionUrl: {{ Illuminate\Support\Js::from(route('admin.galaxy.act', ['inquiry' => '__ID__'])) }},
            csrf: {{ Illuminate\Support\Js::from(csrf_token()) }},
            missionUrl: {{ Illuminate\Support\Js::from(route('admin.mission-control.index')) }},
        };
    </script>
    <script src="{{ asset('js/admin-galaxy.js') }}?v={{ filemtime(public_path('js/admin-galaxy.js')) }}"></script>
@endpush
