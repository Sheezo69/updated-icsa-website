<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminActivityLog;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $username, string $role): Admin
    {
        return Admin::query()->create([
            'username' => $username,
            'email' => $username.'@example.com',
            'password_hash' => Hash::make('Password!123'),
            'role' => $role,
        ]);
    }

    public function test_activity_log_page_and_export_routes_are_not_exposed(): void
    {
        $this->assertFalse(Route::has('admin.activity.index'));
        $this->assertFalse(Route::has('admin.activity.export'));
    }

    public function test_inquiry_changes_store_actor_source_and_before_after_values(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $inquiry = ContactMessage::query()->create([
            'name' => 'Kuwait Visitor',
            'email' => 'visitor@example.com',
            'phone' => '+96550000000',
            'course_interest' => 'advanced-excel',
            'status' => ContactMessage::STATUS_NEW,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.15'])
            ->withHeader('User-Agent', 'Mozilla/5.0 Chrome/120 Windows')
            ->withSession(['admin_id' => $owner->id])
            ->patch(route('admin.inquiries.update', $inquiry), ['status' => ContactMessage::STATUS_RESOLVED])
            ->assertSessionHas('success');

        $log = AdminActivityLog::query()->sole();
        $this->assertSame('owner', $log->actor_name);
        $this->assertSame('inquiry.status_changed', $log->action);
        $this->assertSame('new', $log->before_values['status']);
        $this->assertSame('resolved', $log->after_values['status']);
        $this->assertSame('203.0.113.15', $log->ip_address);
        $this->assertStringContainsString('Chrome', $log->deviceLabel());
    }

    public function test_password_values_are_never_saved_in_background_audit_records(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $staff = $this->account('staff', Admin::ROLE_STAFF);

        $this->withSession(['admin_id' => $owner->id])
            ->put(route('admin.users.password', $staff), ['new_password' => 'NeverStoreMe!456'])
            ->assertSessionHas('success');

        $stored = AdminActivityLog::query()->where('action', 'user.password_reset')->sole();
        $this->assertNull($stored->before_values);
        $this->assertNull($stored->after_values);
        $this->assertStringNotContainsString('NeverStoreMe', json_encode($stored->toArray()));

    }

    public function test_successful_login_and_logout_are_recorded(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);

        $this->post(route('admin.login.submit'), ['username' => 'owner', 'password' => 'Password!123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('admin_activity_logs', ['admin_id' => $owner->id, 'action' => 'authentication.login']);
        $this->assertDatabaseHas('admin_activity_logs', ['admin_id' => $owner->id, 'action' => 'authentication.logout']);
    }
}
