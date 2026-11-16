<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Admin New Registration Mail
 *
 * This mail is sent to the admin when a new user registers.
 *
 * @package App\Mail
 * @author Renatus Bernardo
 */
class AdminNewRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Initialize dependencies for this class.
     */
    public function __construct(public User $user)
    {
        $this->subject('New ' . ucfirst($user->role) . ' registration: ' . $user->name);
    }

    /**
     * Build.
     */
    public function build(): self
    {
        return $this->view('emails.notifications.admin-new-registration');
    }
}
