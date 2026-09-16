@extends('admin.layout')

@section('title', 'Inquiries')
@section('subtitle', 'Review and manage all inquiries submitted from the public site.')
@section('title_icon', 'far fa-envelope')

@section('content')
    @php
        $statuses = [
            'new' => 'New',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'archived' => 'Archived',
        ];
    @endphp

    <div class="inquiry-page" data-inquiry-workspace>
        <div class="inquiry-page-main">
            <section class="inquiry-stat-grid" aria-label="Inquiry statistics">
                <article class="inquiry-stat-card inquiry-stat-card-total">
                    <span class="inquiry-stat-icon"><i class="far fa-envelope" aria-hidden="true"></i></span>
                    <div><span>Total Inquiries</span><strong>{{ $stats['total'] }}</strong><small><i class="fas fa-tag" aria-hidden="true"></i> All inquiries</small></div>
                </article>
                <article class="inquiry-stat-card inquiry-stat-card-new">
                    <span class="inquiry-stat-icon"><i class="far fa-dot-circle" aria-hidden="true"></i></span>
                    <div><span><i class="fas fa-circle" aria-hidden="true"></i> New</span><strong>{{ $stats['new'] }}</strong><small><i class="fas fa-diamond" aria-hidden="true"></i> Needs attention</small></div>
                </article>
                <article class="inquiry-stat-card inquiry-stat-card-progress">
                    <span class="inquiry-stat-icon"><i class="far fa-clock" aria-hidden="true"></i></span>
                    <div><span>In Progress</span><strong>{{ $stats['in_progress'] }}</strong><small><i class="fas fa-diamond" aria-hidden="true"></i> Being handled</small></div>
                </article>
                <article class="inquiry-stat-card inquiry-stat-card-resolved">
                    <span class="inquiry-stat-icon"><i class="far fa-circle-check" aria-hidden="true"></i></span>
                    <div><span>Resolved</span><strong>{{ $stats['resolved'] }}</strong><small><i class="fas fa-diamond" aria-hidden="true"></i> Completed</small></div>
                </article>
            </section>

            <nav class="inquiry-assignment-tabs" aria-label="Inquiry assignment views">
                <a href="{{ route('admin.inquiries.index', request()->except(['assignment', 'page'])) }}" @class(['is-active' => empty($filters['assignment'])])>
                    <i class="fas fa-inbox" aria-hidden="true"></i>
                    All Inquiries
                    <span>{{ $stats['total'] }}</span>
                </a>
                <a href="{{ route('admin.inquiries.index', array_merge(request()->except(['assignment', 'page']), ['assignment' => 'mine'])) }}" @class(['is-active' => ($filters['assignment'] ?? '') === 'mine'])>
                    <i class="fas fa-user-check" aria-hidden="true"></i>
                    Assigned to Me
                    <span>{{ $stats['assigned_to_me'] }}</span>
                </a>
                <a href="{{ route('admin.inquiries.index', array_merge(request()->except(['assignment', 'page']), ['assignment' => 'unassigned'])) }}" @class(['is-active' => ($filters['assignment'] ?? '') === 'unassigned'])>
                    <i class="fas fa-user-clock" aria-hidden="true"></i>
                    Unassigned
                    <span>{{ $stats['unassigned'] }}</span>
                </a>
            </nav>

            <section class="inquiry-filter-card">
                <form method="GET" action="{{ route('admin.inquiries.index') }}" class="inquiry-filter-form">
                    @if (!empty($filters['assignment']))
                        <input type="hidden" name="assignment" value="{{ $filters['assignment'] }}">
                    @endif
                    <label class="inquiry-search-field">
                        <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                        <span class="sr-only">Search inquiries</span>
                        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, email, phone, or message...">
                    </label>

                    <label class="inquiry-filter-field">
                        <span>Status</span>
                        <select name="status">
                            <option value="">All</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="inquiry-filter-field">
                        <span>Course</span>
                        <select name="course">
                            <option value="">All</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course }}" @selected(($filters['course'] ?? '') === $course)>{{ $course }}</option>
                            @endforeach
                        </select>
                    </label>

                    <button type="button" class="inquiry-filter-toggle" data-filter-toggle aria-expanded="{{ ($filters['date_from'] ?? false) || ($filters['date_to'] ?? false) ? 'true' : 'false' }}">
                        <i class="fas fa-sliders" aria-hidden="true"></i> More Filters
                    </button>

                    <div class="inquiry-date-filters" data-date-filters @if (!($filters['date_from'] ?? false) && !($filters['date_to'] ?? false)) hidden @endif>
                        <label class="inquiry-filter-field">
                            <span>From</span>
                            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                        </label>
                        <label class="inquiry-filter-field">
                            <span>To</span>
                            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                        </label>
                    </div>

                    <div class="inquiry-filter-actions">
                        <button type="submit" class="admin-btn admin-btn-primary"><i class="fas fa-filter" aria-hidden="true"></i> Apply</button>
                        <a href="{{ route('admin.inquiries.index', array_filter(['assignment' => $filters['assignment'] ?? null])) }}" class="inquiry-clear-link"><i class="fas fa-rotate-left" aria-hidden="true"></i> Clear Filters</a>
                        <a href="{{ route('admin.inquiries.export', request()->query()) }}" class="admin-btn admin-btn-secondary"><i class="fas fa-download" aria-hidden="true"></i> Export CSV</a>
                    </div>
                </form>
            </section>

            <form id="inquiry-bulk-form" method="POST" action="{{ route('admin.inquiries.bulk') }}">
                @csrf
            </form>

            <section class="inquiry-list-card">
                <div class="inquiry-bulk-bar">
                    <label class="inquiry-check inquiry-select-summary">
                        <input type="checkbox" data-select-all aria-label="Select all inquiries on this page">
                        <span><strong data-selected-count>0</strong> selected</span>
                    </label>

                    <div class="inquiry-bulk-controls">
                        <label class="inquiry-compact-select">
                            <span class="sr-only">Bulk status</span>
                            <select name="bulk_status" form="inquiry-bulk-form" data-requires-selection disabled>
                                <option value="">Change Status</option>
                                @foreach ($statuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <button class="admin-btn admin-btn-secondary" type="submit" name="action" value="bulk_status" form="inquiry-bulk-form" data-requires-selection disabled>Apply</button>

                        <label class="inquiry-compact-select">
                            <span class="sr-only">Assign inquiries</span>
                            <select name="assigned_admin" form="inquiry-bulk-form" data-requires-selection disabled>
                                <option value="">Assign</option>
                                @foreach ($admins as $admin)<option value="{{ $admin->id }}">{{ $admin->username }}</option>@endforeach
                            </select>
                        </label>
                        <button class="admin-btn admin-btn-secondary" type="submit" name="action" value="bulk_assign" form="inquiry-bulk-form" data-requires-selection disabled>Assign</button>

                        <button type="button" class="admin-btn admin-btn-secondary" data-export-selected data-export-url="{{ route('admin.inquiries.export') }}" data-requires-selection disabled><i class="fas fa-download" aria-hidden="true"></i> Export</button>
                        <button class="admin-delete-button" type="submit" name="action" value="bulk_delete" form="inquiry-bulk-form" data-requires-selection disabled onclick="return confirm('Delete all selected inquiries?');"><span class="text">Delete</span><span class="icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path></svg></span></button>
                    </div>
                </div>

                <div class="inquiry-table-scroll">
                    <table class="inquiry-table">
                        <thead>
                            <tr>
                                <th><span class="sr-only">Select</span></th>
                                <th>Contact</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Received</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($inquiries as $inquiry)
                                @php
                                    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($inquiry->name), 0, 1));
                                @endphp
                                <tr>
                                    <td>
                                        <label class="inquiry-check">
                                            <input type="checkbox" name="ids[]" value="{{ $inquiry->id }}" form="inquiry-bulk-form" data-inquiry-checkbox aria-label="Select inquiry #{{ $inquiry->id }}">
                                        </label>
                                    </td>
                                    <td>
                                        <div class="inquiry-contact-cell">
                                            <span class="inquiry-avatar inquiry-avatar-{{ $inquiry->id % 6 }}">{{ $initial ?: '?' }}</span>
                                            <div>
                                                <strong>{{ $inquiry->name }}</strong>
                                                <a href="mailto:{{ $inquiry->email }}"><i class="far fa-envelope" aria-hidden="true"></i> {{ $inquiry->email }}</a>
                                                <span><i class="fas fa-phone" aria-hidden="true"></i> {{ $inquiry->phone ?: 'No phone' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ $inquiry->course_interest ?: 'General Inquiry' }}</strong>
                                        <span class="inquiry-cell-subtitle">{{ $inquiry->form_type ?: ($inquiry->subject ?: 'Website inquiry') }}</span>
                                        <span class="inquiry-assignee {{ $inquiry->assignedTo ? 'is-assigned' : '' }}">
                                            <i class="fas {{ $inquiry->assignedTo ? 'fa-user-check' : 'fa-user-clock' }}" aria-hidden="true"></i>
                                            {{ $inquiry->assignedTo ? 'Assigned to '.$inquiry->assignedTo->username : 'Unassigned' }}
                                        </span>
                                    </td>
                                    <td><span class="inquiry-status inquiry-status-{{ $inquiry->status }}"><i class="fas fa-circle" aria-hidden="true"></i> {{ $statuses[$inquiry->status] ?? ucfirst($inquiry->status) }}</span></td>
                                    <td>
                                        <time datetime="{{ optional($inquiry->created_at)->toIso8601String() }}">{{ optional($inquiry->created_at)->format('M d, Y') }}<span>{{ optional($inquiry->created_at)->format('h:i A') }}</span></time>
                                    </td>
                                    <td>
                                        <div class="inquiry-row-actions">
                                            <button type="button" class="inquiry-view-button" data-open-inquiry="{{ $inquiry->id }}"><i class="far fa-eye" aria-hidden="true"></i> View</button>
                                            <button type="button" class="inquiry-more-button" data-open-inquiry="{{ $inquiry->id }}" aria-label="More actions for inquiry #{{ $inquiry->id }}"><i class="fas fa-ellipsis-vertical" aria-hidden="true"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="admin-empty">No inquiries matched your filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <footer class="inquiry-list-footer">
                    <span>Showing {{ $inquiries->firstItem() ?? 0 }}–{{ $inquiries->lastItem() ?? 0 }} of {{ $inquiries->total() }} inquiries</span>
                    {{ $inquiries->links() }}
                </footer>
            </section>
        </div>

        @foreach ($inquiries as $inquiry)
            @php
                $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($inquiry->name), 0, 1));
                $phoneDigits = preg_replace('/\D+/', '', $inquiry->phone ?? '');
                $phoneDigits = str_starts_with($phoneDigits, '00') ? substr($phoneDigits, 2) : $phoneDigits;
                $phoneDigits = strlen($phoneDigits) === 8 ? '965'.$phoneDigits : $phoneDigits;
                $whatsappText = rawurlencode('Hello '.$inquiry->name.', I am contacting you regarding your inquiry with ICSA.');
                $visitorAttempt = $inquiry->emailAttempts->firstWhere('kind', 'visitor');
                $adminAttempt = $inquiry->emailAttempts->firstWhere('kind', 'admin');
            @endphp
            <aside class="inquiry-detail-panel" data-inquiry-panel="{{ $inquiry->id }}" aria-label="Inquiry #{{ $inquiry->id }} details" hidden>
                <header class="inquiry-detail-nav">
                    <button type="button" data-close-inquiry><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Inquiries</button>
                    <button type="button" data-close-inquiry aria-label="Close inquiry details"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                </header>

                <div class="inquiry-detail-body">
                    <div class="inquiry-detail-heading">
                        <h2>Inquiry #{{ $inquiry->id }}</h2>
                        <span class="inquiry-status inquiry-status-{{ $inquiry->status }}"><i class="fas fa-circle" aria-hidden="true"></i> {{ $statuses[$inquiry->status] ?? ucfirst($inquiry->status) }}</span>
                        <time>{{ optional($inquiry->created_at)->format('M d, Y · h:i A') }}</time>
                    </div>

                    <section class="inquiry-contact-card">
                        <span class="inquiry-avatar inquiry-avatar-large inquiry-avatar-{{ $inquiry->id % 6 }}">{{ $initial ?: '?' }}</span>
                        <div>
                            <h3>{{ $inquiry->name }}</h3>
                            <a href="mailto:{{ $inquiry->email }}"><i class="far fa-envelope" aria-hidden="true"></i> {{ $inquiry->email }}</a>
                            <span><i class="fas fa-phone" aria-hidden="true"></i> {{ $inquiry->phone ?: 'No phone provided' }}</span>
                            <small class="inquiry-detail-assignee">
                                <i class="fas {{ $inquiry->assignedTo ? 'fa-user-check' : 'fa-user-clock' }}" aria-hidden="true"></i>
                                {{ $inquiry->assignedTo ? 'Assigned to '.$inquiry->assignedTo->username : 'Unassigned' }}
                            </small>
                        </div>
                    </section>

                    <section class="inquiry-detail-card inquiry-course-card">
                        <span class="inquiry-detail-card-icon"><i class="fas fa-graduation-cap" aria-hidden="true"></i></span>
                        <div><small>Course</small><strong>{{ $inquiry->course_interest ?: 'General Inquiry' }}</strong></div>
                        <span class="inquiry-type-pill">{{ $inquiry->form_type ?: 'Inquiry' }}</span>
                    </section>

                    <section class="inquiry-detail-card inquiry-message-card">
                        <div class="inquiry-detail-label"><i class="far fa-message" aria-hidden="true"></i> Message</div>
                        <p>{{ $inquiry->message ?: 'No message body provided.' }}</p>
                    </section>

                    <section class="inquiry-detail-section">
                        <div class="inquiry-detail-label"><i class="far fa-circle-check" aria-hidden="true"></i> Status</div>
                        <form method="POST" action="{{ route('admin.inquiries.update', $inquiry) }}">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="inquiry-detail-status" aria-label="Change inquiry status" onchange="this.form.submit()">
                                @foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected($inquiry->status === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </form>
                    </section>

                    <section class="inquiry-detail-section">
                        <h3 class="inquiry-detail-section-title">Inquiry Timeline</h3>
                        <ol class="inquiry-timeline">
                            <li class="is-primary"><span></span><div><strong>Inquiry received</strong><time>{{ optional($inquiry->created_at)->format('M d, Y · h:i A') }}</time></div></li>
                            @if ($visitorAttempt)
                                <li class="{{ $visitorAttempt->status === 'sent' ? 'is-success' : 'is-danger' }}"><span></span><div><strong>Visitor email {{ $visitorAttempt->status }}</strong><time>{{ optional($visitorAttempt->created_at)->format('M d, Y · h:i A') }}</time></div></li>
                            @endif
                            @if ($adminAttempt)
                                <li class="{{ $adminAttempt->status === 'sent' ? 'is-success' : 'is-danger' }}"><span></span><div><strong>Admin email {{ $adminAttempt->status }}</strong><time>{{ optional($adminAttempt->created_at)->format('M d, Y · h:i A') }}</time></div></li>
                            @endif
                            <li data-viewed-event hidden><span></span><div><strong>Viewed in admin</strong><time>Just now</time></div></li>
                        </ol>
                    </section>

                    <section class="inquiry-detail-section">
                        <h3 class="inquiry-detail-section-title">Inquiry Actions</h3>
                        <div class="inquiry-detail-actions">
                            <a class="admin-btn admin-btn-primary" href="mailto:{{ $inquiry->email }}?subject={{ rawurlencode('Your ICSA inquiry #'.$inquiry->id) }}"><i class="far fa-paper-plane" aria-hidden="true"></i> Send Email</a>
                            @if ($phoneDigits)
                                <a class="admin-btn admin-btn-secondary" href="https://wa.me/{{ $phoneDigits }}?text={{ $whatsappText }}" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp" aria-hidden="true"></i> Message</a>
                            @else
                                <button type="button" class="admin-btn admin-btn-secondary" disabled><i class="fab fa-whatsapp" aria-hidden="true"></i> Message</button>
                            @endif
                            @if ($currentAdmin->isOwner())
                                @if ($inquiry->assignedTo?->role === \App\Models\Admin::ROLE_STAFF)
                                    <form method="POST" action="{{ route('admin.inquiries.message-assignee', $inquiry) }}">
                                        @csrf
                                        <button type="submit" class="admin-btn admin-btn-secondary"><i class="fas fa-comments" aria-hidden="true"></i> Message Assigned Staff</button>
                                    </form>
                                @else
                                    <button type="button" class="admin-btn admin-btn-secondary" disabled title="Assign this inquiry to a staff member first"><i class="fas fa-comments" aria-hidden="true"></i> Message Assigned Staff</button>
                                @endif
                            @endif
                            <button type="button" class="admin-btn admin-btn-secondary" data-copy-contact data-contact="{{ $inquiry->name }}&#10;{{ $inquiry->email }}&#10;{{ $inquiry->phone }}"><i class="far fa-copy" aria-hidden="true"></i> Copy Contact</button>
                            <form method="POST" action="{{ route('admin.inquiries.destroy', $inquiry) }}" onsubmit="return confirm('Delete inquiry #{{ $inquiry->id }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-delete-button"><span class="text">Delete</span><span class="icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path></svg></span></button>
                            </form>
                        </div>
                    </section>

                    <section class="inquiry-detail-section inquiry-email-activity">
                        <h3 class="inquiry-detail-section-title">Email Activity</h3>
                        @foreach (['visitor' => ['Visitor email', $visitorAttempt], 'admin' => ['Admin email', $adminAttempt]] as $kind => [$label, $attempt])
                            @php
                                $activityStatus = $attempt?->status ?? 'untracked';
                            @endphp
                            <div class="inquiry-email-row">
                                <span class="inquiry-email-result inquiry-email-result-{{ $activityStatus }}"><i class="fas fa-{{ $activityStatus === 'sent' ? 'check' : ($activityStatus === 'failed' ? 'xmark' : 'minus') }}" aria-hidden="true"></i></span>
                                <strong>{{ $label }}</strong>
                                <span>{{ $attempt ? $inquiry->emailAttempts->where('kind', $kind)->count().' attempt(s)' : 'Not tracked' }}</span>
                            </div>
                        @endforeach
                        @include('admin.inquiries.email-tracking', ['inquiry' => $inquiry])
                    </section>
                </div>
            </aside>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const workspace = document.querySelector('[data-inquiry-workspace]');
            const panels = [...document.querySelectorAll('[data-inquiry-panel]')];
            const rowCheckboxes = [...document.querySelectorAll('[data-inquiry-checkbox]')];
            const selectAll = document.querySelector('[data-select-all]');
            const selectedCount = document.querySelector('[data-selected-count]');
            const bulkControls = [...document.querySelectorAll('[data-requires-selection]')];

            const refreshSelection = () => {
                const checked = rowCheckboxes.filter((checkbox) => checkbox.checked).length;
                selectedCount.textContent = checked;
                bulkControls.forEach((control) => control.disabled = checked === 0);
                if (selectAll) {
                    selectAll.checked = checked > 0 && checked === rowCheckboxes.length;
                    selectAll.indeterminate = checked > 0 && checked < rowCheckboxes.length;
                }
            };

            const closePanels = () => {
                panels.forEach((panel) => panel.hidden = true);
                workspace?.classList.remove('has-open-panel');
            };

            document.addEventListener('click', async (event) => {
                const opener = event.target.closest('[data-open-inquiry]');
                if (opener) {
                    closePanels();
                    const panel = document.querySelector(`[data-inquiry-panel="${opener.dataset.openInquiry}"]`);
                    if (panel) {
                        panel.hidden = false;
                        panel.querySelector('[data-viewed-event]')?.removeAttribute('hidden');
                        workspace?.classList.add('has-open-panel');
                        panel.scrollTop = 0;
                    }
                    return;
                }

                if (event.target.closest('[data-close-inquiry]')) {
                    closePanels();
                    return;
                }

                const copyButton = event.target.closest('[data-copy-contact]');
                if (copyButton) {
                    try {
                        await navigator.clipboard.writeText(copyButton.dataset.contact);
                        const previous = copyButton.innerHTML;
                        copyButton.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Copied';
                        setTimeout(() => copyButton.innerHTML = previous, 1600);
                    } catch (_) {
                        window.prompt('Copy contact details:', copyButton.dataset.contact);
                    }
                }
            });

            document.querySelector('[data-filter-toggle]')?.addEventListener('click', (event) => {
                const filters = document.querySelector('[data-date-filters]');
                filters.hidden = !filters.hidden;
                event.currentTarget.setAttribute('aria-expanded', String(!filters.hidden));
            });

            selectAll?.addEventListener('change', () => {
                rowCheckboxes.forEach((checkbox) => checkbox.checked = selectAll.checked);
                refreshSelection();
            });
            rowCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', refreshSelection));

            document.querySelector('[data-export-selected]')?.addEventListener('click', (event) => {
                const ids = rowCheckboxes.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);
                if (!ids.length) return;
                const url = new URL(event.currentTarget.dataset.exportUrl, window.location.origin);
                ids.forEach((id) => url.searchParams.append('ids[]', id));
                window.location.assign(url.toString());
            });

            document.getElementById('inquiry-bulk-form')?.addEventListener('submit', (event) => {
                const submitter = event.submitter;
                if (submitter?.value === 'bulk_status' && !document.querySelector('[name="bulk_status"]').value) {
                    event.preventDefault();
                    alert('Choose a status first.');
                }
                if (submitter?.value === 'bulk_assign' && !document.querySelector('[name="assigned_admin"]').value) {
                    event.preventDefault();
                    alert('Choose an admin first.');
                }
            });

            refreshSelection();
            const requestedInquiry = new URLSearchParams(window.location.search).get('open');
            if (requestedInquiry) document.querySelector(`[data-open-inquiry="${CSS.escape(requestedInquiry)}"]`)?.click();
        })();
    </script>
@endpush
