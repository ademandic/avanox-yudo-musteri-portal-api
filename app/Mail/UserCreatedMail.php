<?php

/**
 * YUDO Customer Portal API
 *
 * @author    Avanox Bilişim Yazılım ve Danışmanlık LTD ŞTİ
 * @copyright 2025 Avanox Bilişim
 * @license   Proprietary - All rights reserved
 */

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $password;
    public string $loginUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
        $this->loginUrl = config('portal.frontend_url', 'https://portal.yudo.com.tr') . '/giris';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yudo Portal - Hesabınız Oluşturuldu',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.user-created',
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
