<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Verify Email Mail
 *
 * This mail is sent to the user when they need to verify their email address.
 *
 * @package App\Mail
 * @author Renatus Bernardo
 */
class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Initialize dependencies for this class.
     */
    public function __construct(public User $user, public string $verificationUrl)
    {
        $this->subject('Verify your ' . config('app.name') . ' email address');
    }

    /**
     * Build.
     */
    public function build(): self
    {
        return $this->view('emails.auth.verify-email');
    }
}
