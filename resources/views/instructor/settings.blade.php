@extends('layouts.instructor')

@section('title', 'Profile & Settings')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1">Profile &amp; Settings</h2>
        <p class="text-muted small mb-0">Manage your public profile, password, notification preferences, and account.</p>
    </div>
</div>

<!-- Mobile-first Compact Tab Navigation -->
<ul class="nav nav-pills gap-2 flex-nowrap overflow-auto pb-2 mb-3" id="instructorSettingsTabs" role="tablist">
    <li class="nav-item flex-shrink-0" role="presentation">
        <button class="nav-link active btn-sm px-3 py-2" id="profile-tab" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button" role="tab" aria-controls="tab-profile" aria-selected="true">
            <i class="fa-solid fa-user me-1"></i>Profile
        </button>
    </li>
    <li class="nav-item flex-shrink-0" role="presentation">
        <button class="nav-link btn-sm px-3 py-2" id="security-tab" data-bs-toggle="pill" data-bs-target="#tab-security" type="button" role="tab" aria-controls="tab-security" aria-selected="false">
            <i class="fa-solid fa-shield-halved me-1"></i>Security
        </button>
    </li>
    <li class="nav-item flex-shrink-0" role="presentation">
        <button class="nav-link btn-sm px-3 py-2" id="notifications-tab" data-bs-toggle="pill" data-bs-target="#tab-notifications" type="button" role="tab" aria-controls="tab-notifications" aria-selected="false">
            <i class="fa-solid fa-bell me-1"></i>Notifications
        </button>
    </li>
    <li class="nav-item flex-shrink-0" role="presentation">
        <button class="nav-link btn-sm px-3 py-2 text-danger" id="account-tab" data-bs-toggle="pill" data-bs-target="#tab-account" type="button" role="tab" aria-controls="tab-account" aria-selected="false">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>Account
        </button>
    </li>
</ul>

<div class="tab-content" id="instructorSettingsTabContent">
    <!-- Profile & Social Tab -->
    <div class="tab-pane fade show active" id="tab-profile" role="tabpanel" aria-labelledby="profile-tab">
        <form id="profileForm" action="{{ route('instructor.settings.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="bg-white border rounded-3 p-3 text-center">
                        <div class="position-relative d-inline-block mb-3">
                            <img
                                src="{{ $profile['avatar'] }}"
                                alt="{{ $profile['name'] }}"
                                id="avatarPreview"
                                class="rounded-circle border"
                                style="width: 88px; height: 88px; object-fit: cover; cursor: pointer;"
                            >
                        </div>
                        <div class="fw-bold text-dark">{{ $profile['name'] }}</div>
                        <div class="text-muted small mb-3">{{ $profile['email'] }}</div>
                        <input type="file" name="avatar" id="avatarInput" accept="image/*" class="d-none">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" id="changeAvatarBtn">
                            <i class="fa-solid fa-camera me-1"></i>{{ __('instructor.change_photo') }}
                        </button>
                        <div class="form-text mt-2">{{ __('instructor.avatar_hint') }}</div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="bg-white border rounded-3 p-3 p-md-4 mb-3">
                        <h6 class="fw-bold mb-3">{{ __('instructor.profile_info') }}</h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{{ __('instructor.full_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" value="{{ $profile['name'] }}" required placeholder="{{ __('instructor.enter_full_name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{{ __('instructor.email_address') }} <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control form-control-sm" value="{{ $profile['email'] }}" required placeholder="{{ __('instructor.enter_email') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{{ __('instructor.phone_number') }}</label>
                                <input type="tel" name="phone" class="form-control form-control-sm" value="{{ $profile['phone'] }}" placeholder="{{ __('instructor.enter_phone') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">{{ __('instructor.bio_label') }}</label>
                                <textarea name="bio" class="form-control form-control-sm" rows="3" placeholder="{{ __('instructor.bio_placeholder') }}">{{ $profile['bio'] }}</textarea>
                                <div class="form-text">{{ __('instructor.bio_hint') }}</div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-bold mb-2">{{ __('instructor.social_links') }}</h6>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label class="form-label small text-muted mb-1"><i class="fa-brands fa-linkedin me-1"></i>LinkedIn</label>
                                <input type="url" name="linkedin" class="form-control form-control-sm" value="{{ $profile['linkedin'] }}" placeholder="https://linkedin.com/in/username">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-muted mb-1"><i class="fa-brands fa-twitter me-1"></i>Twitter</label>
                                <input type="url" name="twitter" class="form-control form-control-sm" value="{{ $profile['twitter'] }}" placeholder="https://twitter.com/username">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-muted mb-1"><i class="fa-brands fa-facebook me-1"></i>Facebook</label>
                                <input type="url" name="facebook" class="form-control form-control-sm" value="{{ $profile['facebook'] }}" placeholder="https://facebook.com/username">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-muted mb-1"><i class="fa-brands fa-youtube me-1"></i>YouTube</label>
                                <input type="url" name="youtube" class="form-control form-control-sm" value="{{ $profile['youtube'] }}" placeholder="https://youtube.com/@username">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                            <button type="reset" class="btn btn-light btn-sm border">{{ __('instructor.cancel_btn') }}</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="fa-solid fa-floppy-disk me-1"></i>{{ __('instructor.save_changes') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Security Tab -->
    <div class="tab-pane fade" id="tab-security" role="tabpanel" aria-labelledby="security-tab">
        <div class="row">
            <div class="col-lg-8">
                <div class="bg-white border rounded-3 p-3 p-md-4">
                    <h6 class="fw-bold mb-1">{{ __('instructor.change_password') }}</h6>
                    <p class="text-muted small mb-3">{{ __('instructor.change_password_desc') }}</p>

                    <form id="passwordForm" action="{{ route('instructor.settings.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">{{ __('instructor.current_password') }} <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="password" name="current_password" class="form-control" required placeholder="{{ __('instructor.enter_current_password') }}" id="currentPassword">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('currentPassword', this)" aria-label="Toggle password visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{{ __('instructor.new_password') }} <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="password" name="new_password" class="form-control" required placeholder="{{ __('instructor.enter_new_password') }}" id="newPassword">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('newPassword', this)" aria-label="Toggle password visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">{{ __('instructor.min_8_chars') }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{{ __('instructor.confirm_password') }} <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="password" name="new_password_confirmation" class="form-control" required placeholder="{{ __('instructor.confirm_password') }}" id="confirmPassword">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirmPassword', this)" aria-label="Toggle password visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                            <button type="reset" class="btn btn-light btn-sm border">{{ __('instructor.cancel_btn') }}</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="fa-solid fa-key me-1"></i>{{ __('instructor.update_password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Tab -->
    <div class="tab-pane fade" id="tab-notifications" role="tabpanel" aria-labelledby="notifications-tab">
        <div class="row">
            <div class="col-lg-8">
                <div class="bg-white border rounded-3 p-3 p-md-4">
                    <h6 class="fw-bold mb-1">Notification Preferences</h6>
                    <p class="text-muted small mb-3">{{ __('instructor.account_settings_desc') }}</p>

                    <form action="{{ route('instructor.settings.notifications.update') }}" method="POST" id="instructorNotificationForm">
                        @csrf
                        @method('PUT')

                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-semibold small">{{ __('instructor.email_notifications') }}</div>
                                    <div class="text-muted small">{{ __('instructor.email_notifications_desc') }}</div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input type="hidden" name="email_notifications" value="0">
                                    <input class="form-check-input notification-toggle" type="checkbox" role="switch" name="email_notifications" value="1" {{ data_get($notificationSettings, 'email_notifications', true) ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-semibold small">{{ __('instructor.course_updates') }}</div>
                                    <div class="text-muted small">{{ __('instructor.course_updates_desc') }}</div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input type="hidden" name="course_updates" value="0">
                                    <input class="form-check-input notification-toggle" type="checkbox" role="switch" name="course_updates" value="1" {{ data_get($notificationSettings, 'course_updates', true) ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-semibold small">{{ __('instructor.review_notifications') }}</div>
                                    <div class="text-muted small">{{ __('instructor.review_notifications_desc') }}</div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input type="hidden" name="review_notifications" value="0">
                                    <input class="form-check-input notification-toggle" type="checkbox" role="switch" name="review_notifications" value="1" {{ data_get($notificationSettings, 'review_notifications', true) ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="list-group-item px-0 pb-0 d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-semibold small">{{ __('instructor.marketing_emails') }}</div>
                                    <div class="text-muted small">{{ __('instructor.marketing_emails_desc') }}</div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input type="hidden" name="marketing_emails" value="0">
                                    <input class="form-check-input notification-toggle" type="checkbox" role="switch" name="marketing_emails" value="1" {{ data_get($notificationSettings, 'marketing_emails', false) ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Account / Danger Zone Tab -->
    <div class="tab-pane fade" id="tab-account" role="tabpanel" aria-labelledby="account-tab">
        <div class="row">
            <div class="col-lg-8">
                <div class="bg-white border border-danger-subtle rounded-3 p-3 p-md-4">
                    <h6 class="fw-bold text-danger mb-1">{{ __('instructor.danger_zone') }}</h6>
                    <p class="text-muted small mb-3">{{ __('instructor.danger_zone_desc') }}</p>

                    @isset($latestDeletionRequest)
                        @php($deletionStatus = $latestDeletionRequest->status)
                        <div class="alert {{ $deletionStatus === 'pending' ? 'alert-warning' : ($deletionStatus === 'rejected' ? 'alert-danger' : 'alert-success') }} small mb-3" role="alert">
                            @if ($deletionStatus === 'pending')
                                <strong>{{ __('instructor.awaiting_approval_label') }}</strong> {{ __('instructor.deletion_request_submitted', ['date' => $latestDeletionRequest->created_at->format('M d, Y')]) }}
                            @elseif($deletionStatus === 'rejected')
                                <strong>{{ __('instructor.request_rejected_label') }}</strong>
                                {{ $latestDeletionRequest->note ?? __('instructor.contact_support_note') }}
                            @elseif($deletionStatus === 'approved')
                                <strong>{{ __('instructor.approved_label') }}</strong> {{ __('instructor.request_processed') }}
                            @endif
                        </div>
                    @endisset

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 bg-danger-subtle rounded-3">
                        <div>
                            <div class="fw-semibold text-dark small">{{ __('instructor.delete_account') }}</div>
                            <div class="text-muted small">{{ __('instructor.delete_account_desc') }}</div>
                        </div>
                        @if (isset($deletionStatus) && $deletionStatus === 'pending')
                            <button type="button" class="btn btn-outline-danger btn-sm" disabled>
                                <i class="fa-solid fa-clock me-1"></i>{{ __('instructor.awaiting_approval') }}
                            </button>
                        @else
                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteAccount()">
                                <i class="fa-solid fa-trash me-1"></i>{{ __('instructor.delete_account') }}
                            </button>
                        @endif
                    </div>

                    <form id="instructor-delete-account-form" action="{{ route('instructor.settings.account.destroy') }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const changeAvatarBtn = document.getElementById('changeAvatarBtn');

    if (avatarPreview && avatarInput) {
        avatarPreview.addEventListener('click', function () { avatarInput.click(); });
    }
    if (changeAvatarBtn && avatarInput) {
        changeAvatarBtn.addEventListener('click', function () { avatarInput.click(); });
    }
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function (e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (ev) {
                avatarPreview.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    const notificationForm = document.getElementById('instructorNotificationForm');
    if (notificationForm) {
        notificationForm.addEventListener('change', function (event) {
            if (event.target.classList.contains('notification-toggle')) {
                notificationForm.submit();
            }
        });
    }

    // Persist active settings tab across reloads/submits
    const TAB_KEY = 'out_instructor_settings_tab';
    const tabButtons = document.querySelectorAll('#instructorSettingsTabs button[data-bs-toggle="pill"]');
    try {
        const savedTab = localStorage.getItem(TAB_KEY);
        if (savedTab) {
            const btn = document.querySelector('#instructorSettingsTabs button[data-bs-target="' + savedTab + '"]');
            if (btn && window.bootstrap) {
                bootstrap.Tab.getOrCreateInstance(btn).show();
            }
        }
    } catch (e) {}

    tabButtons.forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            try { localStorage.setItem(TAB_KEY, btn.dataset.bsTarget); } catch (e) {}
        });
    });
});

function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function confirmDeleteAccount() {
    const form = document.getElementById('instructor-delete-account-form');
    if (!form) return;

    Swal.fire({
        title: @json(__('instructor.delete_account_confirm_title')),
        text: @json(__('instructor.delete_account_confirm_text')),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: @json(__('instructor.yes_delete_it')),
        cancelButtonText: @json(__('instructor.cancel_btn')),
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#1d4ed8'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Submitting request...',
                text: 'Please wait while we submit your account deletion request for admin approval.',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                    form.submit();
                }
            });
        }
    });
}
</script>
@endpush
