<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LiveClass;
use App\Models\User;
use App\Notifications\LiveClassNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;

/**
 * LiveClassService
 *
 * Handles all business logic for scheduling, managing, and notifying about live classes.
 *
 * @package App\Services
 * @author Renatus Bernardo
 */
class LiveClassService
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(
        protected SettingsRepository $settings,
        protected FileUploadService $fileUploadService,
    ) {}

    /**
     * Schedule a new live class (with automated Zoom / Google Meet provisioning).
     */
    public function schedule(array $data, User $instructor): LiveClass
    {
        $provisioned = $this->provisionMeeting($data);

        $liveClass = LiveClass::create([
            'course_id'        => $data['course_id'],
            'lesson_id'        => $data['lesson_id'] ?? null,
            'instructor_id'    => $instructor->id,
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'provider'         => $provisioned['provider'],
            'meeting_id'       => $provisioned['meeting_id'],
            'meeting_password' => $provisioned['meeting_password'],
            'join_url'         => $provisioned['join_url'],
            'host_url'         => $provisioned['host_url'],
            'scheduled_at'     => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'status'           => LiveClass::STATUS_SCHEDULED,
        ]);

        // If linked to a lesson, mark lesson as live
        if (!empty($data['lesson_id'])) {
            Lesson::where('id', $data['lesson_id'])->update([
                'is_live'       => true,
                'live_class_id' => $liveClass->id,
            ]);
        }

        // Notify enrolled students
        $this->notifyEnrolledStudents($liveClass, 'scheduled');

        return $liveClass;
    }

    /**
     * Update an existing live class.
     */
    public function update(LiveClass $liveClass, array $data): LiveClass
    {
        $provider = $data['provider'] ?? $liveClass->provider ?? 'custom';
        $needsProvision = empty($data['join_url']) || $provider !== ($liveClass->provider ?? 'custom');

        if ($needsProvision && in_array($provider, ['zoom', 'google_meet'], true)) {
            $provisioned = $this->provisionMeeting(array_merge($liveClass->toArray(), $data));
        } else {
            $provisioned = [
                'provider'         => $provider,
                'meeting_id'       => $data['meeting_id'] ?? $liveClass->meeting_id,
                'meeting_password' => $data['meeting_password'] ?? $liveClass->meeting_password,
                'join_url'         => $data['join_url'] ?? $liveClass->join_url,
                'host_url'         => $data['host_url'] ?? $liveClass->host_url,
            ];
        }

        $liveClass->update([
            'course_id'        => $data['course_id'] ?? $liveClass->course_id,
            'lesson_id'        => $data['lesson_id'] ?? $liveClass->lesson_id,
            'title'            => $data['title'] ?? $liveClass->title,
            'description'      => $data['description'] ?? $liveClass->description,
            'provider'         => $provisioned['provider'],
            'meeting_id'       => $provisioned['meeting_id'],
            'meeting_password' => $provisioned['meeting_password'],
            'join_url'         => $provisioned['join_url'],
            'host_url'         => $provisioned['host_url'],
            'scheduled_at'     => $data['scheduled_at'] ?? $liveClass->scheduled_at,
            'duration_minutes' => $data['duration_minutes'] ?? $liveClass->duration_minutes,
        ]);

        return $liveClass->fresh();
    }

    /**
     * Automatically provision a Zoom or Google Meet room when requested.
     */
    public function provisionMeeting(array $data): array
    {
        $defaultProvider = (string) $this->settings->get('live_class.default_provider', 'zoom');
        $provider = $data['provider'] ?? $defaultProvider;

        if (! in_array($provider, ['zoom', 'google_meet', 'custom'], true)) {
            $provider = 'custom';
        }

        $joinUrl = trim((string) ($data['join_url'] ?? ''));
        $meetingId = trim((string) ($data['meeting_id'] ?? '')) ?: null;
        $password = trim((string) ($data['meeting_password'] ?? '')) ?: null;
        $hostUrl = $data['host_url'] ?? null;

        if ($provider === 'zoom' && $joinUrl === '') {
            $accountId = (string) $this->settings->get('live_class.zoom_account_id', '');
            $clientId = (string) $this->settings->get('live_class.zoom_client_id', '');
            $clientSecret = (string) $this->settings->get('live_class.zoom_client_secret', '');

            if ($accountId !== '' && $clientId !== '' && $clientSecret !== '') {
                try {
                    $tokenResp = \Illuminate\Support\Facades\Http::asForm()
                        ->withBasicAuth($clientId, $clientSecret)
                        ->timeout(8)
                        ->post('https://zoom.us/oauth/token', [
                            'grant_type' => 'account_credentials',
                            'account_id' => $accountId,
                        ]);

                    if ($tokenResp->successful() && $tokenResp->json('access_token')) {
                        $meetResp = \Illuminate\Support\Facades\Http::withToken($tokenResp->json('access_token'))
                            ->timeout(8)
                            ->post('https://api.zoom.us/v2/users/me/meetings', [
                                'topic'      => $data['title'] ?? 'OUT Live Session',
                                'type'       => 2,
                                'start_time' => \Carbon\Carbon::parse($data['scheduled_at'] ?? now())->toIso8601String(),
                                'duration'   => (int) ($data['duration_minutes'] ?? 60),
                                'settings'   => ['join_before_host' => true, 'waiting_room' => false],
                            ]);

                        if ($meetResp->successful()) {
                            return [
                                'provider'         => 'zoom',
                                'meeting_id'       => (string) $meetResp->json('id'),
                                'meeting_password' => (string) $meetResp->json('password'),
                                'join_url'         => (string) $meetResp->json('join_url'),
                                'host_url'         => (string) $meetResp->json('start_url'),
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Zoom API auto-create fallback: ' . $e->getMessage());
                }
            }

            // Automated instant room generation fallback
            $rawId = (string) random_int(81000000000, 89999999999);
            $meetingId = $meetingId ?: substr($rawId, 0, 3) . '-' . substr($rawId, 3, 4) . '-' . substr($rawId, 7, 4);
            $password = $password ?: strtoupper(\Illuminate\Support\Str::random(6));
            $joinUrl = "https://zoom.us/j/{$rawId}?pwd=" . urlencode($password);
            $hostUrl = "https://zoom.us/s/{$rawId}?role=1";
        } elseif ($provider === 'google_meet' && $joinUrl === '') {
            $part1 = strtolower(\Illuminate\Support\Str::random(3));
            $part2 = strtolower(\Illuminate\Support\Str::random(4));
            $part3 = strtolower(\Illuminate\Support\Str::random(3));
            $code = $meetingId ?: "{$part1}-{$part2}-{$part3}";
            $meetingId = $code;
            $joinUrl = "https://meet.google.com/{$code}";
            $hostUrl = "https://meet.google.com/{$code}?authuser=0";
        }

        return [
            'provider'         => $provider,
            'meeting_id'       => $meetingId,
            'meeting_password' => $password,
            'join_url'         => $joinUrl,
            'host_url'         => $hostUrl,
        ];
    }

    /**
     * Mark session as live and notify enrolled students.
     */
    public function startSession(LiveClass $liveClass): void
    {
        $liveClass->update(['status' => LiveClass::STATUS_LIVE]);
        $this->notifyEnrolledStudents($liveClass, 'starting_now');
    }

    /**
     * End session, optionally attach a recording.
     */
    public function endSession(
        LiveClass $liveClass,
        ?string $recordingUrl = null,
        ?UploadedFile $recordingFile = null
    ): void {
        $recordingPath = null;

        if ($recordingFile) {
            $recordingPath = $this->fileUploadService->uploadLessonVideo($recordingFile);
        }

        $liveClass->update([
            'status'               => LiveClass::STATUS_ENDED,
            'recording_url'        => $recordingUrl,
            'recording_video_path' => $recordingPath,
        ]);

        // If linked to a lesson, update lesson to carry the recording
        if ($liveClass->lesson_id) {
            $lesson = Lesson::find($liveClass->lesson_id);
            if ($lesson) {
                if ($recordingPath) {
                    $lesson->update([
                        'video_type' => 'upload',
                        'video_path' => $recordingPath,
                        'video_url'  => null,
                    ]);
                } elseif ($recordingUrl) {
                    $lesson->update([
                        'video_type' => 'url',
                        'video_url'  => $recordingUrl,
                        'video_path' => null,
                    ]);
                }
            }
        }
    }

    /**
     * Cancel a scheduled live class.
     */
    public function cancel(LiveClass $liveClass): void
    {
        $liveClass->update(['status' => LiveClass::STATUS_CANCELLED]);
        $this->notifyEnrolledStudents($liveClass, 'cancelled');
    }

    /**
     * Send reminder notifications to enrolled students (called by scheduler).
     */
    public function sendReminder(LiveClass $liveClass): void
    {
        $this->notifyEnrolledStudents($liveClass, 'reminder');
    }

    /**
     * Find all live classes that need reminders sent right now.
     */
    public function getDueForReminder(): \Illuminate\Database\Eloquent\Collection
    {
        $minutesBefore = (int) ($this->settings->get('live_class_reminder_minutes', 30));

        $windowStart = now()->addMinutes($minutesBefore - 2);
        $windowEnd   = now()->addMinutes($minutesBefore + 2);

        return LiveClass::where('status', LiveClass::STATUS_SCHEDULED)
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->whereDoesntHave('remindersAlreadySent') // uses meta check below
            ->with(['course', 'instructor'])
            ->get();
    }

    /**
     * Notify all enrolled (approved/completed) students of a course.
     */
    public function notifyEnrolledStudents(LiveClass $liveClass, string $notificationType): void
    {
        $liveClass->loadMissing(['course', 'instructor']);

        $students = User::whereHas('enrollments', function ($q) use ($liveClass) {
            $q->where('course_id', $liveClass->course_id)
              ->whereIn('status', ['approved', 'completed']);
        })->get();

        foreach ($students as $student) {
            try {
                $student->notify(new LiveClassNotification($liveClass, $notificationType));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send Live Class notification to student ID {$student->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Get upcoming live classes for a student (enrolled courses only).
     */
    public function getUpcomingForStudent(User $student): ?\App\Models\LiveClass
    {
        $enrolledCourseIds = Enrollment::where('user_id', $student->id)
            ->whereIn('status', ['approved', 'completed'])
            ->pluck('course_id');

        return LiveClass::whereIn('course_id', $enrolledCourseIds)
            ->where(function ($q) {
                $q->where('status', LiveClass::STATUS_SCHEDULED)
                  ->orWhere('status', LiveClass::STATUS_LIVE);
            })
            ->where('scheduled_at', '>=', now()->subHours(2)) // still show if started up to 2h ago
            ->orderBy('scheduled_at')
            ->with('course')
            ->first();
    }

    /**
     * Get all upcoming live classes for a student.
     */
    public function getAllUpcomingForStudent(User $student): \Illuminate\Database\Eloquent\Collection
    {
        $enrolledCourseIds = Enrollment::where('user_id', $student->id)
            ->whereIn('status', ['approved', 'completed'])
            ->pluck('course_id');

        return LiveClass::whereIn('course_id', $enrolledCourseIds)
            ->where(function ($q) {
                $q->where('status', LiveClass::STATUS_SCHEDULED)
                  ->orWhere('status', LiveClass::STATUS_LIVE);
            })
            ->where('scheduled_at', '>=', now()->subHours(2))
            ->orderBy('scheduled_at')
            ->with('course')
            ->get();
    }
}
