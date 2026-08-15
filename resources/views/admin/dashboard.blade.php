@extends('admin.layout')

@section('title', 'Dashboard')
@section('subtitle', 'Overview of inquiries, activity, and course content.')

@section('content')
    <section class="admin-stats-grid">
        <div class="admin-stat-card">
            <span>Total Inquiries</span>
            <strong>{{ $stats['total'] }}</strong>
        </div>
        <div class="admin-stat-card">
            <span>New</span>
            <strong>{{ $stats['new'] }}</strong>
        </div>
        <div class="admin-stat-card">
            <span>In Progress</span>
            <strong>{{ $stats['in_progress'] }}</strong>
        </div>
        <div class="admin-stat-card">
            <span>Resolved</span>
            <strong>{{ $stats['resolved'] }}</strong>
        </div>
        <div class="admin-stat-card">
            <span>Course Pages</span>
            <strong>{{ $stats['courses'] }}</strong>
        </div>
    </section>

    <section class="admin-grid">
        <div class="admin-panel">
            <div class="admin-panel-header">
                <div>
                    <h2 class="admin-section-title">Recent Inquiries</h2>
                    <p class="admin-section-subtitle">Latest messages submitted through the website.</p>
                </div>
                <a href="{{ route('admin.inquiries.index') }}" class="admin-btn admin-btn-secondary">Manage</a>
            </div>

            @if ($recent->isEmpty())
                <p class="admin-empty">No inquiries yet.</p>
            @else
                <div class="admin-mini-list">
                    @foreach ($recent as $message)
                        <div class="admin-mini-item">
                            <div class="admin-inline-actions" style="justify-content: space-between;">
                                <strong>{{ $message->name }}</strong>
                                <span class="admin-badge admin-badge-{{ $message->status }}">{{ str_replace('_', ' ', $message->status) }}</span>
                            </div>
                            <p class="admin-note">{{ $message->email }} | {{ $message->phone }}</p>
                            <p style="margin: 0.65rem 0 0;">{{ \Illuminate\Support\Str::limit($message->message ?: 'No message body provided.', 120) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="admin-stack">
            <div class="admin-panel">
                <div class="admin-panel-header">
                    <div>
                        <h2 class="admin-section-title">Last 7 Days</h2>
                        <p class="admin-section-subtitle">Daily submission volume.</p>
                    </div>
                </div>

                @php($maxCount = max(1, $chartData->max('count')))
                <div class="admin-chart">
                    @foreach ($chartData as $point)
                        <div class="admin-chart-row">
                            <span class="admin-muted">{{ $point['date'] }}</span>
                            <div class="admin-chart-track">
                                <div class="admin-chart-bar" style="width: {{ ($point['count'] / $maxCount) * 100 }}%"></div>
                            </div>
                            <strong>{{ $point['count'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="admin-panel">
                <div class="admin-panel-header">
                    <div>
                        <h2 class="admin-section-title">Top Course Interest</h2>
                        <p class="admin-section-subtitle">Most requested programs.</p>
                    </div>
                </div>

                @if ($topCourses->isEmpty())
                    <p class="admin-empty">No course interest data yet.</p>
                @else
                    <div class="admin-mini-list">
                        @foreach ($topCourses as $row)
                            <div class="admin-mini-item">
                                <strong>{{ $row->course_interest }}</strong>
                                <p class="admin-note">{{ $row->inquiry_count }} inquiries</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
