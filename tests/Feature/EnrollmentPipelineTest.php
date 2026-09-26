<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminActivityLog;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnrollmentPipelineTest extends TestCase
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
            'name' => 'Pipeline Student',
            'email' => 'pipeline@example.com',
            'phone' => '+96550000000',
            'course_interest' => 'Advanced Excel',
            'message' => 'I would like to enroll in the next available class.',
            'status' => ContactMessage::STATUS_NEW,
        ], $attributes));
    }

    public function test_admin_and_staff_can_open_the_live_pipeline(): void
    {
        $owner = $this->account('owner');
        $staff = $this->account('icsastaff', Admin::ROLE_STAFF);
        $this->inquiry(['assigned_to' => $staff->id]);

        foreach ([$owner, $staff] as $account) {
            $this->withSession(['admin_id' => $account->id])
                ->get(route('admin.inquiries.pipeline'))
                ->assertOk()
                ->assertSee('Turn every inquiry into')
                ->assertSee('New Leads')
                ->assertSee('Receptionist workload')
                ->assertSee('Pipeline Student');
        }
    }

    public function test_drag_move_persists_stage_syncs_status_and_is_audited(): void
    {
        $owner = $this->account('owner');
        $inquiry = $this->inquiry();

        $response = $this->withSession(['admin_id' => $owner->id])
            ->patchJson(route('admin.inquiries.pipeline.move', $inquiry), ['stage' => 'qualified']);

        $response->assertOk()->assertJson(['ok' => true]);
        $inquiry->refresh();
        $this->assertSame('qualified', $inquiry->pipeline_stage);
        $this->assertSame(ContactMessage::STATUS_IN_PROGRESS, $inquiry->status);
        $this->assertNotNull($inquiry->pipeline_moved_at);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'inquiry.pipeline_moved',
            'subject_id' => (string) $inquiry->id,
        ]);
        $this->assertSame('qualified', AdminActivityLog::query()->sole()->after_values['pipeline_stage']);
    }

    public function test_pipeline_snapshot_returns_fresh_board_and_workload_markup(): void
    {
        $owner = $this->account('owner');
        $staff = $this->account('icsastaff', Admin::ROLE_STAFF);
        $this->inquiry(['assigned_to' => $staff->id, 'pipeline_stage' => 'contacted']);

        $this->withSession(['admin_id' => $owner->id])
            ->getJson(route('admin.inquiries.pipeline.snapshot'))
            ->assertOk()
            ->assertJsonStructure(['version', 'board_html', 'workload_html'])
            ->assertJsonPath('version', fn ($value) => is_string($value) && strlen($value) === 40)
            ->assertSee('Pipeline Student')
            ->assertSee('icsastaff');
    }

    public function test_invalid_pipeline_stage_is_rejected(): void
    {
        $owner = $this->account('owner');
        $inquiry = $this->inquiry();

        $this->withSession(['admin_id' => $owner->id])
            ->patchJson(route('admin.inquiries.pipeline.move', $inquiry), ['stage' => 'deleted'])
            ->assertUnprocessable();

        $this->assertSame('new_lead', $inquiry->fresh()->pipeline_stage);
    }
}
