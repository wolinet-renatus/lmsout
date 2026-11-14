<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $template->name ?? 'Certificate Preview' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Great+Vibes&family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #0f172a;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 16px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .cert-viewport { box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4); }
        {!! $template->custom_css ?? '' !!}
    </style>
</head>
<body>
    @include('certificates.partials.template-render', ['template' => $template, 'is_pdf' => false])
</body>
</html>
