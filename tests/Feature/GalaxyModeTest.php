<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminActivityLog;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GalaxyModeTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $username, string $role = Admin::ROLE_ADMIN): Admin
    {
        return Admin::query()->create([
            'username' => $username,
            'email' => $username.'@example.com',
            'password_hash' => Hash::make('Password!123'),
            'role' => $role,
        ]);
    }

    private function inquiry(array $attributes = []): ContactMessage
    {
        return ContactMessage::query()->create(array_merge([
            'name' => 'Galaxy Lead',
            'email' => 'galaxy@example.com',
            'phone' => '+96550000000',
            'course_interest' => 'Advanced Excel',
            'message' => 'Please share the next available schedule.',
            'status' => ContactMessage::STATUS_NEW,
            'lead_score' => 81,
        ], $attributes));
    }

    public function test_galaxy_mode_is_owner_only_and_renders_real_signals(): void
    {
        $owner = $this->account('owner');
        $staff = $this->account('icsastaff', Admin::ROLE_STAFF);
        $this->inquiry(['assigned_to' => $staff->id]);

        $this->withSession(['admin_id' => $owner->id])
            ->get(route('admin.galaxy.index'))
            ->assertOk()
            ->assertSee('Galaxy Command Deck')
            ->assertSee('Galaxy Lead')
            ->assertSee('Drag an inquiry comet');

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.galaxy.index'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_snapshot_contains_course_systems_staff_and_inquiries(): void
    {
        $owner = $this->account('owner');
        $this->account('icsastaff', Admin::ROLE_STAFF);
        $this->inquiry();

        $this->withSession(['admin_id' => $owner->id])
            ->getJson(route('admin.galaxy.snapshot'))
            ->assertOk()
            ->assertJsonCount(6, 'categories')
            ->assertJsonPath('inquiries.0.name', 'Galaxy Lead')
            ->assertJsonPath('staff.0.name', 'icsastaff')
            ->assertJsonStructure(['version', 'generated_at', 'categories', 'inquiries', 'staff', 'stars', 'stats']);
    }

    public function test_comet_can_be_dragged_to_staff_ship_and_is_audited(): void
    {
        $owner = $this->account('owner');
        $staff = $this->account('icsastaff', Admin::ROLE_STAFF);
        $inquiry = $this->inquiry();

        $this->withSession(['admin_id' => $owner->id])
            ->patchJson(route('admin.galaxy.act', $inquiry), ['action' => 'assign_staff', 'staff_id' => $staff->id])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame($staff->id, $inquiry->fresh()->assigned_to);
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'galaxy.assign_staff', 'subject_id' => (string) $inquiry->id]);
    }

    public function test_comet_can_be_dragged_to_course_planet_and_qualified(): void
    {
        $owner = $this->account('owner');
        $inquiry = $this->inquiry(['course_interest' => 'General inquiry']);

        $this->withSession(['admin_id' => $owner->id])
            ->patchJson(route('admin.galaxy.act', $inquiry), ['action' => 'qualify_course', 'course_slug' => 'advanced-excel'])
            ->assertOk();

        $inquiry->refresh();
        $this->assertSame('Advanced Excel', $inquiry->course_interest);
        $this->assertSame('qualified', $inquiry->pipeline_stage);
        $this->assertSame(ContactMessage::STATUS_IN_PROGRESS, $inquiry->status);
        $log = AdminActivityLog::query()->sole();
        $this->assertSame('galaxy.qualify_course', $log->action);
        $this->assertSame('Advanced Excel', $log->after_values['course']);
    }
}
