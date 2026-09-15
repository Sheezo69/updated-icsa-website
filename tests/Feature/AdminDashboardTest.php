<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ContactMessage;
use App\Support\CourseFileRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_real_inquiry_and_course_data(): void
    {
        $admin = Admin::create([
            'username' => 'tester',
            'password_hash' => bcrypt('test-password'),
            'role' => 'admin',
        ]);

        ContactMessage::create([
            'name' => 'Dashboard Student',
            'email' => 'student@example.com',
            'phone' => '+965 5555 1234',
            'course_interest' => 'Advanced Excel',
            'message' => 'Course details please',
            'status' => ContactMessage::STATUS_NEW,
        ]);

        $this->mock(CourseFileRepository::class)
            ->shouldReceive('count')
            ->once()
            ->andReturn(40);

        $this->withSession(['admin_id' => $admin->id])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Inquiries Overview')
            ->assertSee('Inquiries by Course')
            ->assertSee('Recent Inquiries')
            ->assertSee('Dashboard Student')
            ->assertSee('Advanced Excel')
            ->assertSee('Course Pages')
            ->assertSee('40');
    }

    public function test_dashboard_inquiry_chart_period_can_be_changed(): void
    {
        $admin = Admin::create([
            'username' => 'period_tester',
            'password_hash' => bcrypt('test-password'),
            'role' => 'admin',
        ]);

        $this->mock(CourseFileRepository::class)
            ->shouldReceive('count')
            ->once()
            ->andReturn(0);

        $this->withSession(['admin_id' => $admin->id])
            ->get(route('admin.dashboard', ['period' => 30]))
            ->assertOk()
            ->assertSee('Total inquiries over the last 30 days')
            ->assertSee('<option value="30" selected>Last 30 Days</option>', false)
            ->assertSee('--chart-columns: 10', false);
    }
}
