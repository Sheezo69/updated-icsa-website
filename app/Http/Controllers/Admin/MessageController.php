<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminConversation;
use App\Models\AdminMessage;
use App\Models\ContactMessage;
use App\Support\AdminActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Admin $currentAdmin */
        $currentAdmin = $request->attributes->get('currentAdmin');
        $search = trim($request->string('search')->toString());
        $box = $request->string('box')->toString() === 'archived' ? 'archived' : 'active';
        $archiveColumn = 'participant_one_archived_at';
        $otherArchiveColumn = 'participant_two_archived_at';

        $conversations = AdminConversation::query()
            ->forAdmin($currentAdmin->id)
            ->where(function (Builder $query) use ($currentAdmin, $box, $archiveColumn, $otherArchiveColumn): void {
                $operator = $box === 'archived' ? 'whereNotNull' : 'whereNull';
                $query->where(function (Builder $one) use ($currentAdmin, $archiveColumn, $operator): void {
                    $one->where('participant_one_id', $currentAdmin->id)->{$operator}($archiveColumn);
                })->orWhere(function (Builder $two) use ($currentAdmin, $otherArchiveColumn, $operator): void {
                    $two->where('participant_two_id', $currentAdmin->id)->{$operator}($otherArchiveColumn);
                });
            })
            ->with('latestMessage')
            ->withCount(['messages as unread_count' => function (Builder $query) use ($currentAdmin): void {
                $query->whereNull('read_at')->where('sender_id', '!=', $currentAdmin->id);
            }])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder->where('participant_one_name', 'like', '%'.$search.'%')
                        ->orWhere('participant_two_name', 'like', '%'.$search.'%');
                });
            })
            ->orderByRaw('CASE WHEN participant_one_id = ? THEN participant_one_pinned_at ELSE participant_two_pinned_at END IS NULL', [$currentAdmin->id])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        $selectedConversation = null;
        if ($request->filled('conversation')) {
            $selectedConversation = AdminConversation::query()
                ->forAdmin($currentAdmin->id)
                ->findOrFail($request->integer('conversation'));
        } elseif ($conversations->isNotEmpty()) {
            $selectedConversation = $conversations->first();
        }

        $messages = collect();
        $otherParticipant = null;
        if ($selectedConversation) {
            $this->markRead($selectedConversation, $currentAdmin);
            $selectedConversationInList = $conversations->firstWhere('id', $selectedConversation->id);
            if ($selectedConversationInList) {
                $selectedConversationInList->unread_count = 0;
            }
            $messages = $selectedConversation->messages()->orderBy('id')->get();
            $otherParticipant = Admin::query()->find($selectedConversation->otherParticipantId($currentAdmin->id));
        }

        $adminUnreadMessages = $this->unreadCount($currentAdmin);

        $contacts = Admin::query()
            ->whereKeyNot($currentAdmin->id)
            ->when(! $currentAdmin->isOwner(), fn (Builder $query) => $query->where('role', Admin::ROLE_ADMIN))
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('username')
            ->get(['id', 'username', 'role']);

        return view('admin.messages.index', compact(
            'conversations',
            'selectedConversation',
            'messages',
            'otherParticipant',
            'contacts',
            'search',
            'box',
            'adminUnreadMessages',
        ));
    }

    public function send(Request $request): RedirectResponse
    {
        /** @var Admin $currentAdmin */
        $currentAdmin = $request->attributes->get('currentAdmin');
        $data = $request->validate([
            'conversation_id' => ['nullable', 'required_without:recipient_id', 'integer'],
            'recipient_id' => ['nullable', 'required_without:conversation_id', 'integer', 'exists:admins,id'],
            'body' => ['nullable', 'required_without:attachment', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,application/pdf'],
        ]);

        $body = trim((string) ($data['body'] ?? ''));
        if ($body === '' && ! $request->hasFile('attachment')) {
            throw ValidationException::withMessages(['body' => 'Write a message before sending.']);
        }

        if (! empty($data['conversation_id'])) {
            $conversation = AdminConversation::query()
                ->forAdmin($currentAdmin->id)
                ->findOrFail($data['conversation_id']);
            $recipient = Admin::query()->find($conversation->otherParticipantId($currentAdmin->id));
            if (! $recipient) {
                return back()->with('error', 'This account no longer exists, so the conversation is read-only.');
            }
            $this->authorizeRecipient($currentAdmin, $recipient);
        } else {
            $recipient = Admin::query()->findOrFail($data['recipient_id'] ?? 0);
            $this->authorizeRecipient($currentAdmin, $recipient);
            $conversation = $this->conversationBetween($currentAdmin, $recipient);
        }

        $attachment = [];
        if ($file = $request->file('attachment')) {
            $path = $file->store('admin-message-attachments/'.$conversation->id, 'local');
            if (! is_string($path) || $path === '') {
                throw ValidationException::withMessages(['attachment' => 'The attachment could not be saved. Please try again.']);
            }
            $attachment = [
                'attachment_path' => $path,
                'attachment_name' => Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 255, ''),
                'attachment_mime' => $file->getMimeType(),
                'attachment_size' => $file->getSize(),
            ];
        }

        try {
            DB::transaction(function () use ($conversation, $currentAdmin, $body, $attachment, $recipient): void {
                $message = $conversation->messages()->create([
                    'sender_id' => $currentAdmin->id,
                    'sender_name' => $currentAdmin->username,
                    'body' => $body,
                    ...$attachment,
                ]);

                $conversation->update([
                    'last_message_at' => $message->created_at,
                    $conversation->preferenceColumn($currentAdmin->id, 'archived_at') => null,
                    $conversation->preferenceColumn($recipient->id, 'archived_at') => null,
                ]);
            });
        } catch (\Throwable $exception) {
            if (isset($attachment['attachment_path'])) {
                Storage::disk('local')->delete($attachment['attachment_path']);
            }
            throw $exception;
        }

        return redirect()->route('admin.messages.index', ['conversation' => $conversation->id]);
    }

    public function poll(Request $request, AdminConversation $conversation): JsonResponse
    {
        /** @var Admin $currentAdmin */
        $currentAdmin = $request->attributes->get('currentAdmin');
        abort_unless($conversation->includes($currentAdmin->id), 403);

        $this->markRead($conversation, $currentAdmin);
        $messages = $conversation->messages()
            ->where('id', '>', max(0, $request->integer('after')))
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->map(fn (AdminMessage $message) => $this->messagePayload($message, $currentAdmin));

        return response()->json([
            'messages' => $messages,
            'unread_count' => $this->unreadCount($currentAdmin),
            'read_through' => (int) $conversation->messages()
                ->where('sender_id', $currentAdmin->id)
                ->whereNotNull('read_at')
                ->max('id'),
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        /** @var Admin $currentAdmin */
        $currentAdmin = $request->attributes->get('currentAdmin');

        if (! Schema::hasTable('admin_messages') || ! Schema::hasTable('admin_conversations') || $currentAdmin->notify_messages === false) {
            return response()->json(['unread_count' => 0, 'latest_unread' => null]);
        }

        $latest = AdminMessage::query()->whereNull('read_at')
            ->where('sender_id', '!=', $currentAdmin->id)
            ->whereHas('conversation', fn (Builder $query) => $query->forAdmin($currentAdmin->id))
            ->latest('id')->first();

        return response()->json([
            'unread_count' => $this->unreadCount($currentAdmin),
            'latest_unread' => $latest ? [
                'id' => $latest->id,
                'conversation_id' => $latest->conversation_id,
                'sender_name' => $latest->sender_name,
            ] : null,
        ]);
    }

    public function attachment(Request $request, AdminMessage $message): BinaryFileResponse
    {
        /** @var Admin $currentAdmin */
        $currentAdmin = $request->attributes->get('currentAdmin');
        abort_unless($message->conversation?->includes($currentAdmin->id), 403);
        abort_unless($message->attachment_path && Storage::disk('local')->exists($message->attachment_path), 404);

        return response()->download(
            Storage::disk('local')->path($message->attachment_path),
            $message->attachment_name ?: 'attachment',
            ['Content-Type' => $message->attachment_mime ?: 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff'],
        );
    }

    public function archive(Request $request, AdminConversation $conversation, AdminActivityLogger $audit): RedirectResponse
    {
        /** @var Admin $currentAdmin */
        $currentAdmin = $request->attributes->get('currentAdmin');
        abort_unless($conversation->includes($currentAdmin->id), 403);
        $before = $conversation->isArchivedFor($currentAdmin->id);
        $archived = ! $before;
        $conversation->update([$conversation->preferenceColumn($currentAdmin->id, 'archived_at') => $archived ? now() : null]);
        $audit->record($request, $archived ? 'conversation.archived' : 'conversation.restored', 'messages', $archived ? 'Archived a conversation.' : 'Restored a conversation.', AdminConversation::class, $conversation->id, $conversation->otherParticipantName($currentAdmin->id), ['archived' => $before], ['archived' => $archived]);

        return redirect()->route('admin.messages.index', $archived ? [] : ['conversation' => $conversation->id]);
    }

    public function pin(Request $request, AdminConversation $conversation, AdminActivityLogger $audit): RedirectResponse
    {
        /** @var Admin $currentAdmin */
        $currentAdmin = $request->attributes->get('currentAdmin');
        abort_unless($conversation->includes($currentAdmin->id), 403);
        $before = $conversation->isPinnedFor($currentAdmin->id);
        $pinned = ! $before;
        $conversation->update([$conversation->preferenceColumn($currentAdmin->id, 'pinned_at') => $pinned ? now() : null]);
        $audit->record($request, $pinned ? 'conversation.pinned' : 'conversation.unpinned', 'messages', $pinned ? 'Pinned a conversation.' : 'Unpinned a conversation.', AdminConversation::class, $conversation->id, $conversation->otherParticipantName($currentAdmin->id), ['pinned' => $before], ['pinned' => $pinned]);

        return redirect()->route('admin.messages.index', ['conversation' => $conversation->id, ...($request->string('box')->toString() === 'archived' ? ['box' => 'archived'] : [])]);
    }

    public function messageAssignee(Request $request, ContactMessage $inquiry, AdminActivityLogger $audit): RedirectResponse
    {
        /** @var Admin $currentAdmin */
        $currentAdmin = $request->attributes->get('currentAdmin');
        $recipient = $inquiry->assigned_to ? Admin::query()->find($inquiry->assigned_to) : null;
        if (! $recipient || $recipient->role !== Admin::ROLE_STAFF) {
            return back()->with('error', 'Assign this inquiry to a staff member first.');
        }

        $conversation = $this->conversationBetween($currentAdmin, $recipient);
        DB::transaction(function () use ($conversation, $currentAdmin, $recipient, $inquiry): void {
            $message = $conversation->messages()->create([
                'sender_id' => $currentAdmin->id,
                'sender_name' => $currentAdmin->username,
                'body' => 'Please review inquiry #'.$inquiry->id.'. '.route('admin.inquiries.index', ['open' => $inquiry->id]),
            ]);
            $conversation->update([
                'last_message_at' => $message->created_at,
                $conversation->preferenceColumn($currentAdmin->id, 'archived_at') => null,
                $conversation->preferenceColumn($recipient->id, 'archived_at') => null,
            ]);
        });
        $audit->record($request, 'inquiry.staff_messaged', 'inquiries', 'Messaged the assigned staff member about an inquiry.', ContactMessage::class, $inquiry->id, '#'.$inquiry->id.' '.$inquiry->name, null, ['assigned_staff' => $recipient->username]);

        return redirect()->route('admin.messages.index', ['conversation' => $conversation->id]);
    }

    private function conversationBetween(Admin $sender, Admin $recipient): AdminConversation
    {
        $first = $sender->id < $recipient->id ? $sender : $recipient;
        $second = $sender->id < $recipient->id ? $recipient : $sender;

        return AdminConversation::query()->firstOrCreate(
            ['participant_one_id' => $first->id, 'participant_two_id' => $second->id],
            ['participant_one_name' => $first->username, 'participant_two_name' => $second->username],
        );
    }

    private function authorizeRecipient(Admin $sender, Admin $recipient): void
    {
        abort_if($sender->id === $recipient->id, 422);
        abort_if(! $sender->isOwner() && ! $recipient->isOwner(), 403);
    }

    private function markRead(AdminConversation $conversation, Admin $reader): void
    {
        $conversation->messages()
            ->where('sender_id', '!=', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function unreadCount(Admin $admin): int
    {
        if ($admin->notify_messages === false) {
            return 0;
        }

        return AdminMessage::query()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $admin->id)
            ->whereHas('conversation', fn (Builder $query) => $query->forAdmin($admin->id))
            ->count();
    }

    private function messagePayload(AdminMessage $message, Admin $viewer): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'sender_name' => $message->sender_name,
            'mine' => $message->sender_id === $viewer->id,
            'read' => $message->read_at !== null,
            'time' => optional($message->created_at)->format('h:i A'),
            'datetime' => optional($message->created_at)->toIso8601String(),
            'attachment' => $message->attachment_path ? [
                'name' => $message->attachment_name,
                'mime' => $message->attachment_mime,
                'size' => $message->attachment_size,
                'url' => route('admin.messages.attachment', $message),
            ] : null,
        ];
    }
}
