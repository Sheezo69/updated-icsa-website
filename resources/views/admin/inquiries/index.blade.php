@extends('admin.layout')

@section('title', 'Inquiries')
@section('subtitle', 'Review and update the messages submitted from the public site.')

@section('content')
    <section class="admin-stats-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
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
    </section>

    <section class="admin-card">
        <form method="GET" action="{{ route('admin.inquiries.index') }}" class="admin-form-grid">
            <div class="admin-field">
                <label for="status">Status</label>
                <select id="status" name="status" class="admin-select">
                    <option value="">All statuses</option>
                    @foreach (['new' => 'New', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'archived' => 'Archived'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="admin-field">
                <label for="search">Search</label>
                <input id="search" name="search" class="admin-input" value="{{ $filters['search'] ?? '' }}" placeholder="Name, email, or message">
            </div>

            <div class="admin-field">
                <label for="date_from">From</label>
                <input id="date_from" type="date" name="date_from" class="admin-input" value="{{ $filters['date_from'] ?? '' }}">
            </div>

            <div class="admin-field">
                <label for="date_to">To</label>
                <input id="date_to" type="date" name="date_to" class="admin-input" value="{{ $filters['date_to'] ?? '' }}">
            </div>

            <div class="admin-actions admin-field-full">
                <button type="submit" class="admin-btn admin-btn-primary">Apply Filters</button>
                <a href="{{ route('admin.inquiries.index') }}" class="admin-btn admin-btn-secondary">Clear</a>
                <a href="{{ route('admin.inquiries.export', request()->query()) }}" class="admin-btn admin-btn-secondary">Export CSV</a>
            </div>
        </form>
    </section>

    <section class="admin-table-wrap" style="margin-top: 1rem;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Contact</th>
                    <th>Course / Subject</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inquiries as $inquiry)
                    <tr>
                        <td>#{{ $inquiry->id }}</td>
                        <td>
                            <strong>{{ $inquiry->name }}</strong><br>
                            <span class="admin-muted">{{ $inquiry->email }}</span><br>
                            <span class="admin-muted">{{ $inquiry->phone }}</span>
                        </td>
                        <td>
                            <strong>{{ $inquiry->course_interest ?: 'General' }}</strong><br>
                            <span class="admin-muted">{{ $inquiry->subject ?: 'No subject' }}</span>
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($inquiry->message ?: 'No message body provided.', 140) }}</td>
                        <td>
                            <span class="admin-badge admin-badge-{{ $inquiry->status }}">{{ str_replace('_', ' ', $inquiry->status) }}</span>
                        </td>
                        <td>{{ optional($inquiry->created_at)->format('M d, Y H:i') }}</td>
                        <td>
                            <div class="admin-stack">
                                <form method="POST" action="{{ route('admin.inquiries.update', $inquiry) }}" class="admin-inline-actions">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="admin-select" style="min-width: 150px;">
                                        @foreach (['new' => 'New', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'archived' => 'Archived'] as $value => $label)
                                            <option value="{{ $value }}" @selected($inquiry->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="admin-btn admin-btn-secondary">Save</button>
                                </form>

                                <form method="POST" action="{{ route('admin.inquiries.destroy', $inquiry) }}" onsubmit="return confirm('Delete this inquiry?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-btn admin-btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="admin-empty">No inquiries matched your filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top: 1rem;">
            {{ $inquiries->links() }}
        </div>
    </section>
@endsection
