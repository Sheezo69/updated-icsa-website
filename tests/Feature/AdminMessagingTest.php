<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminConversation;
use App\Models\AdminMessage;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMessagingTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $username, string $role): Admin
    {
        return Admin::query()->create([
            'username' => $username,
            'password_hash' => bcrypt('test-password'),
            'role' => $role,
        ]);
    }

    public function test_messaging_requires_an_authenticated_admin(): void
    {
        $this->get(route('admin.messages.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.messages.send'), ['body' => 'Hello'])->assertRedirect(route('admin.login'));
    }

    public function test_admin_and_staff_can_message_and_read_each_other(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $staff = $this->account('staff', Admin::ROLE_STAFF);

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.messages.send'), [
                'recipient_id' => $staff->id,
                'body' => 'Please handle the new inquiry.',
            ])
            ->assertRedirect();

        $conversation = AdminConversation::query()->firstOrFail();
        $message = AdminMessage::query()->firstOrFail();
        $this->assertSame($owner->id, $message->sender_id);
        $this->assertNull($message->read_at);

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-message-unread', false)
            ->assertSee('>1</span>', false);

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.messages.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Please handle the new inquiry.')
            ->assertSee('owner')
            ->assertSee('Private');

        $this->assertNotNull($message->fresh()->read_at);

        $this->withSession(['admin_id' => $staff->id])
            ->post(route('admin.messages.send'), [
                'conversation_id' => $conversation->id,
                'body' => 'On it.',
            ])
            ->assertRedirect(route('admin.messages.index', ['conversation' => $conversation->id]));

        $this->assertDatabaseHas('admin_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $staff->id,
            'body' => 'On it.',
        ]);
    }

    public function test_staff_cannot_message_staff_or_access_another_conversation(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $staffOne = $this->account('staff_one', Admin::ROLE_STAFF);
        $staffTwo = $this->account('staff_two', Admin::ROLE_STAFF);

        $this->withSession(['admin_id' => $staffOne->id])
            ->post(route('admin.messages.send'), ['recipient_id' => $staffTwo->id, 'body' => 'Not allowed'])
            ->assertForbidden();

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.messages.send'), ['recipient_id' => $staffTwo->id, 'body' => 'Private'])
            ->assertRedirect();

        $conversation = AdminConversation::query()->firstOrFail();
        $this->withSession(['admin_id' => $staffOne->id])
            ->get(route('admin.messages.poll', $conversation))
            ->assertForbidden();

        $this->withSession(['admin_id' => $staffOne->id])
            ->get(route('admin.messages.index', ['conversation' => $conversation->id]))
            ->assertNotFound();
    }

    public function test_message_output_is_escaped_and_limits_are_validated(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $staff = $this->account('staff', Admin::ROLE_STAFF);

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.messages.send'), ['recipient_id' => $staff->id, 'body' => '<script>alert(1)</script>'])
            ->assertRedirect();

        $conversation = AdminConversation::query()->firstOrFail();
        $this->withSession(['admin_id' => $owner->id])
            ->get(route('admin.messages.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.messages.send'), ['recipient_id' => $staff->id, 'body' => str_repeat('x', 2001)])
            ->assertSessionHasErrors('body');
    }

    public function test_valid_attachments_are_private_and_invalid_files_are_rejected(): void
    {
        Storage::fake('local');
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $staff = $this->account('staff', Admin::ROLE_STAFF);
        $outsider = $this->account('outsider', Admin::ROLE_STAFF);

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.messages.send'), [
                'recipient_id' => $staff->id,
                'attachment' => UploadedFile::fake()->image('classroom.png'),
            ])->assertRedirect();

        $message = AdminMessage::query()->firstOrFail();
        $this->assertSame('', $message->body);
        $this->assertSame('classroom.png', $message->attachment_name);
        Storage::disk('local')->assertExists($message->attachment_path);

        $this->withSession(['admin_id' => $owner->id])
            ->get(route('admin.messages.index', ['conversation' => $message->conversation_id]))
            ->assertOk()->assertSee('classroom.png');

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.messages.send'), [
                'conversation_id' => $message->conversation_id,
                'attachment' => UploadedFile::fake()->create('guide.pdf', 100, 'application/pdf'),
            ])->assertRedirect();
        $this->assertDatabaseHas('admin_messages', ['attachment_name' => 'guide.pdf', 'attachment_mime' => 'application/pdf']);

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.messages.attachment', $message))
            ->assertOk();

        $this->withSession(['admin_id' => $outsider->id])
            ->get(route('admin.messages.attachment', $message))
            ->assertForbidden();

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.messages.send'), [
                'recipient_id' => $staff->id,
                'attachment' => UploadedFile::fake()->create('evil.svg', 50, 'image/svg+xml'),
            ])->assertSessionHasErrors('attachment');

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.messages.send'), [
                'recipient_id' => $staff->id,
                'attachment' => UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf'),
            ])->assertSessionHasErrors('attachment');

        $this->assertSame(2, AdminMessage::query()->count());
    }

    public function test_archive_and_pin_are_personal_and_new_messages_restore_archived_chat(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $staff = $this->account('staff', Admin::ROLE_STAFF);

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.messages.send'), ['recipient_id' => $staff->id, 'body' => 'First message'])
            ->assertRedirect();
        $conversation = AdminConversation::query()->firstOrFail();

        $this->withSession(['admin_id' => $owner->id])
            ->patch(route('admin.messages.pin', $conversation))
            ->assertRedirect();
        $this->assertTrue($conversation->fresh()->isPinnedFor($owner->id));
        $this->assertFalse($conversation->fresh()->isPinnedFor($staff->id));

        $this->withSession(['admin_id' => $staff->id])
            ->patch(route('admin.messages.archive', $conversation))
            ->assertRedirect();
        $this->assertTrue($conversation->fresh()->isArchivedFor($staff->id));
        $this->assertFalse($conversation->fresh()->isArchivedFor($owner->id));

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.messages.index', ['box' => 'archived']))
            ->assertOk()->assertSee('First message');

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.messages.send'), ['conversation_id' => $conversation->id, 'body' => 'New work'])
            ->assertRedirect();
        $this->assertFalse($conversation->fresh()->isArchivedFor($staff->id));
    }

    public function test_owner_can_message_assigned_staff_directly_from_an_inquiry(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $staff = $this->account('staff', Admin::ROLE_STAFF);
        $inquiry = ContactMessage::query()->create([
            'name' => 'Visitor', 'email' => 'visitor@example.com', 'phone' => '50000000',
            'status' => 'new', 'assigned_to' => $staff->id,
        ]);

        $this->withSession(['admin_id' => $staff->id])
            ->post(route('admin.inquiries.message-assignee', $inquiry))
            ->assertRedirect(route('admin.dashboard'));

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.inquiries.message-assignee', $inquiry))
            ->assertRedirect();

        $message = AdminMessage::query()->firstOrFail();
        $this->assertStringContainsString('inquiry #'.$inquiry->id, $message->body);
        $this->assertStringContainsString('open='.$inquiry->id, $message->body);

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.messages.unread'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('latest_unread.sender_name', 'owner')
            ->assertDontSee('Please review inquiry #');
    }
}
