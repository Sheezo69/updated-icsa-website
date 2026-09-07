<details class="email-tracking">
    <summary><i class="fas fa-paper-plane" aria-hidden="true"></i> Email tracking <span>{{ $inquiry->emailAttempts->count() }} attempts</span></summary>
    <div class="email-tracking-content">
        <p class="admin-muted">Sent means accepted by the configured mail transport, not confirmed inbox delivery. Times shown in {{ config('app.timezone') }}.</p>
        <div class="email-tracking-grid">
            @foreach (['visitor' => 'Visitor confirmation', 'admin' => 'Admin notification'] as $kind => $label)
                @php
                    $attempts = $inquiry->emailAttempts->where('kind', $kind);
                    $latest = $attempts->first();
                    $status = $latest?->status ?? 'untracked';
                    $nextRecipient = $kind === 'visitor' ? $inquiry->email : config('mail.admin_notification_email');
                @endphp
                <section class="email-tracking-card">
                    <div class="email-tracking-heading">
                        <h3>{{ $label }}</h3>
                        <span class="email-pill email-pill-{{ $status }}">{{ $status === 'untracked' ? 'Not tracked' : ucfirst($status) }}</span>
                    </div>
                    <dl>
                        <dt>Last recipient</dt><dd>{{ $latest?->recipient ?: 'No recorded recipient' }}</dd>
                        <dt>Last attempt</dt><dd>{{ $latest?->created_at?->format('M d, Y H:i:s') ?? 'No recorded attempts' }}</dd>
                        <dt>Sent at</dt><dd>{{ $latest?->sent_at?->format('M d, Y H:i:s') ?? '—' }}</dd>
                        @if ($latest)<dt>Transport</dt><dd>{{ $latest->mailer }}</dd>@endif
                    </dl>
                    @if ($latest?->error)<p class="email-tracking-error" role="status">{{ $latest->error }}</p>@endif
                    @if ($status === 'untracked')<p class="admin-muted">Previous sends were not recorded. This does not mean the email failed.</p>@endif
                    @if ($status === 'pending')<p class="admin-muted">No outcome recorded yet. If this persists, check the server log before resending.</p>@endif
                    @if (in_array($latest?->mailer, ['log', 'array']))<p class="email-tracking-error">Test transport: this attempt did not deliver an external email.</p>@endif
                    <form method="POST" action="{{ route('admin.inquiries.email.resend', $inquiry) }}" onsubmit="if (!confirm('Send another {{ strtolower($label) }} email?')) return false; this.querySelector('button').disabled = true;">
                        @csrf
                        <input type="hidden" name="kind" value="{{ $kind }}">
                        <p class="admin-muted">Send to: <strong>{{ $nextRecipient ?: 'Recipient not configured' }}</strong></p>
                        <button class="admin-btn admin-btn-secondary" type="submit" @disabled(!$nextRecipient)><i class="fas fa-rotate-right" aria-hidden="true"></i> Resend {{ $kind }} email</button>
                    </form>
                    @if ($attempts->isNotEmpty())
                        <details class="email-attempt-history">
                            <summary>Attempt history ({{ $attempts->count() }})</summary>
                            <ol>
                                @foreach ($attempts as $attempt)
                                    <li>
                                        <span class="email-pill email-pill-{{ $attempt->status }}">{{ ucfirst($attempt->status) }}</span>
                                        <time>{{ $attempt->created_at->format('M d, Y H:i:s') }}</time>
                                        <span>{{ $attempt->recipient ?: 'Missing recipient' }}</span>
                                        @if ($attempt->error)<span class="email-tracking-error">{{ $attempt->error }}</span>@endif
                                    </li>
                                @endforeach
                            </ol>
                        </details>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</details>
