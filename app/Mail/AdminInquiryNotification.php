<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminInquiryNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
        public string $formType,
    ) {
    }

    public function envelope(): Envelope
    {
        $course = $this->contactMessage->course_interest;
        $subject = $this->formType === 'Course Enrollment' && $course
            ? 'New Course Inquiry — '.$course
            : 'New Contact Form Message — ICSA';

        return new Envelope(
            subject: $subject,
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-inquiry-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
