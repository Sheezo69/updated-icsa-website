<?php

namespace App\Support;

use App\Mail\AdminInquiryNotification;
use App\Mail\VisitorConfirmation;
use App\Models\Admin;
use App\Models\ContactMessage;
use App\Models\InquiryEmailAttempt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class InquiryMailer
{
    public function sendBoth(ContactMessage $inquiry): void
    {
        foreach (['visitor', 'admin'] as $kind) {
            try {
                $this->send($inquiry, $kind);
            } catch (\Throwable $exception) {
                // A mail failure must not reject an already saved inquiry.
                report($exception);
            }
        }
    }

    public function send(ContactMessage $inquiry, string $kind): ?InquiryEmailAttempt
    {
        if (! in_array($kind, ['visitor', 'admin'], true)) {
            throw new \InvalidArgumentException('Unknown email type.');
        }
        $lock = Cache::lock('inquiry-mail:'.$inquiry->id.':'.$kind, 300);
        if (! $lock->get()) {
            return null;
        }
        try {
            if ($inquiry->emailAttempts()->where('kind', $kind)
                ->where('created_at', '>', now()->subMinute())->exists()) {
                return null;
            }
            $recipient = $kind === 'visitor' ? $inquiry->email : config('mail.admin_notification_email');
            $attempt = $inquiry->emailAttempts()->create([
                'kind' => $kind,
                'recipient' => $recipient,
                'mailer' => config('mail.default'),
                'status' => 'pending',
            ]);
            try {
                if (! is_string($recipient) || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('Recipient email is missing or invalid.');
                }
                $mail = $kind === 'visitor'
                    ? new VisitorConfirmation($inquiry)
                    : new AdminInquiryNotification($inquiry, $inquiry->form_type ?? 'Contact Form');
                $pendingMail = Mail::to($recipient);
                if ($kind === 'admin' && Schema::hasColumns('admins', ['notify_email', 'notify_inquiries'])) {
                    $copies = Admin::query()
                        ->where('notify_email', true)
                        ->where('notify_inquiries', true)
                        ->whereNotNull('email')
                        ->pluck('email')
                        ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) && $email !== $recipient)
                        ->unique()
                        ->values()
                        ->all();

                    if ($copies !== []) {
                        $pendingMail->bcc($copies);
                    }
                }
                $sent = $pendingMail->send($mail);
                if ($sent === null) {
                    throw new \RuntimeException('The mail transport did not accept the message.');
                }
            } catch (\Throwable $exception) {
                // Do not expose raw SMTP exceptions (credentials or message content) in the UI.
                $attempt->update([
                    'status' => 'failed',
                    'error' => $exception instanceof \InvalidArgumentException
                        ? 'Recipient email is missing or invalid. Check the visitor address or ADMIN_NOTIFICATION_EMAIL.'
                        : 'Email could not be handed to the mail transport. Check SMTP settings and the server log for this attempt.',
                ]);
                report($exception);
                return $attempt;
            }
            $attempt->update(['status' => 'sent', 'sent_at' => now()]);
            return $attempt;
        } finally {
            $lock->release();
        }
    }
}
