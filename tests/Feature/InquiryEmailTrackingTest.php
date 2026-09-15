<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ContactMessage;
use App\Support\InquiryMailer;
use App\Support\CourseFileRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InquiryEmailTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array', 'mail.admin_notification_email' => 'admin@example.com']);
    }

    private function inquiry(): ContactMessage
    {
        return ContactMessage::create([
            'name' => 'Test Visitor', 'email' => 'visitor@example.com', 'phone' => '50953314',
            'message' => 'Test inquiry', 'status' => 'new', 'form_type' => 'Contact Form',
        ]);
    }

    public function test_both_forms_record_separate_emails_and_preserve_reply_to(): void
    {
        $this->mock(CourseFileRepository::class)->shouldReceive('all')->andReturn([['slug' => 'python']]);
        foreach (['api.contact' => 'Contact Form', 'api.inquiry' => 'Course Enrollment'] as $route => $type) {
            $this->postJson(route($route), [
                'name' => 'Test Visitor', 'email' => 'visitor@example.com', 'phone' => '50953314',
                'course' => 'python', 'message' => 'Please contact me',
            ])->assertOk()->assertJson(['success' => true]);
            $inquiry = ContactMessage::latest('id')->first();
            $this->assertSame($type, $inquiry->form_type);
            $this->assertCount(2, $inquiry->emailAttempts);
            $this->assertSame(['sent'], $inquiry->emailAttempts->pluck('status')->unique()->values()->all());
            $this->assertNotNull($inquiry->emailAttempts->first()->sent_at);
        }
        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(4, $messages);
        $adminMail = $messages->last()->getOriginalMessage();
        $this->assertSame('admin@example.com', $adminMail->getTo()[0]->getAddress());
        $this->assertSame('visitor@example.com', $adminMail->getReplyTo()[0]->getAddress());
    }

    public function test_failure_is_recorded_and_the_other_email_is_still_attempted(): void
    {
        config(['mail.admin_notification_email' => null]);
        $inquiry = $this->inquiry();
        app(InquiryMailer::class)->sendBoth($inquiry);
        $this->assertSame('sent', $inquiry->emailAttempts()->where('kind', 'visitor')->first()->status);
        $failure = $inquiry->emailAttempts()->where('kind', 'admin')->first();
        $this->assertSame('failed', $failure->status);
        $this->assertNotEmpty($failure->error);
        $this->assertNull($failure->sent_at);
    }

    public function test_transport_failure_is_recorded_without_rejecting_the_form(): void
    {
        $this->mock(CourseFileRepository::class)->shouldReceive('all')->andReturn([]);
        Mail::shouldReceive('to')->twice()->andReturnSelf();
        Mail::shouldReceive('send')->twice()->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->postJson(route('api.contact'), [
            'name' => 'Visitor', 'email' => 'visitor@example.com', 'phone' => '50953314',
        ])->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseCount('contact_messages', 1);
        $this->assertSame(2, ContactMessage::first()->emailAttempts()->where('status', 'failed')->count());
    }

    public function test_phone_numbers_accept_only_eight_digit_kuwait_formats_and_are_normalized(): void
    {
        $this->mock(CourseFileRepository::class)->shouldReceive('all')->andReturn([]);

        foreach (['50953314', '96550953314', '+965 5095-3314'] as $phone) {
            $this->postJson(route('api.contact'), [
                'name' => 'Phone Test',
                'email' => 'phone@example.com',
                'phone' => $phone,
            ])->assertOk()->assertJson(['success' => true]);
        }

        $this->assertSame(
            ['+96550953314'],
            ContactMessage::query()->pluck('phone')->unique()->values()->all(),
        );

        foreach (['5095331', '509533144', '+96650953314', '83886756'] as $index => $phone) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.($index + 1)])
                ->postJson(route('api.contact'), [
                'name' => 'Invalid Phone',
                'email' => 'invalid@example.com',
                'phone' => $phone,
                ])->assertUnprocessable()->assertJsonFragment([
                    'message' => 'Enter a valid 8-digit Kuwait mobile number.',
                ]);
        }

        $this->assertDatabaseCount('contact_messages', 3);
    }

    public function test_resend_keeps_history_uses_current_recipient_and_blocks_duplicates(): void
    {
        $inquiry = $this->inquiry();
        $mailer = app(InquiryMailer::class);
        $mailer->send($inquiry, 'admin');
        $this->assertNull($mailer->send($inquiry, 'admin'));
        $this->travel(61)->seconds();
        config(['mail.admin_notification_email' => 'new@example.com']);
        $mailer->send($inquiry, 'admin');
        $this->assertSame(['new@example.com', 'admin@example.com'], $inquiry->emailAttempts()->pluck('recipient')->all());
        $lock = Cache::lock('inquiry-mail:'.$inquiry->id.':visitor', 300);
        $lock->get();
        try {
            $this->assertNull($mailer->send($inquiry, 'visitor'));
        } finally {
            $lock->release();
        }
    }

    public function test_admin_screen_and_resend_require_authentication(): void
    {
        $inquiry = $this->inquiry();
        $url = route('admin.inquiries.email.resend', $inquiry);
        $this->post($url, ['kind' => 'admin'])->assertRedirect(route('admin.login'));
        $this->assertDatabaseCount('inquiry_email_attempts', 0);
        $admin = Admin::create(['username' => 'tester', 'password_hash' => bcrypt('test-password'), 'role' => 'admin']);
        $this->withSession(['admin_id' => $admin->id])->get(route('admin.inquiries.index'))
            ->assertOk()->assertSee('Not tracked')->assertSee('Visitor confirmation')->assertSee('Admin notification');
        $this->post($url, ['kind' => 'invalid'])->assertSessionHasErrors('kind');
        $this->post($url, ['kind' => 'admin'])->assertSessionHas('success');
        $this->assertDatabaseCount('inquiry_email_attempts', 1);
    }

    public function test_inquiry_management_filters_assigns_and_exports_selected_rows(): void
    {
        $admin = Admin::create(['username' => 'tester', 'password_hash' => bcrypt('test-password'), 'role' => 'admin']);
        $handler = Admin::create(['username' => 'handler', 'password_hash' => bcrypt('test-password'), 'role' => 'staff']);
        $excel = ContactMessage::create([
            'name' => 'Excel Student', 'email' => 'excel@example.com', 'phone' => '+965 5555 1234',
            'course_interest' => 'Advanced Excel', 'message' => 'Excel question', 'status' => 'new',
        ]);
        $design = ContactMessage::create([
            'name' => 'Design Student', 'email' => 'design@example.com', 'phone' => '+965 5555 9999',
            'course_interest' => 'Web Design', 'message' => 'Design question', 'status' => 'resolved',
        ]);

        $this->withSession(['admin_id' => $admin->id])
            ->get(route('admin.inquiries.index', ['search' => '5555 1234', 'course' => 'Advanced Excel']))
            ->assertOk()
            ->assertSee('Excel Student')
            ->assertDontSee('Design Student')
            ->assertSee('https://wa.me/96555551234', false);

        $this->withSession(['admin_id' => $admin->id])
            ->post(route('admin.inquiries.bulk'), [
                'action' => 'bulk_assign',
                'ids' => [$excel->id],
                'assigned_admin' => $handler->id,
            ])
            ->assertSessionHas('success');

        $this->assertSame($handler->id, $excel->fresh()->assigned_to);
        $this->assertSame($admin->id, $excel->fresh()->updated_by);
        $this->assertNull($design->fresh()->assigned_to);

        $this->withSession(['admin_id' => $handler->id])
            ->get(route('admin.inquiries.index', ['assignment' => 'mine']))
            ->assertOk()
            ->assertSee('Assigned to Me')
            ->assertSee('Excel Student')
            ->assertSee('Assigned to handler')
            ->assertDontSee('Design Student');

        $this->withSession(['admin_id' => $admin->id])
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Assigned Inquiries')
            ->assertSee(route('admin.inquiries.index', ['assignment' => $handler->id]), false);

        $export = $this->withSession(['admin_id' => $admin->id])
            ->get(route('admin.inquiries.export', ['ids' => [$excel->id]]));
        $export->assertOk()->assertDownload();
        $this->assertStringContainsString('Excel Student', $export->streamedContent());
        $this->assertStringContainsString('handler', $export->streamedContent());
        $this->assertStringNotContainsString('Design Student', $export->streamedContent());
    }

    public function test_honeypot_creates_no_inquiries_or_email_attempts(): void
    {
        foreach (['api.contact', 'api.inquiry'] as $route) {
            $this->postJson(route($route), ['website' => 'spam'])->assertOk();
        }
        $this->assertDatabaseCount('contact_messages', 0);
        $this->assertDatabaseCount('inquiry_email_attempts', 0);
    }
}
