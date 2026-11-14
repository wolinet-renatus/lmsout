@extends('layouts.admin')

@section('title', 'Live Class Configuration')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Live Class Configuration</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-video mr-2"></i> Live Class Module Settings</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Settings
                    </a>
                </div>
                <form action="{{ route('admin.settings.live_class.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        {{-- Live Class Enable Toggle --}}
                        <div class="form-group mb-4">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="live_class_enabled"
                                    name="live_class_enabled" value="1"
                                    {{ old('live_class_enabled', $settings['live_class_enabled']) ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-dark" for="live_class_enabled">Enable Live Class Module</label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Globally enable or disable live streaming features (Zoom/Meet schedules, countdown banners, and live curriculum indicators) across the student and instructor panels.
                            </small>
                        </div>

                        <hr>

                        {{-- Reminders configuration --}}
                        <h6 class="font-weight-bold text-dark mb-3">Live Class Reminders</h6>
                        <p class="text-muted">Send automatic alert notifications to enrolled students before a live class starts.</p>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="live_class_reminder_minutes" class="font-weight-bold">Reminder Time (minutes before class)</label>
                                <input type="number" name="live_class_reminder_minutes" id="live_class_reminder_minutes"
                                    class="form-control @error('live_class_reminder_minutes') is-invalid @enderror"
                                    value="{{ old('live_class_reminder_minutes', $settings['live_class_reminder_minutes']) }}"
                                    min="5" max="1440" required>
                                @error('live_class_reminder_minutes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Students will receive dashboard alerts and notifications this many minutes before the live class starts. Recommended: 15 to 30 minutes.
                                </small>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold font-weight-bold">Notification Targets</label>
                                <input type="text" class="form-control" value="Enrolled Course Students" disabled>
                                <small class="form-text text-muted">
                                    Reminders will be dispatched to all students currently enrolled in the corresponding course.
                                </small>
                            </div>
                        </div>

                        <hr>

                        {{-- Multi-Provider Configuration (Zoom & Google Meet) --}}
                        <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-plug mr-1 text-primary"></i> Automated Multi-Provider Integration (Zoom &amp; Google Meet)</h6>
                        <p class="text-muted">Configure default live class provider and automated meeting generation credentials.</p>

                        <div class="form-row mb-3">
                            <div class="form-group col-md-4">
                                <label for="default_provider" class="font-weight-bold">Default Live Class Provider</label>
                                <select name="default_provider" id="default_provider" class="form-control">
                                    <option value="zoom" {{ old('default_provider', $settings['default_provider'] ?? 'zoom') === 'zoom' ? 'selected' : '' }}>Zoom Video Communications</option>
                                    <option value="google_meet" {{ old('default_provider', $settings['default_provider'] ?? 'zoom') === 'google_meet' ? 'selected' : '' }}>Google Meet</option>
                                    <option value="custom" {{ old('default_provider', $settings['default_provider'] ?? 'zoom') === 'custom' ? 'selected' : '' }}>Custom Meeting URL</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Zoom Provider Card --}}
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0"><i class="fas fa-video text-primary mr-1"></i> Zoom Integration</h4>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="zoom_enabled" name="zoom_enabled" value="1"
                                                {{ old('zoom_enabled', $settings['zoom_enabled'] ?? true) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="zoom_enabled">Enabled</label>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Zoom Account ID (Server-to-Server OAuth)</label>
                                            <input type="text" name="zoom_account_id" class="form-control"
                                                value="{{ old('zoom_account_id', $settings['zoom_account_id'] ?? '') }}" placeholder="Optional - leave blank for instant room provisioning">
                                        </div>
                                        <div class="form-group">
                                            <label>Zoom Client ID</label>
                                            <input type="text" name="zoom_client_id" class="form-control"
                                                value="{{ old('zoom_client_id', $settings['zoom_client_id'] ?? '') }}" placeholder="Zoom OAuth Client ID">
                                        </div>
                                        <div class="form-group mb-0">
                                            <label>Zoom Client Secret</label>
                                            <input type="password" name="zoom_client_secret" class="form-control"
                                                value="{{ old('zoom_client_secret', $settings['zoom_client_secret'] ?? '') }}" placeholder="Zoom OAuth Client Secret">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Google Meet Provider Card --}}
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0"><i class="fab fa-google text-success mr-1"></i> Google Meet Integration</h4>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="google_meet_enabled" name="google_meet_enabled" value="1"
                                                {{ old('google_meet_enabled', $settings['google_meet_enabled'] ?? true) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="google_meet_enabled">Enabled</label>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Google OAuth Client ID</label>
                                            <input type="text" name="google_client_id" class="form-control"
                                                value="{{ old('google_client_id', $settings['google_client_id'] ?? '') }}" placeholder="Optional - leave blank for instant Meet code generation">
                                        </div>
                                        <div class="form-group">
                                            <label>Google OAuth Client Secret</label>
                                            <input type="password" name="google_client_secret" class="form-control"
                                                value="{{ old('google_client_secret', $settings['google_client_secret'] ?? '') }}" placeholder="Google Client Secret">
                                        </div>
                                        <div class="form-group mb-0">
                                            <label>Google Calendar ID</label>
                                            <input type="text" name="google_calendar_id" class="form-control"
                                                value="{{ old('google_calendar_id', $settings['google_calendar_id'] ?? 'primary') }}" placeholder="primary">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
