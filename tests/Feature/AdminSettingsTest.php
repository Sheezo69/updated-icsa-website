<?php

namespace Tests\Feature;

use App\Mail\AdminInquiryNotification;
use App\Models\Admin;
use App\Models\ContactMessage;
use App\Support\InquiryMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function account(array $attributes = []): Admin
    {
        return Admin::query()->create(array_merge([
            'username' => 'owner',
            'email' => 'owner@example.com',
            'password_hash' => Hash::make('OldPassword!1'),
            'role' => Admin::ROLE_ADMIN,
        ], $attributes));
    }

    public function test_settings_renders_only_the_selected_section(): void
    {
        $admin = $this->account();

        $this->withSession(['admin_id' => $admin->id])
            ->get(route('admin.settings.edit', ['tab' => 'profile']))
            ->assertOk()
            ->assertSee('Update your personal information')
            ->assertDontSee('Password requirements')
            ->assertDontSee('Choose which account alerts');

        $this->withSession(['admin_id' => $admin->id])
            ->get(route('admin.settings.edit', ['tab' => 'security']))
            ->assertOk()
            ->assertSee('Password requirements')
            ->assertDontSee('Change Photo');

        $this->withSession(['admin_id' => $admin->id])
            ->get(route('admin.settings.edit', ['tab' => 'notifications']))
            ->assertOk()
            ->assertSee('New Inquiry Alerts')
            ->assertDontSee('Current Password');
    }

    public function test_profile_information_and_photo_can_be_updated(): void
    {
        $admin = $this->account();
        $storedPath = null;

        try {
            $this->withSession(['admin_id' => $admin->id])
                ->put(route('admin.settings.profile'), [
                    'username' => 'new_owner',
                    'email' => 'new-owner@example.com',
                    'avatar' => UploadedFile::fake()->image('avatar.png', 300, 300),
                ])
                ->assertRedirect(route('admin.settings.edit', ['tab' => 'profile']))
                ->assertSessionHas('success');

            $admin->refresh();
            $storedPath = $admin->avatar_path;
            $this->assertSame('new_owner', $admin->username);
            $this->assertSame('new-owner@example.com', $admin->email);
            $this->assertNotNull($storedPath);
            $this->assertFileExists(public_path($storedPath));
        } finally {
            if ($storedPath) {
                File::delete(public_path($storedPath));
            }
        }
    }

    public function test_password_notifications_and_system_preferences_are_saved(): void
    {
        $admin = $this->account();

        $this->withSession(['admin_id' => $admin->id])
            ->put(route('admin.settings.password'), [
                'current_password' => 'OldPassword!1',
                'new_password' => 'NewPassword!2',
                'new_password_confirmation' => 'NewPassword!2',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'security']));
        $this->assertTrue(Hash::check('NewPassword!2', $admin->fresh()->password_hash));

        $this->withSession(['admin_id' => $admin->id])
            ->put(route('admin.settings.notifications'), ['notify_messages' => '1'])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'notifications']));
        $admin->refresh();
        $this->assertFalse($admin->notify_email);
        $this->assertFalse($admin->notify_inquiries);
        $this->assertTrue($admin->notify_messages);

        $this->withSession(['admin_id' => $admin->id])
            ->put(route('admin.settings.preferences'), ['timezone' => 'Asia/Dubai', 'language' => 'en'])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'system']));
        $this->assertSame('Asia/Dubai', $admin->fresh()->timezone);
    }

    public function test_enabled_accounts_receive_inquiry_email_copies(): void
    {
        Mail::fake();
        config(['mail.admin_notification_email' => 'icsaq8@gmail.com']);
        $this->account([
            'email' => 'alerts@example.com',
            'notify_email' => true,
            'notify_inquiries' => true,
        ]);
        $inquiry = ContactMessage::query()->create([
            'name' => 'Visitor',
            'email' => 'visitor@example.com',
            'phone' => '50953314',
            'message' => 'Please contact me.',
            'status' => ContactMessage::STATUS_NEW,
        ]);

        app(InquiryMailer::class)->send($inquiry, 'admin');

        Mail::assertSent(AdminInquiryNotification::class, function (AdminInquiryNotification $mail): bool {
            return $mail->hasTo('icsaq8@gmail.com') && $mail->hasBcc('alerts@example.com');
        });
    }
}
