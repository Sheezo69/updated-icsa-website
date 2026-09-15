<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminConversation;
use App\Models\AdminMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Admin $currentAdmin */
        $currentAdmin = $request->attributes->get('currentAdmin');
        $search = trim($request->string('search')->toString());

        $conversations = AdminConversation::query()
            ->forAdmin($currentAdmin->id)
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
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $body = trim($data['body']);
        if ($body === '') {
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

        DB::transaction(function () use ($conversation, $currentAdmin, $body): void {
            $message = $conversation->messages()->create([
                'sender_id' => $currentAdmin->id,
                'sender_name' => $currentAdmin->username,
                'body' => $body,
            ]);

            $conversation->update(['last_message_at' => $message->created_at]);
        });

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

        return response()->json(['unread_count' => Schema::hasTable('admin_messages') ? $this->unreadCount($currentAdmin) : 0]);
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
        ];
    }
}
