<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Course Updated Mail
 *
 * This mail is sent to the instructor when a course is updated.
 *
 * @package App\Mail
 * @author Renatus Bernardo
 */
class CourseUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Carbon $occurredAt;

    /**
     * Initialize dependencies for this class.
     */
    public function __construct(public Course $course, public User $instructor, public ?string $summary = null, ?Carbon $occurredAt = null, public ?string $studentName = null)
    {
        $this->subject('Updates in ' . $course->title);
        $this->occurredAt = $occurredAt ?? now();
    }

    /**
     * Build.
     */
    public function build(): self
    {
        return $this->view('emails.notifications.course-updated');
    }
}
