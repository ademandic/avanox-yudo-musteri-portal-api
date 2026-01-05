<?php

/**
 * YUDO Customer Portal API
 *
 * @author    Avanox Bilişim Yazılım ve Danışmanlık LTD ŞTİ
 * @copyright 2025 Avanox Bilişim
 * @license   Proprietary - All rights reserved
 */

namespace App\Mail;

use App\Models\PortalInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public PortalInvitation $invitation;
    public string $inviteUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(PortalInvitation $invitation, string $inviteUrl)
    {
        $this->invitation = $invitation;
        $this->inviteUrl = $inviteUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yudo Portal - Davetiye',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-invitation',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
