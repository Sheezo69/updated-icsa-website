@extends('admin.layout')

@section('title', 'Activity Log')
@section('title_icon', 'fas fa-clock-rotate-left')
@section('subtitle', 'A permanent record of important administrator and staff actions.')

@section('content')
    @php
        $timezone = $currentAdmin->timezone ?? 'Asia/Kuwait';
    @endphp
    <section class="activity-stats" aria-label="Activity summary">
        <article><span class="activity-stat-icon is-blue"><i class="fas fa-wave-square"></i></span><div><span>All Activity</span><strong>{{ number_format($stats['total']) }}</strong></div></article>
        <article><span class="activity-stat-icon is-green"><i class="fas fa-calendar-day"></i></span><div><span>Today</span><strong>{{ number_format($stats['today']) }}</strong></div></article>
        <article><span class="activity-stat-icon is-purple"><i class="fas fa-user-shield"></i></span><div><span>Staff Actions</span><strong>{{ number_format($stats['staff']) }}</strong></div></article>
        <article><span class="activity-stat-icon is-red"><i class="fas fa-trash-can"></i></span><div><span>Deletions</span><strong>{{ number_format($stats['deletions']) }}</strong></div></article>
    </section>

    <section class="admin-card activity-filter-card">
        <form method="GET" action="{{ route('admin.activity.index') }}" class="activity-filter-form">
            <label class="activity-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search actor, target, IP or activity...">
            </label>
            <select name="actor" aria-label="Filter by actor">
                <option value="">All team members</option>
                @foreach ($actors as $actor)
                    <option value="{{ $actor->admin_id }}" @selected((string) ($filters['actor'] ?? '') === (string) $actor->admin_id)>{{ $actor->actor_name }}</option>
                @endforeach
            </select>
            <select name="section" aria-label="Filter by section">
                <option value="">All sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section }}" @selected(($filters['section'] ?? '') === $section)>{{ Illuminate\Support\Str::headline($section) }}</option>
                @endforeach
            </select>
            <select name="action" aria-label="Filter by action">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ Illuminate\Support\Str::headline(Illuminate\Support\Str::after($action, '.')) }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" aria-label="From date">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" aria-label="To date">
            <button class="admin-btn admin-btn-primary" type="submit"><i class="fas fa-filter"></i> Apply</button>
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.activity.index') }}">Clear</a>
            <a class="admin-btn admin-btn-secondary activity-export" href="{{ route('admin.activity.export', array_filter($filters)) }}"><i class="fas fa-download"></i> Export CSV</a>
        </form>
    </section>

    <section class="activity-feed">
        @forelse ($logs as $log)
            @php
                $tone = str_contains($log->action, 'deleted') ? 'danger' : (str_contains($log->action, 'created') || str_contains($log->action, 'uploaded') || str_contains($log->action, 'login') ? 'success' : (str_contains($log->action, 'password') ? 'security' : 'info'));
                $before = $log->before_values ?? [];
                $after = $log->after_values ?? [];
            @endphp
            <article class="activity-entry">
                <div class="activity-time">
                    <strong>{{ $log->created_at?->timezone($timezone)->format('M d, Y') }}</strong>
                    <span>{{ $log->created_at?->timezone($timezone)->format('h:i:s A') }}</span>
                </div>
                <div class="activity-actor">
                    <span class="activity-avatar">{{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($log->actor_name, 0, 1)) }}</span>
                    <div><strong>{{ $log->actor_name }}</strong><span>{{ ucfirst($log->actor_role) }}</span></div>
                </div>
                <div class="activity-main">
                    <div class="activity-main-head">
                        <span class="activity-action is-{{ $tone }}">{{ $log->actionLabel() }}</span>
                        <span class="activity-section">{{ Illuminate\Support\Str::headline($log->section) }}</span>
                    </div>
                    <strong>{{ $log->subject_label ?: 'System activity' }}</strong>
                    <p>{{ $log->description }}</p>
                </div>
                <div class="activity-source">
                    <span><i class="fas fa-location-dot"></i> {{ $log->ip_address ?: 'Unknown IP' }}</span>
                    <span title="{{ $log->user_agent }}"><i class="fas fa-display"></i> {{ $log->deviceLabel() }}</span>
                </div>
                <div class="activity-details-cell">
                    @if ($before !== [] || $after !== [])
                        <details class="activity-details">
                            <summary>View changes <i class="fas fa-chevron-down"></i></summary>
                            <div class="activity-change-grid">
                                <div><span>Before</span><pre>{{ $before !== [] ? json_encode($before, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '—' }}</pre></div>
                                <div><span>After</span><pre>{{ $after !== [] ? json_encode($after, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '—' }}</pre></div>
                            </div>
                        </details>
                    @else
                        <span class="admin-muted">No field changes</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="admin-card activity-empty">
                <i class="fas fa-shield-halved"></i>
                <h2>No activity found</h2>
                <p>Important admin and staff actions will appear here.</p>
            </div>
        @endforelse
    </section>

    @if ($logs->hasPages())
        <div class="admin-pagination">{{ $logs->links() }}</div>
    @endif
@endsection
