<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\CourseAssignment;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Shared attendance logic for Admin, Instructor (tutor) and Student QR self check-in.
 *
 * @package App\Services
 * @author Renatus Bernardo
 */
class CampusAttendanceService
{
    /**
     * Inject SrsWorkflowService to recompute enrollment attendance percentages after check-ins.
     */
    public function __construct(private SrsWorkflowService $srs)
    {
    }

    /**
     * Enrollments (roster) for a cohort.
     */
    public function roster(CourseAssignment $assignment)
    {
        return Enrollment::with('user:id,name,email,phone')
            ->where('course_id', $assignment->course_id)
            ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED, Enrollment::STATUS_PENDING])
            ->whereIn('delivery_mode', ['face_to_face', 'hybrid'])
            ->where(function ($q) use ($assignment) {
                $q->where('course_assignment_id', $assignment->id)
                    ->orWhere(function ($w) use ($assignment) {
                        $w->whereNull('course_assignment_id')
                            ->when($assignment->branch_id, fn ($b) => $b->where('branch_id', $assignment->branch_id))
                            ->when($assignment->session, fn ($s) => $s->where('session', $assignment->session));
                    });
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * Persist manual marks: [enrollment_id => status].
     *
     * @param  array<int|string, string>  $marks
     */
    public function saveMarks(CourseAssignment $assignment, string $date, array $marks, User $marker): int
    {
        $saved = 0;
        DB::transaction(function () use ($assignment, $date, $marks, $marker, &$saved) {
            foreach ($marks as $enrollmentId => $status) {
                if (! in_array($status, ['present', 'late', 'absent', 'excused'], true)) {
                    continue;
                }
                $enrollment = Enrollment::find($enrollmentId);
                if (! $enrollment || (int) $enrollment->course_id !== (int) $assignment->course_id) {
                    continue;
                }

                AttendanceRecord::withoutGlobalScope('branch')->updateOrCreate(
                    ['enrollment_id' => $enrollment->id, 'attendance_date' => $date],
                    [
                        'course_id' => $assignment->course_id,
                        'course_assignment_id' => $assignment->id,
                        'user_id' => $enrollment->user_id,
                        'branch_id' => $assignment->branch_id ?? $enrollment->branch_id,
                        'status' => $status,
                        'check_in_method' => 'manual',
                        'checked_in_at' => in_array($status, ['present', 'late'], true) ? now() : null,
                        'marked_by' => $marker->id,
                    ]
                );
                $this->srs->recalculateAttendancePercentage($enrollment);
                $saved++;
            }
        });

        return $saved;
    }

    /**
     * Open a short-lived QR check-in session (default 15 minutes) for a cohort/date.
     */
    public function openQrSession(CourseAssignment $assignment, int $minutes = 15): string
    {
        $token = Str::random(40);
        Cache::put('attendance_qr:' . $token, [
            'assignment_id' => $assignment->id,
            'date' => now()->toDateString(),
        ], now()->addMinutes($minutes));

        return $token;
    }

    /**
     * Student self check-in through a QR token.
     *
     * @return array{ok: bool, message: string}
     */
    public function checkIn(string $token, User $student): array
    {
        $payload = Cache::get('attendance_qr:' . $token);
        if (! $payload) {
            return ['ok' => false, 'message' => 'This QR code has expired. Ask your tutor to display a new one.'];
        }

        $assignment = CourseAssignment::withoutGlobalScope('branch')->find($payload['assignment_id']);
        if (! $assignment) {
            return ['ok' => false, 'message' => 'Class session not found.'];
        }

        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('course_id', $assignment->course_id)
            ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])
            ->latest('id')
            ->first();

        if (! $enrollment) {
            return ['ok' => false, 'message' => 'You are not an active student of this class.'];
        }

        $sessionStart = $assignment->start_time ? Carbon::parse($payload['date'] . ' ' . substr((string) $assignment->start_time, 0, 8)) : null;
        $status = ($sessionStart && now()->gt($sessionStart->copy()->addMinutes(15))) ? 'late' : 'present';

        AttendanceRecord::withoutGlobalScope('branch')->updateOrCreate(
            ['enrollment_id' => $enrollment->id, 'attendance_date' => $payload['date']],
            [
                'course_id' => $assignment->course_id,
                'course_assignment_id' => $assignment->id,
                'user_id' => $student->id,
                'branch_id' => $assignment->branch_id ?? $enrollment->branch_id,
                'session_token' => substr($token, 0, 100),
                'status' => $status,
                'check_in_method' => 'qr_self',
                'checked_in_at' => now(),
            ]
        );
        $this->srs->recalculateAttendancePercentage($enrollment);

        return ['ok' => true, 'message' => 'Checked in as ' . strtoupper($status) . ' for ' . ($assignment->course?->title ?? 'class') . '.'];
    }
}
