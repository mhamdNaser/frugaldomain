<?php

namespace App\Modules\Core\Mail;

use App\Modules\Core\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[FrugalDomain] Contact Form: ' . $this->contactMessage->subject,
            replyTo: [$this->contactMessage->email]
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message-submitted',
            with: [
                'contactMessage' => $this->contactMessage,
            ]
        );
    }
}

