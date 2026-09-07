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
            'name' => 'Test Visitor', 'email' => 'visitor@example.com', 'phone' => '12345678',
            'message' => 'Test inquiry', 'status' => 'new', 'form_type' => 'Contact Form',
        ]);
    }

    public function test_both_forms_record_separate_emails_and_preserve_reply_to(): void
    {
        $this->mock(CourseFileRepository::class)->shouldReceive('all')->andReturn([['slug' => 'python']]);
        foreach (['api.contact' => 'Contact Form', 'api.inquiry' => 'Course Enrollment'] as $route => $type) {
            $this->postJson(route($route), [
                'name' => 'Test Visitor', 'email' => 'visitor@example.com', 'phone' => '12345678',
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
            'name' => 'Visitor', 'email' => 'visitor@example.com', 'phone' => '12345678',
        ])->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseCount('contact_messages', 1);
        $this->assertSame(2, ContactMessage::first()->emailAttempts()->where('status', 'failed')->count());
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

    public function test_honeypot_creates_no_inquiries_or_email_attempts(): void
    {
        foreach (['api.contact', 'api.inquiry'] as $route) {
            $this->postJson(route($route), ['website' => 'spam'])->assertOk();
        }
        $this->assertDatabaseCount('contact_messages', 0);
        $this->assertDatabaseCount('inquiry_email_attempts', 0);
    }
}
