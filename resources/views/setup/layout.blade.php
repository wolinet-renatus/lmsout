<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="OUT LMS — Wolinet">
    <meta name="theme-color" content="#013ba6">
    <title>Setup Wizard &ndash; {{ config('app.name', 'OUT LMS') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/front/img/logo/brand-favicon-v3.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="stylesheet" href="{{ asset('setup/setup.css') }}">
</head>

<body>
    <div class="setup-shell">
        <header class="setup-header">
            <h1 class="setup-title">{{ config('app.name', 'OUT LMS') }} &mdash; Setup Wizard</h1>
            <p class="setup-subtitle">Follow the guided steps to configure your platform.</p>
        </header>

        <main class="setup-main">
            <div class="setup-steps">
                @foreach ($steps as $index => $step)
                    <div class="setup-step {{ $step['status'] }}">
                        <span class="setup-step-icon">
                            @if ($step['status'] === 'complete')
                                ✓
                            @else
                                {{ $index + 1 }}
                            @endif
                        </span>
                        <span class="setup-step-title">{{ $step['label'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="setup-panel">
                @yield('content')
            </div>

            <footer class="setup-footer">
                Need a fresh start? Remove the <code>{{ basename(config('setup.installed_marker')) }}</code> file
                at <code>{{ config('setup.installed_marker') }}</code> to re-run this wizard.
            </footer>
        </main>
    </div>
</body>

</html>

