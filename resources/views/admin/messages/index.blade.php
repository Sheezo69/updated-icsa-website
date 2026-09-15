@extends('admin.layout')

@section('title', 'Messages')
@section('subtitle', 'Private conversations between administrators and staff.')
@section('title_icon', 'far fa-comments')

@section('content')
    <div class="message-workspace {{ request()->filled('conversation') ? 'has-explicit-conversation' : '' }}" data-message-workspace>
        <aside class="message-inbox">
            <div class="message-inbox-header">
                <div>
                    <span class="message-eyebrow">Internal inbox</span>
                    <h2>Conversations</h2>
                </div>
                <button type="button" class="message-compose-button" data-open-compose aria-label="Start a new conversation">
                    <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                </button>
            </div>

            <form class="message-search" method="GET" action="{{ route('admin.messages.index') }}">
                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                <input name="search" value="{{ $search }}" placeholder="Search conversations..." aria-label="Search conversations">
            </form>

            <div class="message-conversation-list">
                @forelse ($conversations as $conversation)
                    @php
                        $partnerId = $conversation->otherParticipantId($currentAdmin->id);
                        $partner = $contacts->firstWhere('id', $partnerId);
                        $partnerName = $partner?->username ?? $conversation->otherParticipantName($currentAdmin->id);
                        $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($partnerName, 0, 1));
                    @endphp
                    <a href="{{ route('admin.messages.index', ['conversation' => $conversation->id]) }}"
                       class="message-conversation {{ $selectedConversation?->id === $conversation->id ? 'is-active' : '' }}">
                        <span class="message-avatar message-avatar-{{ $partnerId % 6 }}">{{ $initial ?: '?' }}<i></i></span>
                        <span class="message-conversation-copy">
                            <span class="message-conversation-top">
                                <strong>{{ $partnerName }}</strong>
                                <time>{{ optional($conversation->last_message_at)->isToday() ? optional($conversation->last_message_at)->format('h:i A') : optional($conversation->last_message_at)->format('M d') }}</time>
                            </span>
                            <span class="message-conversation-bottom">
                                <span>{{ \Illuminate\Support\Str::limit($conversation->latestMessage?->body ?? 'No messages yet', 42) }}</span>
                                @if ($conversation->unread_count > 0)<b>{{ $conversation->unread_count }}</b>@endif
                            </span>
                        </span>
                    </a>
                @empty
                    <div class="message-list-empty">
                        <i class="far fa-message" aria-hidden="true"></i>
                        <strong>{{ $search ? 'No matches found' : 'Your inbox is clear' }}</strong>
                        <span>{{ $search ? 'Try another name.' : 'Start a conversation with your team.' }}</span>
                    </div>
                @endforelse
            </div>
        </aside>

        <section class="message-thread">
            @if ($selectedConversation)
                @php
                    $partnerName = $otherParticipant?->username ?? $selectedConversation->otherParticipantName($currentAdmin->id);
                    $partnerInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($partnerName, 0, 1));
                    $lastMessageId = (int) ($messages->last()?->id ?? 0);
                @endphp
                <header class="message-thread-header">
                    <a href="{{ route('admin.messages.index') }}" class="message-mobile-back" aria-label="Back to conversations"><i class="fas fa-arrow-left"></i></a>
                    <span class="message-avatar message-avatar-large message-avatar-{{ $selectedConversation->otherParticipantId($currentAdmin->id) % 6 }}">{{ $partnerInitial ?: '?' }}<i></i></span>
                    <div>
                        <h2>{{ $partnerName }}</h2>
                        <span>{{ $otherParticipant ? ucfirst($otherParticipant->role).' account' : 'Deleted account' }}</span>
                    </div>
                    <span class="message-private-pill"><i class="fas fa-lock" aria-hidden="true"></i> Private</span>
                </header>

                <div class="message-thread-body" data-message-thread data-last-message="{{ $lastMessageId }}" data-poll-url="{{ route('admin.messages.poll', $selectedConversation) }}">
                    <div class="message-date-divider"><span>Conversation</span></div>
                    @foreach ($messages as $message)
                        <article class="message-bubble-row {{ $message->sender_id === $currentAdmin->id ? 'is-mine' : 'is-theirs' }}" data-message-id="{{ $message->id }}">
                            <div class="message-bubble">
                                <p>{{ $message->body }}</p>
                                <footer>
                                    <time datetime="{{ optional($message->created_at)->toIso8601String() }}">{{ optional($message->created_at)->format('h:i A') }}</time>
                                    @if ($message->sender_id === $currentAdmin->id)
                                        <i class="fas fa-check-double {{ $message->read_at ? 'is-read' : '' }}" data-read-receipt aria-label="{{ $message->read_at ? 'Read' : 'Sent' }}"></i>
                                    @endif
                                </footer>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($otherParticipant)
                    <form method="POST" action="{{ route('admin.messages.send') }}" class="message-composer" data-message-form>
                        @csrf
                        <input type="hidden" name="conversation_id" value="{{ $selectedConversation->id }}">
                        <label>
                            <span class="sr-only">Message {{ $partnerName }}</span>
                            <textarea name="body" rows="1" maxlength="2000" placeholder="Write a message..." required data-message-input>{{ old('body') }}</textarea>
                        </label>
                        <span class="message-character-count" data-character-count>0/2000</span>
                        <button type="submit" aria-label="Send message"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
                    </form>
                @else
                    <div class="message-read-only"><i class="fas fa-circle-info"></i> This account was deleted. The conversation is read-only.</div>
                @endif
            @else
                <div class="message-welcome">
                    <span class="message-welcome-icon"><i class="far fa-comments" aria-hidden="true"></i></span>
                    <span class="message-eyebrow">ICSA team chat</span>
                    <h2>Keep work moving together.</h2>
                    <p>Start a private conversation with {{ $currentAdmin->isOwner() ? 'an administrator or staff member' : 'an administrator' }}.</p>
                    <button type="button" class="admin-btn admin-btn-primary" data-open-compose><i class="fas fa-plus"></i> New Message</button>
                </div>
            @endif
        </section>
    </div>

    <dialog class="message-compose-modal" data-compose-modal>
        <form method="POST" action="{{ route('admin.messages.send') }}">
            @csrf
            <header>
                <div><span class="message-eyebrow">Private message</span><h2>New conversation</h2></div>
                <button type="button" data-close-compose aria-label="Close"><i class="fas fa-xmark"></i></button>
            </header>
            <label class="message-modal-field">
                <span>Send to</span>
                <select name="recipient_id" required>
                    <option value="">Choose a team member</option>
                    @foreach ($contacts as $contact)
                        <option value="{{ $contact->id }}">{{ $contact->username }} · {{ ucfirst($contact->role) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="message-modal-field">
                <span>Message</span>
                <textarea name="body" rows="5" maxlength="2000" placeholder="What would you like to say?" required></textarea>
            </label>
            <footer>
                <button type="button" class="admin-btn admin-btn-secondary" data-close-compose>Cancel</button>
                <button type="submit" class="admin-btn admin-btn-primary"><i class="fas fa-paper-plane"></i> Send Message</button>
            </footer>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
(() => {
    const modal = document.querySelector('[data-compose-modal]');
    document.querySelectorAll('[data-open-compose]').forEach(button => button.addEventListener('click', () => modal?.showModal()));
    document.querySelectorAll('[data-close-compose]').forEach(button => button.addEventListener('click', () => modal?.close()));
    modal?.addEventListener('click', event => { if (event.target === modal) modal.close(); });

    const input = document.querySelector('[data-message-input]');
    const count = document.querySelector('[data-character-count]');
    const resizeInput = () => {
        if (!input) return;
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 120)}px`;
        if (count) count.textContent = `${input.value.length}/2000`;
    };
    input?.addEventListener('input', resizeInput);
    input?.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            if (input.value.trim()) input.form.requestSubmit();
        }
    });
    resizeInput();

    const thread = document.querySelector('[data-message-thread]');
    if (!thread) return;
    thread.scrollTop = thread.scrollHeight;

    const appendMessage = message => {
        if (thread.querySelector(`[data-message-id="${message.id}"]`)) return;
        const row = document.createElement('article');
        row.className = `message-bubble-row ${message.mine ? 'is-mine' : 'is-theirs'}`;
        row.dataset.messageId = message.id;
        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        const body = document.createElement('p');
        body.textContent = message.body;
        const footer = document.createElement('footer');
        const time = document.createElement('time');
        time.dateTime = message.datetime || '';
        time.textContent = message.time;
        footer.append(time);
        if (message.mine) {
            const receipt = document.createElement('i');
            receipt.className = `fas fa-check-double ${message.read ? 'is-read' : ''}`;
            receipt.dataset.readReceipt = '';
            footer.append(receipt);
        }
        bubble.append(body, footer);
        row.append(bubble);
        thread.append(row);
    };

    const poll = async () => {
        if (document.hidden) return;
        try {
            const after = Number(thread.dataset.lastMessage || 0);
            const response = await fetch(`${thread.dataset.pollUrl}?after=${after}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) return;
            const data = await response.json();
            data.messages.forEach(message => {
                appendMessage(message);
                thread.dataset.lastMessage = Math.max(Number(thread.dataset.lastMessage || 0), Number(message.id));
            });
            if (data.messages.length) thread.scrollTo({ top: thread.scrollHeight, behavior: 'smooth' });
            if (data.read_through) {
                thread.querySelectorAll('[data-message-id]').forEach(row => {
                    if (Number(row.dataset.messageId) <= data.read_through) row.querySelector('[data-read-receipt]')?.classList.add('is-read');
                });
            }
            window.updateAdminUnread?.(data.unread_count);
        } catch (_) {}
    };
    window.setInterval(poll, 5000);
})();
</script>
@endpush
