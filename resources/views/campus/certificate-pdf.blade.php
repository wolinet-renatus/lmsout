<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; margin: 0; padding: 24px; }
        .frame { border: 8px double #013ba6; padding: 36px; text-align: center; }
        .org { color: #013ba6; font-size: 22px; font-weight: bold; margin-bottom: 6px; }
        .sub { color: #228ddd; font-size: 13px; margin-bottom: 24px; text-transform: uppercase; letter-spacing: 0.6px; }
        .title { font-size: 30px; letter-spacing: 2px; margin: 16px 0; color: #013ba6; font-weight: bold; }
        .name { font-size: 26px; font-weight: bold; margin: 16px 0; border-bottom: 1px solid #9ca3af; display: inline-block; padding: 0 24px 6px; }
        .course { font-size: 20px; font-weight: bold; color: #111827; margin: 12px 0; }
        .meta { font-size: 12px; color: #4b5563; margin-top: 22px; }
        .qr { margin-top: 18px; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="org">{{ strtoupper(site_name()) }}</div>
        <div class="sub">The Open University of Tanzania &bull; Short Course &amp; Campus Programme</div>
        <div class="title">CERTIFICATE OF COMPLETION</div>
        <p>This is to certify that</p>
        <div class="name">{{ $enrollment->applicant?->full_name ?: ($enrollment->user?->name ?? 'Student') }}</div>
        <p>has successfully completed the programme in</p>
        <div class="course">{{ $enrollment->course?->title ?? 'Course' }}</div>
        <p>
            Delivered at <strong>{{ $enrollment->branch?->name ?? 'Main Centre' }}</strong>
            ({{ ucwords(str_replace('_', ' ', $enrollment->delivery_mode ?? 'face_to_face')) }})
        </p>

        <div class="meta">
            Serial Number: <strong>{{ $enrollment->certificate_serial }}</strong> |
            Issued Date: <strong>{{ ($enrollment->certificate_issued_at ?? $enrollment->certificate_ready_at ?? now())->format('d M Y') }}</strong>
        </div>
        <div class="qr">
            <img src="{{ $qr }}" width="90" height="90" alt="Verify QR"><br>
            <small>{{ $verifyUrl }}</small>
        </div>
    </div>
</body>
</html>
