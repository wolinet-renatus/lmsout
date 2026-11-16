<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Revenue Share Service
 *
 * Handles revenue share calculation and balance updates for Courses and Bundles.
 * Logs per-instructor payout breakdown into payments.notes as parseable JSON for audit trail.
 *
 * @package App\Services
 * @author Renatus Bernardo
 */
class RevenueShareService
{
    /**
     * Inject SettingsRepository for platform revenue split configuration.
     */
    public function __construct(private SettingsRepository $settingsRepository)
    {
    }

    /**
     * Retrieve configured platform commission mode ('percentage'|'fixed') and value.
     */
    public function settings(): array
    {
        return $this->settingsRepository->get('revenue.distribution', [
            'mode' => 'percentage',
            'value' => 0,
        ]);
    }

    /**
     * Split a gross payment amount into platform commission and instructor earning pool.
     */
    public function calculate(float $amount): array
    {
        $settings = $this->settings();
        $mode = $settings['mode'] ?? 'percentage';
        $value = (float) ($settings['value'] ?? 0);

        $commission = 0.0;

        if ($amount <= 0) {
            return [
                'platform_commission' => 0.0,
                'instructor_earning' => 0.0,
            ];
        }

        if ($mode === 'fixed') {
            $commission = min($value, $amount);
        } else {
            if ($value < 0) $value = 0;
            if ($value > 100) $value = 100;
            $commission = round($amount * ($value / 100), 2);
        }

        $commission = round($commission, 2);
        $instructor = round($amount - $commission, 2);
        if ($instructor < 0) $instructor = 0.0;

        return [
            'platform_commission' => $commission,
            'instructor_earning' => $instructor,
        ];
    }

    /**
     * Process revenue share splits and credit instructor wallet balances atomically.
     */
    public function process(Payment $payment, bool $force = false): Payment
    {
        if (!$force && $payment->commission_processed) {
            return $payment;
        }

        $amount = (float) $payment->amount;
        $distribution = $this->calculate($amount);
        $platformCommission = $distribution['platform_commission'];
        $instructorEarning = $distribution['instructor_earning'];

        return DB::transaction(function () use ($payment, $platformCommission, $instructorEarning, $amount) {
            $payment->platform_commission = $platformCommission;
            $payment->commission_processed = true;

            // 1. Handle Bundle Direct Purchase Revenue Share
            if ($payment->bundle_id) {
                $payment->loadMissing('bundle.courses');
                $bundle = $payment->bundle;

                if ($bundle) {
                    $vendor = $bundle->vendor;

                    if ($vendor && $vendor->hasRole('admin')) {
                        $totalCourses = $bundle->courses->count();
                        if ($totalCourses > 0) {
                            $instructorCounts = [];
                            foreach ($bundle->courses as $c) {
                                $instructorCounts[$c->instructor_id] = ($instructorCounts[$c->instructor_id] ?? 0) + 1;
                            }
                            foreach ($instructorCounts as $instructorId => $count) {
                                $share = round(($count / $totalCourses) * $instructorEarning, 2);
                                if ($share > 0) {
                                    \App\Models\User::where('id', $instructorId)->increment('balance', $share);
                                }
                            }
                        }
                    } elseif ($vendor && $vendor->hasRole('instructor')) {
                        $vendor->increment('balance', $instructorEarning);
                    }

                    $payment->instructor_earning = $instructorEarning;
                }

            // 3. Handle Standard Single Course Direct Purchase Revenue Share (with Co-Instructor split)
            } else {
                $payment->loadMissing('enrollment.course.instructor', 'enrollment.course.coInstructors.user');
                $course = optional($payment->enrollment)->course;

                // Institutional revenue default for Face-to-Face & Hybrid courses unless explicitly enabled
                if ($course && !$course->isOnline() && !$course->f2f_commission_enabled) {
                    $payment->platform_commission = $amount;
                    $payment->instructor_earning = 0.0;
                } else {
                    $payment->instructor_earning = $instructorEarning;

                    if ($course && $instructorEarning > 0) {
                        $payoutLog = [];
                        $this->distributeCoursePool($course, $instructorEarning, $payoutLog, 'course');
                        if ($course->coInstructors->isNotEmpty()) {
                            $breakdown = [
                                'type'                => 'course_co_instructors',
                                'course_id'           => $course->id,
                                'course_title'        => $course->title,
                                'gross_amount'        => $amount,
                                'platform_commission' => $platformCommission,
                                'instructor_pool'     => $instructorEarning,
                                'payouts'             => $payoutLog,
                                'processed_at'        => now()->toDateTimeString(),
                            ];
                            $existingNotes = $payment->notes ? $payment->notes . "\n\n" : '';
                            $payment->notes = $existingNotes . '<!-- REVENUE_BREAKDOWN:' . json_encode($breakdown) . ' -->';
                        }
                    }
                }
            }

            $payment->save();
            return $payment->fresh();
        });
    }

    /**
     * Distribute a course's instructor earnings pool across primary + co-instructors.
     * Primary instructor keeps (100 - sum(co_instructor_shares))%.
     */
    public function distributeCoursePool(\App\Models\Course $course, float $poolAmount, array &$payoutLog = [], string $sourceType = 'course'): void
    {
        if ($poolAmount <= 0) {
            return;
        }

        $course->loadMissing('instructor', 'coInstructors.user');
        $coTotalPercent = 0.0;

        foreach ($course->coInstructors as $co) {
            $pct = max(0.0, min(100.0, (float) $co->revenue_share));
            if ($pct <= 0 || !$co->user) {
                continue;
            }
            // Prevent total co-instructor percentages from exceeding 100%
            $effectivePct = min($pct, max(0.0, 100.0 - $coTotalPercent));
            if ($effectivePct <= 0) {
                continue;
            }
            $coTotalPercent += $effectivePct;
            $coShare = round($poolAmount * ($effectivePct / 100), 2);
            if ($coShare > 0) {
                $co->user->increment('balance', $coShare);
                $payoutLog[] = [
                    'instructor_id'   => $co->user->id,
                    'instructor_name' => $co->user->name,
                    'role'            => 'co_instructor',
                    'share_percent'   => $effectivePct,
                    'source_type'     => $sourceType,
                    'source_id'       => $course->id,
                    'source_title'    => $course->title,
                    'share'           => $coShare,
                ];
            }
        }

        $primaryPercent = max(0.0, 100.0 - $coTotalPercent);
        $primaryShare = round($poolAmount * ($primaryPercent / 100), 2);
        if ($course->instructor && $primaryShare > 0) {
            $course->instructor->increment('balance', $primaryShare);
            $payoutLog[] = [
                'instructor_id'   => $course->instructor->id,
                'instructor_name' => $course->instructor->name,
                'role'            => 'primary',
                'share_percent'   => $primaryPercent,
                'source_type'     => $sourceType,
                'source_id'       => $course->id,
                'source_title'    => $course->title,
                'share'           => $primaryShare,
            ];
        }
    }

    /**
     * Parse the subscription revenue breakdown from a payment's notes field.
     * Returns null if no breakdown exists (regular course payment).
     */
    public static function parseBreakdown(Payment $payment): ?array
    {
        if (!$payment->notes) return null;
        if (!preg_match('/<!--\s*REVENUE_BREAKDOWN:(.*?)\s*-->/', $payment->notes, $matches)) return null;
        $decoded = json_decode($matches[1], true);
        return is_array($decoded) ? $decoded : null;
    }
}
