@extends('admin.layout')

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $displayName = ucfirst($currentAdmin?->username ?? 'Admin');
    $statusLabels = ['new' => 'New', 'in_progress' => 'In Progress', 'resolved' => 'Resolved'];
    $statusColors = ['new' => '#2f85ff', 'in_progress' => '#9b50ef', 'resolved' => '#20c98b'];
    $courseColors = ['#2f80ed', '#9349e8', '#20bf87', '#ff8b2a', '#8aa3c5'];
    $courseTotal = max(1, $courseInquiryTotal);
    $courseCursor = 0;
    $courseStops = [];

    foreach ($topCourses as $index => $course) {
        $start = $courseCursor;
        $courseCursor += ((int) $course->inquiry_count / $courseTotal) * 100;
        $color = $courseColors[$index % count($courseColors)];
        $courseStops[] = $color.' '.$start.'% '.$courseCursor.'%';
    }

    if ($courseCursor < 100) {
        $courseStops[] = '#273b55 '.$courseCursor.'% 100%';
    }

    $donutGradient = $topCourses->isEmpty() ? '#273b55 0% 100%' : implode(', ', $courseStops);
    $chartMaximum = max(1, (int) $chartData->max('count'));
    $chartDivisor = max(1, $chartData->count() - 1);
    $chartPoints = $chartData->values()->map(function (array $point, int $index) use ($chartMaximum, $chartDivisor): string {
        $x = ($index / $chartDivisor) * 600;
        $y = 160 - (($point['count'] / $chartMaximum) * 125);
        return round($x, 2).','.round($y, 2);
    })->implode(' ');
@endphp

@section('title', $greeting.', '.$displayName.'! 🧙🏽')
@section('subtitle', "Here's an overview of your ICSA website performance and recent activity.")

@section('content')
    <div class="dashboard-date-row">
        <span><i class="far fa-calendar" aria-hidden="true"></i> {{ now()->format('M j, Y') }}</span>
    </div>

    <section class="dashboard-kpi-grid" aria-label="Website statistics">
        @foreach ([
            ['key' => 'total', 'label' => 'Total Inquiries', 'icon' => 'fas fa-users', 'tone' => 'blue'],
            ['key' => 'new', 'label' => 'New Inquiries', 'icon' => 'fas fa-plus', 'tone' => 'green'],
            ['key' => 'in_progress', 'label' => 'In Progress', 'icon' => 'far fa-clock', 'tone' => 'purple'],
            ['key' => 'resolved', 'label' => 'Resolved', 'icon' => 'far fa-circle-check', 'tone' => 'orange'],
            ['key' => 'courses', 'label' => 'Course Pages', 'icon' => 'far fa-file-lines', 'tone' => 'sky'],
        ] as $card)
            @php
                $trend = $card['key'] === 'courses' ? null : $trends[$card['key']];
                $percentage = $trend['percentage'] ?? null;
                $isNegative = is_int($percentage) && $percentage < 0;
            @endphp
            <article class="dashboard-kpi dashboard-kpi-{{ $card['tone'] }}">
                <div class="dashboard-kpi-top">
                    <span class="dashboard-kpi-icon"><i class="{{ $card['icon'] }}" aria-hidden="true"></i></span>
                    <svg class="dashboard-sparkline" viewBox="0 0 90 40" aria-hidden="true">
                        <path d="M2 35 C17 33, 20 36, 32 28 S48 17, 58 23 S73 30, 88 5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <span class="dashboard-kpi-label">{{ $card['label'] }}</span>
                <strong>{{ $stats[$card['key']] }}</strong>
                <div class="dashboard-kpi-trend {{ $isNegative ? 'is-negative' : '' }}">
                    @if ($card['key'] === 'courses')
                        <span><i class="fas fa-arrow-up" aria-hidden="true"></i> Active</span><small>current catalog</small>
                    @elseif (is_null($percentage))
                        <span><i class="fas fa-arrow-up" aria-hidden="true"></i> New activity</span><small>vs. previous 7 days</small>
                    @else
                        <span><i class="fas fa-arrow-{{ $isNegative ? 'down' : 'up' }}" aria-hidden="true"></i> {{ $percentage >= 0 ? '+' : '' }}{{ $percentage }}%</span><small>vs. previous 7 days</small>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    <section class="dashboard-overview-grid">
        <article class="dashboard-panel dashboard-trend-panel">
            <header class="dashboard-panel-header">
                <div class="dashboard-panel-title">
                    <span class="dashboard-panel-icon"><i class="fas fa-chart-simple" aria-hidden="true"></i></span>
                    <div><h2>Inquiries Overview</h2><p>Total inquiries over the last {{ $chartPeriod }} days</p></div>
                </div>
                <form method="GET" action="{{ route('admin.dashboard') }}" class="dashboard-period-form">
                    <label class="dashboard-period-pill">
                        <span class="sr-only">Chart period</span>
                        <select name="period" onchange="this.form.submit()" aria-label="Select inquiry chart period">
                            @foreach ([7 => 'Last 7 Days', 30 => 'Last 30 Days', 90 => 'Last 90 Days'] as $days => $label)
                                <option value="{{ $days }}" @selected($chartPeriod === $days)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </label>
                </form>
            </header>

            <div class="dashboard-line-chart">
                <svg viewBox="0 0 600 180" role="img" aria-label="Inquiry totals over the last {{ $chartPeriod }} days" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="dashboardChartFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2f85ff" stop-opacity="0.35"/>
                            <stop offset="100%" stop-color="#2f85ff" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <g class="dashboard-chart-grid">
                        <line x1="0" y1="35" x2="600" y2="35"/><line x1="0" y1="77" x2="600" y2="77"/><line x1="0" y1="119" x2="600" y2="119"/><line x1="0" y1="160" x2="600" y2="160"/>
                    </g>
                    <polygon points="0,160 {{ $chartPoints }} 600,160" fill="url(#dashboardChartFill)"/>
                    <polyline points="{{ $chartPoints }}" fill="none" stroke="#3b8cff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    @foreach ($chartData->values() as $index => $point)
                        @php
                            $circleX = ($index / $chartDivisor) * 600;
                            $circleY = 160 - (($point['count'] / $chartMaximum) * 125);
                        @endphp
                        <circle cx="{{ $circleX }}" cy="{{ $circleY }}" r="5" fill="#4d97ff" stroke="#d5e9ff" stroke-width="2"><title>{{ $point['range'] }}: {{ $point['count'] }} inquiries</title></circle>
                    @endforeach
                </svg>
                <div class="dashboard-chart-labels" style="--chart-columns: {{ $chartData->count() }};">
                    @foreach ($chartData as $point)<span>{{ $point['date'] }}</span>@endforeach
                </div>
            </div>
        </article>

        <article class="dashboard-panel dashboard-course-panel">
            <header class="dashboard-panel-header">
                <div class="dashboard-panel-title">
                    <span class="dashboard-panel-icon"><i class="fas fa-graduation-cap" aria-hidden="true"></i></span>
                    <div><h2>Inquiries by Course</h2><p>Course interest breakdown</p></div>
                </div>
            </header>

            @if ($topCourses->isEmpty())
                <p class="admin-empty">No course interest data yet.</p>
            @else
                <div class="dashboard-donut-layout">
                    <div class="dashboard-donut" style="--dashboard-donut: conic-gradient({{ $donutGradient }});">
                        <div><strong>{{ $courseInquiryTotal }}</strong><span>Course inquiries</span></div>
                    </div>
                    <ol class="dashboard-course-legend">
                        @foreach ($topCourses as $index => $course)
                            <li><i style="--legend-color: {{ $courseColors[$index % count($courseColors)] }}"></i><span>{{ $course->course_interest }}</span><strong>{{ $course->inquiry_count }} ({{ round(($course->inquiry_count / $courseTotal) * 100) }}%)</strong></li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </article>

        <aside class="dashboard-panel dashboard-quick-panel">
            <header class="dashboard-panel-header">
                <div class="dashboard-panel-title">
                    <span class="dashboard-panel-icon"><i class="fas fa-bolt" aria-hidden="true"></i></span>
                    <div><h2>Quick Actions</h2><p>Common admin tasks</p></div>
                </div>
            </header>
            <nav class="dashboard-quick-actions">
                <a class="is-blue" href="{{ route('admin.inquiries.index') }}"><span><i class="far fa-envelope" aria-hidden="true"></i> View All Inquiries</span><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                @if (($currentAdmin ?? null)?->canAccess('courses'))
                    <a class="is-green" href="{{ route('admin.courses.create') }}"><span><i class="fas fa-plus" aria-hidden="true"></i> Add New Course</span><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                @endif
                @if (($currentAdmin ?? null)?->isOwner())
                    <a class="is-purple" href="{{ route('admin.users.index') }}"><span><i class="fas fa-users" aria-hidden="true"></i> Manage Users</span><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                @endif
                @if (($currentAdmin ?? null)?->canAccess('media'))
                    <a class="is-orange" href="{{ route('admin.media.index') }}"><span><i class="far fa-image" aria-hidden="true"></i> Upload Media</span><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                @endif
            </nav>
        </aside>
    </section>

    <section class="dashboard-lower-grid">
        <article class="dashboard-panel dashboard-recent-panel">
            <header class="dashboard-panel-header">
                <div class="dashboard-panel-title">
                    <span class="dashboard-panel-icon"><i class="far fa-envelope" aria-hidden="true"></i></span>
                    <div><h2>Recent Inquiries</h2><p>Latest messages submitted through the website</p></div>
                </div>
                <a href="{{ route('admin.inquiries.index') }}">View All</a>
            </header>

            @if ($recent->isEmpty())
                <p class="admin-empty">No inquiries yet.</p>
            @else
                <div class="dashboard-table-scroll">
                    <table class="dashboard-recent-table">
                        <thead><tr><th>Name</th><th>Course / Subject</th><th>Date</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($recent as $message)
                                @php
                                    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($message->name), 0, 1));
                                @endphp
                                <tr>
                                    <td><div class="dashboard-person"><span class="inquiry-avatar inquiry-avatar-{{ $message->id % 6 }}">{{ $initial ?: '?' }}</span><div><strong>{{ $message->name }}</strong><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></div></div></td>
                                    <td>{{ $message->course_interest ?: ($message->subject ?: 'General Inquiry') }}</td>
                                    <td><time>{{ optional($message->created_at)->format('M j, Y') }}</time></td>
                                    <td><span class="inquiry-status inquiry-status-{{ $message->status }}">{{ $statusLabels[$message->status] ?? ucfirst($message->status) }}</span></td>
                                    <td><a class="dashboard-row-link" href="{{ route('admin.inquiries.index', ['search' => $message->email]) }}" aria-label="View {{ $message->name }} inquiry"><i class="fas fa-ellipsis-vertical" aria-hidden="true"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>

        <div class="dashboard-side-stack">
            <article class="dashboard-panel dashboard-status-panel">
                <header class="dashboard-panel-header">
                    <div class="dashboard-panel-title">
                        <span class="dashboard-panel-icon"><i class="far fa-eye" aria-hidden="true"></i></span>
                        <div><h2>Inquiry Status</h2><p>Current workload distribution</p></div>
                    </div>
                </header>
                <div class="dashboard-status-chart">
                    @foreach ($statusLabels as $key => $label)
                        <div><span>{{ $label }}</span><div><i style="width: {{ $stats['total'] ? ($stats[$key] / $stats['total']) * 100 : 0 }}%; --bar-color: {{ $statusColors[$key] }}"></i></div><strong>{{ $stats[$key] }}</strong></div>
                    @endforeach
                </div>
            </article>

            <article class="dashboard-panel dashboard-top-courses">
                <header class="dashboard-panel-header">
                    <div class="dashboard-panel-title">
                        <span class="dashboard-panel-icon"><i class="fas fa-fire" aria-hidden="true"></i></span>
                        <div><h2>Top Course Interest</h2><p>Most requested programs</p></div>
                    </div>
                    <a href="{{ route('admin.inquiries.index') }}">View All</a>
                </header>

                @if ($topCourses->isEmpty())
                    <p class="admin-empty">No course interest data yet.</p>
                @else
                    <ol class="dashboard-ranking">
                        @foreach ($topCourses as $index => $course)
                            <li><span>{{ $index + 1 }}</span><div><strong>{{ $course->course_interest }}</strong><small>{{ $course->inquiry_count }} inquiries</small></div><i><b style="width: {{ ($course->inquiry_count / max(1, $topCourses->max('inquiry_count'))) * 100 }}%"></b></i><em>{{ round(($course->inquiry_count / $courseTotal) * 100) }}%</em></li>
                        @endforeach
                    </ol>
                @endif
            </article>
        </div>
    </section>
@endsection
