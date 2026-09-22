<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminActivityLog;
use App\Models\ContactMessage;
use App\Models\WebsitePageView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MissionControlTest extends TestCase
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

    private function inquiry(array $attributes = []): ContactMessage
    {
        return ContactMessage::query()->create(array_merge([
            'name' => 'High Intent Visitor',
            'email' => 'visitor@example.com',
            'phone' => '+96550000000',
            'course_interest' => 'advanced-excel',
            'message' => 'I am ready to enroll and would like the next available schedule.',
            'status' => ContactMessage::STATUS_NEW,
            'form_type' => 'Course Enrollment',
        ], $attributes));
    }

    public function test_mission_control_is_owner_only_and_renders_live_intelligence(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $staff = $this->account('staff', Admin::ROLE_STAFF);
        $hash = hash('sha256', 'visitor-token');
        $inquiry = $this->inquiry(['analytics_visitor_hash' => $hash]);

        foreach (['/', '/courses/advanced-excel', '/courses/advanced-excel', '/contact'] as $index => $path) {
            WebsitePageView::query()->create([
                'visitor_hash' => $hash,
                'page_path' => $path,
                'page_type' => str_starts_with($path, '/courses/') ? 'course' : 'home',
                'utm_source' => $index === 0 ? 'instagram' : null,
                'utm_campaign' => $index === 0 ? 'excel-intake' : null,
                'device_type' => 'Mobile',
                'browser' => 'Chrome',
                'operating_system' => 'Android',
                'country_code' => 'KW',
                'visited_at' => now()->subMinutes(5 - $index),
            ]);
        }

        $this->withSession(['admin_id' => $owner->id])
            ->get(route('admin.mission-control.index', ['command' => 'show hot leads']))
            ->assertOk()
            ->assertSee('ICSA')
            ->assertSee('Mission Control')
            ->assertSee('High Intent Visitor')
            ->assertSee('Hot leads')
            ->assertSee('excel-intake')
            ->assertSee('No raw IP data');

        $this->withSession(['admin_id' => $owner->id])
            ->get(route('admin.mission-control.snapshot'))
            ->assertOk()
            ->assertJsonPath('stats.live_visitors', 1)
            ->assertJsonPath('stats.hot_leads', 1);

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.mission-control.index'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(0, (int) $inquiry->fresh()->lead_score, 'Read-only dashboard must not mutate lead records.');
    }

    public function test_admin_can_refresh_scores_and_balance_workload_with_an_audit_trail(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $staffOne = $this->account('staff_one', Admin::ROLE_STAFF);
        $staffTwo = $this->account('staff_two', Admin::ROLE_STAFF);
        $this->inquiry(['name' => 'Already Assigned', 'assigned_to' => $staffOne->id]);
        foreach (range(1, 5) as $number) {
            $this->inquiry(['name' => 'Lead '.$number, 'email' => 'lead'.$number.'@example.com', 'phone' => '+9655'.str_pad((string) $number, 7, '0', STR_PAD_LEFT)]);
        }

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.mission-control.automate'), ['action' => 'recalculate_scores'])
            ->assertSessionHas('success');
        $this->assertGreaterThan(0, ContactMessage::query()->min('lead_score'));
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'mission.leads_rescored']);

        $this->withSession(['admin_id' => $owner->id])
            ->post(route('admin.mission-control.automate'), ['action' => 'balance_workload'])
            ->assertSessionHas('success');

        $this->assertSame(0, ContactMessage::query()->whereNull('assigned_to')->count());
        $loads = [
            ContactMessage::query()->where('assigned_to', $staffOne->id)->count(),
            ContactMessage::query()->where('assigned_to', $staffTwo->id)->count(),
        ];
        $this->assertLessThanOrEqual(1, max($loads) - min($loads));
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'mission.workload_balanced']);
        $this->assertSame(2, AdminActivityLog::query()->where('section', 'mission_control')->count());
    }

    public function test_public_inquiry_is_privately_linked_to_its_anonymous_journey(): void
    {
        Mail::fake();
        $token = str_repeat('c', 64);
        $hash = hash('sha256', $token);
        WebsitePageView::query()->create([
            'visitor_hash' => $hash,
            'page_path' => '/courses/advanced-excel',
            'page_type' => 'course',
            'utm_campaign' => 'excel-launch',
            'device_type' => 'Mobile',
            'browser' => 'Chrome',
            'operating_system' => 'Android',
            'visited_at' => now('UTC')->subMinute(),
        ]);

        $this->withCookie('_icsa_vid', $token)->post(route('api.inquiry'), [
            'name' => 'Journey Lead',
            'email' => 'journey@example.com',
            'phone' => '50000000',
            'course' => 'advanced-excel',
            'message' => 'I want to register for the next available class immediately.',
        ])->assertOk();

        $inquiry = ContactMessage::query()->where('email', 'journey@example.com')->sole();
        $this->assertSame($hash, $inquiry->analytics_visitor_hash);
        $this->assertGreaterThanOrEqual(75, $inquiry->lead_score);
        $this->assertFalse(Schema::hasColumn('contact_messages', 'ip_address'));
    }

    public function test_mission_control_remains_available_without_analytics_storage(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $this->inquiry();
        Schema::drop('website_page_views');

        $this->withSession(['admin_id' => $owner->id])
            ->get(route('admin.mission-control.index'))
            ->assertOk()
            ->assertSee('Traffic intelligence is awaiting analytics storage')
            ->assertSee('High Intent Visitor');
    }
}
