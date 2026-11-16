<?php

namespace App\Http\Controllers\Admin\Campus;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Enrollment;
use App\Services\QrCodeService;
use App\Services\SrsWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

/**
 * F2F / Hybrid certificate clearance (tuition balance + attendance threshold).
 * Online-course certificates remain in the existing Admin\CertificateController.
 * @author Renatus Bernardo
 */
class CertificateClearanceController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(private SrsWorkflowService $srs)
    {
    }

    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $enrollments = Enrollment::with(['user:id,name,email', 'course:id,title,attendance_threshold', 'branch:id,name', 'invoice:id,enrollment_id,balance,payment_status'])
            ->whereIn('delivery_mode', ['face_to_face', 'hybrid'])
            ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->where('certificate_status', $status)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $enrollments->getCollection()->each(function (Enrollment $e) {
            $e->setAttribute('is_eligible', $this->srs->isCertificateEligible($e));
        });

        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.campus.certificates.index', compact('enrollments', 'branches', 'status'));
    }

    /**
     * Validate and update the specified resource record.
     */
    public function update(Request $request, Enrollment $enrollment)
    {
        $target = $request->validate(['action' => ['required', 'in:ready,issued']])['action'];

        if (! $this->srs->isCertificateEligible($enrollment)) {
            ToastMagic::error('Not eligible yet: tuition must be fully paid and attendance must meet the course threshold.');
            return back();
        }

        $this->srs->ensureCertificate($enrollment, $request->user()->id, $target);

        if ($target === 'ready') {
            $enrollment->loadMissing('course.instructor', 'courseAssignment.instructor', 'user', 'branch');
            $instructor = $enrollment->courseAssignment?->instructor ?? $enrollment->course?->instructor;
            if ($instructor?->email) {
                try {
                    \Illuminate\Support\Facades\Mail::raw(
                        "Certificate Ready Notification\n\nStudent: " . ($enrollment->user?->name ?? 'Learner')
                        . "\nCourse: " . ($enrollment->course?->title ?? 'Short Course')
                        . "\nCenter: " . ($enrollment->branch?->name ?? 'Main Centre')
                        . "\nSerial: " . ($enrollment->certificate_serial ?? 'Pending'),
                        fn ($m) => $m->to($instructor->email)->subject('Certificate Ready for Release - ' . ($enrollment->course?->title ?? 'Course'))
                    );
                } catch (\Throwable) {
                }
            }
        }

        ToastMagic::success($target === 'issued' ? 'Certificate issued.' : 'Certificate marked ready & tutor notified.');

        return back();
    }

    /**
     * Download pdf.
     */
    public function downloadPdf(Enrollment $enrollment)
    {
        abort_unless(
            $enrollment->certificate_serial && in_array($enrollment->certificate_status, ['ready', 'issued'], true),
            404
        );

        $enrollment->load(['user', 'applicant', 'course', 'branch']);
        $verifyUrl = route('verify-certificate', ['code' => $enrollment->certificate_serial]);
        $qr = QrCodeService::dataUri($verifyUrl, 120);

        return Pdf::loadView('campus.certificate-pdf', compact('enrollment', 'qr', 'verifyUrl'))
            ->download(($enrollment->certificate_serial ?: 'certificate') . '.pdf');
    }
}
