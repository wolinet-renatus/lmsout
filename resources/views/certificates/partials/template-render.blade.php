{{--
    Renders a CertificateTemplate (drag & drop builder output).
    Expects: $template, $student_name, $course_title, $instructor_name,
             $completion_date, $certificate_number, optional $is_pdf.
    In PDF mode coordinates are rendered in points (pt) so the 842x595
    canvas maps exactly onto an A4 landscape page.
--}}
@php
    $unit = !empty($is_pdf) ? 'pt' : 'px';
    $canvasW = $template->canvas_width ?? 842;
    $canvasH = $template->canvas_height ?? 595;
    $certNo = $certificate_number ?? 'CERT-000000';
    $verifyUrl = $verification_url ?? route('verify-certificate', ['code' => $certNo]);
    $replacements = [
        '{student_name}' => $student_name ?? 'Student',
        '{course_title}' => $course_title ?? 'Course',
        '{instructor_name}' => $instructor_name ?? 'Instructor',
        '{issued_at}' => $completion_date ?? now()->format('F d, Y'),
        '{completion_date}' => $completion_date ?? now()->format('F d, Y'),
        '{certificate_no}' => $certNo,
        '{certificate_number}' => $certNo,
        '{verification_code}' => $certNo,
        '{verify_url}' => $verifyUrl,
        '{site_name}' => $site_name ?? config('app.name', 'OUT'),
        '{qr}' => '',
    ];
    $qrSrc = \App\Services\QrCodeService::dataUri($verifyUrl, 200);

    $bgSrc = null;
    if (!empty($template->background_image)) {
        $bgPath = public_path($template->background_image);
        if (!empty($is_pdf) && file_exists($bgPath)) {
            $mime = mime_content_type($bgPath) ?: 'image/png';
            $bgSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($bgPath));
        } else {
            $bgSrc = asset($template->background_image);
        }
    }

    $logoPath = public_path('assets/front/img/logo/logo.png');
    $logoSrc = file_exists($logoPath)
        ? (!empty($is_pdf) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : asset('assets/front/img/logo/logo.png'))
        : null;

    $fontMap = function ($family) use ($is_pdf) {
        if (!empty($is_pdf)) {
            return in_array($family, ['sans-serif', 'Inter']) ? "'DejaVu Sans', sans-serif" : "'DejaVu Serif', serif";
        }
        return match ($family) {
            'sans-serif', 'Inter' => "'Inter', sans-serif",
            'cursive', 'Great Vibes' => "'Great Vibes', cursive",
            'Dancing Script' => "'Dancing Script', cursive",
            default => "'Playfair Display', Georgia, serif",
        };
    };
@endphp

<div class="cert-viewport" style="position: relative; overflow: hidden; background: #ffffff; width: {{ $canvasW }}{{ $unit }}; height: {{ $canvasH }}{{ $unit }};">
    @if ($bgSrc)
        <img src="{{ $bgSrc }}" alt="" style="position: absolute; left: 0; top: 0; width: {{ $canvasW }}{{ $unit }}; height: {{ $canvasH }}{{ $unit }}; z-index: 0;">
    @endif

    @foreach (($template->elements ?? []) as $el)
        @php
            $type = $el['type'] ?? 'text';
            $content = $el['content'] ?? '';
            foreach ($replacements as $key => $val) {
                $content = str_replace($key, $val, $content);
            }
            $align = $el['text_align'] ?? 'center';
            $base = "position:absolute; left:{$el['x']}{$unit}; top:{$el['y']}{$unit}; width:{$el['width']}{$unit}; height:" . ($el['height'] ?? 20) . "{$unit}; z-index:" . ($el['z_index'] ?? 4) . ';';
        @endphp

        @if ($type === 'line')
            <div style="{{ $base }} height: 0; border-top: {{ $el['border_width'] ?? 1 }}{{ $unit }} solid {{ $el['border_color'] ?? '#d4af37' }};"></div>
        @elseif ($type === 'box')
            <div style="{{ $base }} box-sizing: border-box; border: {{ $el['border_width'] ?? 1 }}{{ $unit }} {{ $el['border_style'] ?? 'solid' }} {{ $el['border_color'] ?? '#1e3a8a' }}; border-radius: {{ $el['border_radius'] ?? 0 }}{{ $unit }}; background-color: {{ $el['background_color'] ?? 'transparent' }};"></div>
        @elseif ($type === 'qr' || trim($el['content'] ?? '') === '{qr}')
            <div style="{{ $base }}">
                <img src="{{ $qrSrc }}" alt="QR Code" style="width: {{ $el['width'] }}{{ $unit }}; height: {{ $el['height'] }}{{ $unit }};">
            </div>
        @elseif (in_array($type, ['image', 'logo', 'signature']))
            <div style="{{ $base }} text-align: {{ $align }};">
                @if (!empty($el['image_url']))
                    <img src="{{ $el['image_url'] }}" alt="" style="max-width: 100%; max-height: {{ $el['height'] }}{{ $unit }};">
                @elseif ($type === 'logo' && $logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo" style="max-width: 100%; max-height: {{ $el['height'] }}{{ $unit }};">
                @elseif ($type === 'signature')
                    <span style="font-family: {!! $fontMap('cursive') !!}; font-size: 22{{ $unit }}; color: #334155;">{{ $instructor_name ?? '' }}</span>
                @endif
            </div>
        @else
            @php
                $fs = $el['font_size'] ?? 14;
                $lh = $el['line_height'] ?? 1.2;
                $lines = max(1, substr_count($content, "\n") + 1);
                $padTop = max(0, (($el['height'] ?? 20) - $fs * $lh * $lines) / 2);
            @endphp
            <div style="{{ $base }} box-sizing: border-box; padding-top: {{ round($padTop, 1) }}{{ $unit }};
                font-family: {!! $fontMap($el['font_family'] ?? 'serif') !!};
                font-size: {{ $fs }}{{ $unit }};
                font-weight: {{ $el['font_weight'] ?? 'normal' }};
                font-style: {{ $el['font_style'] ?? 'normal' }};
                color: {{ $el['color'] ?? '#0f172a' }};
                text-align: {{ $align }};
                letter-spacing: {{ $el['letter_spacing'] ?? 0 }}{{ $unit }};
                line-height: {{ $lh }};
                @if (!empty($el['background_color'])) background-color: {{ $el['background_color'] }}; @endif
                @if (!empty($el['border_color'])) border: {{ $el['border_width'] ?? 1 }}{{ $unit }} solid {{ $el['border_color'] }}; @endif
                @if (!empty($el['border_radius'])) border-radius: {{ $el['border_radius'] }}{{ $unit }}; @endif
                @if (!empty($el['border_bottom'])) border-bottom: {{ $el['border_bottom'] }}; @endif
                word-wrap: break-word;">{!! nl2br(e($content)) !!}</div>
        @endif
    @endforeach
</div>

