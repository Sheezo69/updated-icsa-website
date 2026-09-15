<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminConversation;
use App\Models\AdminMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
