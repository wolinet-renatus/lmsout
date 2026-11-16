<?php
/**
 * Dynamic Certificates API controller.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Certificate controller API Controller — handles public and authenticated Sanctum REST endpoints.
 *
 * @author Renatus Bernardo
 */
class CertificateController extends Controller
{
    /**
     * Get all certificates for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $certificates = Enrollment::with(['course.instructor', 'branch:id,name'])
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('status', Enrollment::STATUS_COMPLETED)
                    ->orWhere(function ($cq) {
                        $cq->whereNotNull('certificate_serial')
                            ->whereIn('certificate_status', ['ready', 'issued']);
                    });
            })
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($enrollment) {
                $serial = $enrollment->certificate_serial ?: $enrollment->certificate_number;
                return [
                    'id' => $enrollment->id,
                    'course_id' => $enrollment->course_id,
                    'title' => $enrollment->course->title,
                    'issued_date' => $enrollment->completion_date ?: optional($enrollment->certificate_issued_at ?? $enrollment->certificate_ready_at)->format('F d, Y'),
                    'issued_at' => ($enrollment->certificate_issued_at ?? $enrollment->updated_at)?->toIso8601String(),
                    'certificate_id' => $serial,
                    'delivery_mode' => $enrollment->delivery_mode ?? ($enrollment->course?->delivery_mode ?? 'online'),
                    'branch_name' => $enrollment->branch?->name,
                    'course_image' => $enrollment->course->thumbnail,
                    'instructor_name' => $enrollment->course->instructor->name ?? 'Instructor',
                    'download_url' => URL::temporarySignedRoute(
                        'api.certificates.download.signed',
                        now()->addDays(1),
                        ['id' => $enrollment->id]
                    ),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $certificates
        ]);
    }

    /**
     * Get single certificate details.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $enrollment = Enrollment::with(['course.instructor', 'branch:id,name'])
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('status', Enrollment::STATUS_COMPLETED)
                    ->orWhere(function ($cq) {
                        $cq->whereNotNull('certificate_serial')
                            ->whereIn('certificate_status', ['ready', 'issued']);
                    });
            })
            ->findOrFail($id);

        $serial = $enrollment->certificate_serial ?: $enrollment->certificate_number;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'title' => $enrollment->course->title,
                'issued_date' => $enrollment->completion_date ?: optional($enrollment->certificate_issued_at ?? $enrollment->certificate_ready_at)->format('F d, Y'),
                'certificate_id' => $serial,
                'delivery_mode' => $enrollment->delivery_mode ?? ($enrollment->course?->delivery_mode ?? 'online'),
                'branch_name' => $enrollment->branch?->name,
                'course_image' => $enrollment->course->thumbnail,
                'instructor_name' => $enrollment->course->instructor->name ?? 'Instructor',
                'download_url' => URL::temporarySignedRoute(
                    'api.certificates.download.signed',
                    now()->addDays(1),
                    ['id' => $enrollment->id]
                ),
            ]
        ]);
    }

    /**
     * Download certificate as PDF.
     */
    public function download(Request $request, $id)
    {
        $user = $request->user();

        $enrollment = Enrollment::with(['user', 'applicant', 'course.instructor', 'branch'])
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('status', Enrollment::STATUS_COMPLETED)
                    ->orWhere(function ($cq) {
                        $cq->whereNotNull('certificate_serial')
                            ->whereIn('certificate_status', ['ready', 'issued']);
                    });
            })
            ->findOrFail($id);

        return $this->renderCertificatePdf($enrollment);
    }

    /**
     * Download certificate using signed URL.
     */
    public function downloadSigned(Request $request, $id)
    {
        $enrollment = Enrollment::with(['user', 'applicant', 'course.instructor', 'branch'])
            ->where(function ($q) {
                $q->where('status', Enrollment::STATUS_COMPLETED)
                    ->orWhere(function ($cq) {
                        $cq->whereNotNull('certificate_serial')
                            ->whereIn('certificate_status', ['ready', 'issued']);
                    });
            })
            ->findOrFail($id);

        return $this->renderCertificatePdf($enrollment);
    }

    /**
     * Render certificate pdf.
     */
    private function renderCertificatePdf(Enrollment $enrollment)
    {
        if ($enrollment->certificate_serial) {
            $verifyUrl = route('verify-certificate', ['code' => $enrollment->certificate_serial]);
            $qr = \App\Services\QrCodeService::dataUri($verifyUrl, 120);

            return Pdf::loadView('campus.certificate-pdf', compact('enrollment', 'qr', 'verifyUrl'))
                ->download($enrollment->certificate_serial . '.pdf');
        }

        $certificateData = [
            'course_id' => $enrollment->course_id,
            'student_name' => $enrollment->user?->name ?? 'Student',
            'course_title' => $enrollment->course?->title ?? 'Course',
            'instructor_name' => $enrollment->course?->instructor?->name ?? 'Instructor',
            'completion_date' => optional($enrollment->updated_at)->format('F d, Y'),
            'certificate_number' => $enrollment->certificate_number,
            'verification_url' => route('verify-certificate', ['code' => $enrollment->certificate_number]),
            'background_base64' => $this->certificateBackgroundBase64(),
        ];

        $pdf = Pdf::loadView('student.certificate', $certificateData)
            ->setPaper('a4', 'landscape')
            ->setOption('enable-local-file-access', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('enable_remote', true)
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0);

        $filename = 'certificate-' . Str::slug($certificateData['course_title']) . '-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Get the certificate background base64
     */
    private function certificateBackgroundBase64(): ?string
    {
        $path = public_path('assets/front/img/certificate.png');

        if (!file_exists($path)) {
            return null;
        }

        return base64_encode(file_get_contents($path));
    }
}
