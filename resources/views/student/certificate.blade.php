{{--
    Certificate dispatcher.
    Resolves the certificate template for the enrollment (course-assigned template,
    otherwise the system default). When a builder template exists it is rendered;
    otherwise the classic certificate layout is used.
--}}
@php
    $resolvedTemplate = $template ?? null;

    if (! $resolvedTemplate && \Illuminate\Support\Facades\Schema::hasTable('certificate_templates')) {
        if (!empty($certificate_number)) {
            $certEnrollment = \App\Models\Enrollment::with('course.certificateTemplate')
                ->where('certificate_number', $certificate_number)
                ->first();
            $courseTemplate = $certEnrollment?->course?->certificateTemplate;
            if ($courseTemplate && $courseTemplate->is_active) {
                $resolvedTemplate = $courseTemplate;
            }
        }
        $resolvedTemplate = $resolvedTemplate ?: \App\Models\CertificateTemplate::getDefault();
    }

    $useTemplate = $resolvedTemplate && !empty($resolvedTemplate->elements);
    $isPdfRender = $is_pdf ?? true;
@endphp

@if ($useTemplate)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Completion</title>
    @unless ($isPdfRender)
        <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Great+Vibes&family=Inter:wght@400;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    @endunless
    <style>
        @page { size: {{ ($resolvedTemplate->orientation ?? 'landscape') === 'portrait' ? '595pt 842pt' : '842pt 595pt' }}; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #ffffff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        {!! $resolvedTemplate->custom_css ?? '' !!}
    </style>
</head>
<body>
    @include('certificates.partials.template-render', [
        'template' => $resolvedTemplate,
        'is_pdf' => $isPdfRender,
    ])
</body>
</html>
@else
    @include('student.certificate-classic')
@endif

