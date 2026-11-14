@extends(!empty($isAdminBuilder) ? 'layouts.admin' : 'layouts.instructor')

@if(!empty($isAdminBuilder))
@section('title', isset($course) ? 'Edit Course' : 'Create Course')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.courses.index') }}">Courses</a></div>
        <div class="breadcrumb-item">{{ isset($course) ? 'Edit Course' : 'Create Course' }}</div>
    </div>
@endsection
@endif

@section(!empty($isAdminBuilder) ? 'main-content' : 'content')
    <section class="section-bg fix">
        <div class="container">
            <div class="course-builder-layout">
                <div class="course-builder-main">
                    <!-- Course Builder Header -->
                    <div class="course-builder-header">
                        <div class="builder-title">
                            <i class="fa-solid fa-arrow-left" onclick="window.history.back()"
                                style="cursor: pointer; margin-right: 15px;"></i>
                            <h2>
                                @isset($course)
                                    Edit Course
                                @else
                                    Course Builder
                                @endisset
                            </h2>
                        </div>
                        <div class="builder-progress">
                            <div class="progress-step active" data-step="1">
                                <span class="step-number">1</span>
                                <span class="step-label">Basics</span>
                            </div>
                            <div class="progress-step" data-step="2">
                                <span class="step-number">2</span>
                                <span class="step-label">Curriculum</span>
                            </div>
                            <div class="progress-step" data-step="3">
                                <span class="step-number">3</span>
                                <span class="step-label">Additional</span>
                            </div>
                        </div>
                        <div class="builder-actions d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="openBuilderModal('aiCourseBuilderModal')">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>AI Course Builder
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openBuilderModal('aiStudioModal')">
                                <i class="fa-solid fa-robot me-1"></i>AI Studio
                            </button>
                            <button type="button" class="btn-publish" id="publishBtn" style="display: none;">
                                <i class="fa-solid fa-rocket"></i>
                                Publish
                            </button>
                        </div>
                    </div>

                    @php
                        $formAction = !empty($isAdminBuilder)
                            ? (isset($course) ? route('admin.courses.update', $course->id) : route('admin.courses.store'))
                            : (isset($course) ? route('instructor.courses.update', $course->id) : route('instructor.courses.store'));
                    @endphp
                    <form id="courseBuilderForm" class="course-builder-form" method="POST"
                        action="{{ $formAction }}"
                        enctype="multipart/form-data">
                        @csrf
                        @isset($course)
                            @method('PUT')
                        @endisset

                        <!-- Step 1: Basics -->
                        <div class="builder-step active" data-step="1">
                            <div class="step-content">
                                <div class="step-left">
                                    <!-- Title -->
                                    <div class="form-group">
                                        <label>
                                            Title
                                            <i class="fa-solid fa-sparkles" style="color: #228ddd; margin-left: 5px;"></i>
                                        </label>
                                        <input type="text" name="title" class="form-control"
                                            placeholder="Enter course title"
                                            value="@isset($course){{ old('title', $course->title) }}@else{{ old('title') }}@endisset"
                                            required>
                                    </div>

                                    <!-- Description -->
                                    <div class="form-group">
                                        <label>
                                            Description
                                            <i class="fa-solid fa-sparkles" style="color: #228ddd; margin-left: 5px;"></i>
                                            <button type="button" class="btn btn-sm btn-outline-primary ml-2"
                                                id="generateDescriptionBtn" style="font-size: 0.7rem; padding: 2px 8px;">
                                                <i class="fa-solid fa-wand-magic-sparkles"></i> Generate with AI
                                            </button>
                                        </label>
                                        <div class="rich-editor-toolbar">
                                            <div class="toolbar-group">
                                                <select class="format-select">
                                                    <option value="Paragraph">Paragraph</option>
                                                    <option value="Heading 1">Heading 1</option>
                                                    <option value="Heading 2">Heading 2</option>
                                                    <option value="Heading 3">Heading 3</option>
                                                </select>
                                                <button type="button" class="toolbar-btn" data-command="bold"
                                                    title="Bold"><i class="fa-solid fa-bold"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="italic"
                                                    title="Italic"><i class="fa-solid fa-italic"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="underline"
                                                    title="Underline"><i class="fa-solid fa-underline"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="link"
                                                    title="Link"><i class="fa-solid fa-link"></i></button>
                                            </div>
                                            <div class="toolbar-group">
                                                <button type="button" class="toolbar-btn" data-command="align" data-value=""
                                                    title="Align Left"><i class="fa-solid fa-align-left"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="align"
                                                    data-value="center" title="Align Center"><i
                                                        class="fa-solid fa-align-center"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="align"
                                                    data-value="right" title="Align Right"><i
                                                        class="fa-solid fa-align-right"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="align"
                                                    data-value="justify" title="Justify"><i
                                                        class="fa-solid fa-align-justify"></i></button>
                                            </div>
                                            <div class="toolbar-group">
                                                <button type="button" class="toolbar-btn" data-command="list"
                                                    data-value="bullet" title="Bullet List"><i
                                                        class="fa-solid fa-list-ul"></i></button>
                                                <button type="button" class="toolbar-btn" data-command="list"
                                                    data-value="ordered" title="Numbered List"><i
                                                        class="fa-solid fa-list-ol"></i></button>

                                            </div>
                                        </div>
                                        <div class="rich-editor-wrapper">
                                            <div class="rich-editor"></div>
                                            <textarea name="description" class="rich-editor-textarea"
                                                style="display: none;"></textarea>
                                        </div>

                                    </div>

                                    <!-- Options Tabs -->
                                    <div class="options-tabs">
                                        <div class="tab-header">
                                            <button type="button" class="tab-btn active" data-tab="general">
                                                <i class="fa-solid fa-gear"></i>
                                                General
                                            </button>
                                        </div>
                                        <div class="tab-content">
                                            <div class="tab-pane active" id="general-tab">
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label>Maximum Student</label>
                                                        <input type="number" name="max_students" id="max_students"
                                                            class="form-control"
                                                            value="@isset($course){{ old('max_students', $course->max_students) }}@else{{ old('max_students', '20') }}@endisset"
                                                            min="1">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Difficulty Level</label>
                                                        <select name="difficulty" class="form-control">
                                                            <option value="beginner"
                                                                @isset($course){{ old('difficulty', $course->difficulty) === 'beginner' ? 'selected' : '' }}@else{{ old('difficulty') === 'beginner' ? 'selected' : '' }}@endisset>
                                                                Beginner</option>
                                                            <option value="intermediate"
                                                                @isset($course){{ old('difficulty', $course->difficulty) === 'intermediate' ? 'selected' : '' }}@else{{ old('difficulty') === 'intermediate' ? 'selected' : '' }}@endisset>
                                                                Intermediate</option>
                                                            <option value="advanced"
                                                                @isset($course){{ old('difficulty', $course->difficulty) === 'advanced' ? 'selected' : '' }}@else{{ old('difficulty') === 'advanced' ? 'selected' : '' }}@endisset>
                                                                Advanced</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="custom-control custom-control--checkbox">
                                                        <input type="checkbox" name="public_course" id="public_course"
                                                            @isset($course){{ old('public_course', $course->public_course) ? 'checked' : '' }}@else{{ old('public_course') ? 'checked' : '' }}@endisset>
                                                        <span class="control-indicator"></span>
                                                        <span class="control-label">Public Course</span>
                                                    </label>
                                                </div>
                                                <div class="form-group mt-4">
                                                    <label class="custom-control custom-control--checkbox">
                                                        <input type="checkbox" name="is_live_course" id="is_live_course"
                                                            @isset($course){{ old('is_live_course', $course->is_live_course) ? 'checked' : '' }}@else{{ old('is_live_course') ? 'checked' : '' }}@endisset>
                                                        <span class="control-indicator"></span>
                                                        <span class="control-label">
                                                            Live Course
                                                            <span style="display:inline-block;background:#e53e3e;color:#fff;font-size:0.65rem;font-weight:700;padding:1px 6px;border-radius:4px;margin-left:5px;letter-spacing:0.5px;vertical-align:middle;">LIVE</span>
                                                        </span>
                                                    </label>
                                                    <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Show a Live badge on course cards in the frontend and app.</small>
                                                </div>

                                                <hr class="my-3">
                                                @php
                                                    $dMode = old('delivery_mode', $course->delivery_mode ?? 'online');
                                                    $isCampusMode = in_array($dMode, ['face_to_face', 'hybrid'], true);
                                                @endphp
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label>Delivery Mode</label>
                                                        <select name="delivery_mode" id="delivery_mode" class="form-control">
                                                            <option value="online" {{ $dMode === 'online' ? 'selected' : '' }}>Online (Self-Paced / Direct Checkout)</option>
                                                            <option value="face_to_face" {{ $dMode === 'face_to_face' ? 'selected' : '' }}>Face-to-Face (Campus / Short Course)</option>
                                                            <option value="hybrid" {{ $dMode === 'hybrid' ? 'selected' : '' }}>Hybrid (Campus + Online LMS)</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Content Type</label>
                                                        @php $cType = old('course_type', $course->course_type ?? 'course'); @endphp
                                                        <select name="course_type" class="form-control">
                                                            <option value="course" {{ $cType === 'course' ? 'selected' : '' }}>Course</option>
                                                            <option value="quiz" {{ $cType === 'quiz' ? 'selected' : '' }}>Quiz</option>
                                                            <option value="test_series" {{ $cType === 'test_series' ? 'selected' : '' }}>Test Series (Quizzes Only)</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Drip Content</label>
                                                        @php $dripVal = old('drip_enabled', $course->drip_enabled ?? false); @endphp
                                                        <select name="drip_enabled" class="form-control">
                                                            <option value="0" {{ !$dripVal ? 'selected' : '' }}>No</option>
                                                            <option value="1" {{ $dripVal ? 'selected' : '' }}>Yes</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div id="campusDeliveryFields" class="p-3 rounded border mb-3" style="background:#f8fafd; display: {{ $isCampusMode ? 'block' : 'none' }};">
                                                    <h6 class="text-primary font-weight-bold mb-3"><i class="fa-solid fa-building-columns mr-1"></i> Campus / Short-Course Options</h6>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-4">
                                                            <label>Default Branch</label>
                                                            <select name="default_branch_id" class="form-control">
                                                                <option value="">-- Any Branch --</option>
                                                                @foreach($campusBranches ?? [] as $cb)
                                                                    <option value="{{ $cb->id }}" {{ (string) old('default_branch_id', $course->default_branch_id ?? '') === (string) $cb->id ? 'selected' : '' }}>{{ $cb->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <label>Default Venue</label>
                                                            <select name="default_venue_id" class="form-control">
                                                                <option value="">-- Any Venue --</option>
                                                                @foreach($campusVenues ?? [] as $cv)
                                                                    <option value="{{ $cv->id }}" {{ (string) old('default_venue_id', $course->default_venue_id ?? '') === (string) $cv->id ? 'selected' : '' }}>{{ $cv->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <label>Duration (e.g. 4 weeks)</label>
                                                            <input type="text" name="duration_label" class="form-control" placeholder="e.g. 4 weeks"
                                                                value="{{ old('duration_label', $course->duration_label ?? '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-4">
                                                            <label>Attendance Threshold (%)</label>
                                                            <input type="number" name="attendance_threshold" class="form-control" min="0" max="100"
                                                                value="{{ old('attendance_threshold', $course->attendance_threshold ?? 75) }}">
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <label class="d-block">Eligibility Discount (Staff/OUT)</label>
                                                            <input type="hidden" name="has_discount" value="0">
                                                            <label class="custom-control custom-control--checkbox mt-1">
                                                                <input type="checkbox" name="has_discount" value="1"
                                                                    {{ old('has_discount', $course->has_discount ?? false) ? 'checked' : '' }}>
                                                                <span class="control-indicator"></span>
                                                                <span class="control-label">Allow Course Discount</span>
                                                            </label>
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <label>Discount Amount</label>
                                                            <input type="number" step="0.01" min="0" name="discount_amount" class="form-control"
                                                                value="{{ old('discount_amount', $course->discount_amount ?? 0) }}">
                                                        </div>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-6 mb-0">
                                                            <input type="hidden" name="allow_online_f2f_payment" value="0">
                                                            <label class="custom-control custom-control--checkbox">
                                                                <input type="checkbox" name="allow_online_f2f_payment" value="1"
                                                                    {{ old('allow_online_f2f_payment', $course->allow_online_f2f_payment ?? true) ? 'checked' : '' }}>
                                                                <span class="control-indicator"></span>
                                                                <span class="control-label">Allow Online Payment Alongside Offline/Control No.</span>
                                                            </label>
                                                        </div>
                                                        @if(!empty($isAdminBuilder))
                                                            <div class="form-group col-md-6 mb-0">
                                                                <input type="hidden" name="f2f_commission_enabled" value="0">
                                                                <label class="custom-control custom-control--checkbox">
                                                                    <input type="checkbox" name="f2f_commission_enabled" value="1"
                                                                        {{ old('f2f_commission_enabled', $course->f2f_commission_enabled ?? false) ? 'checked' : '' }}>
                                                                    <span class="control-indicator"></span>
                                                                    <span class="control-label">Enable Instructor Commission Split (otherwise 100% institutional)</span>
                                                                </label>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-row">
                                                    @if(!empty($isAdminBuilder))
                                                        <div class="form-group">
                                                            <label>Assign Instructor</label>
                                                            <select name="instructor_id" class="form-control">
                                                                @foreach($instructorsList as $inst)
                                                                    <option value="{{ $inst->id }}" {{ (int) old('instructor_id', $course->instructor_id ?? auth()->id()) === (int) $inst->id ? 'selected' : '' }}>
                                                                        {{ $inst->name }} ({{ $inst->email }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    @endif
                                                    <div class="form-group">
                                                        <label>Assistant Instructor</label>
                                                        <select name="assistant_instructor_id" class="form-control" {{ empty($canManagePricingAndTeam) ? 'disabled' : '' }}>
                                                            <option value="">Select</option>
                                                            @foreach($instructorsList as $inst)
                                                                <option value="{{ $inst->id }}" {{ (int) old('assistant_instructor_id', $course->assistant_instructor_id ?? 0) === (int) $inst->id ? 'selected' : '' }}>
                                                                    {{ $inst->name }} ({{ $inst->email }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="mt-3 p-3 border rounded bg-light">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <strong class="small"><i class="fa-solid fa-user-group me-1 text-primary"></i>Co-instructors</strong>
                                                        @if(!empty($canManagePricingAndTeam))
                                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="addCoInstructorRow()">
                                                                <i class="fa-solid fa-plus me-1"></i>Add
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <p class="text-muted mb-2" style="font-size:0.75rem;">
                                                        Primary keeps 100% of the instructor share. Co-instructors appear on the course page and get their share of the instructor earnings from every sale. With "Can edit" they can change the curriculum, quizzes, assignments, FAQs and live classes — never the price, the co-instructor list or delete the course.
                                                    </p>
                                                    <div id="coInstructorsList">
                                                        @php $existingCos = old('co_instructors', $courseDataArray['co_instructors'] ?? []); @endphp
                                                        @foreach($existingCos as $idx => $co)
                                                            <div class="row g-2 align-items-center mb-2 co-instructor-row">
                                                                <div class="col-md-5">
                                                                    <input type="email" name="co_instructors[{{ $idx }}][email]" class="form-control form-control-sm" placeholder="name@example.com" value="{{ $co['email'] ?? '' }}" {{ empty($canManagePricingAndTeam) ? 'readonly' : '' }}>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="input-group input-group-sm">
                                                                        <input type="number" name="co_instructors[{{ $idx }}][revenue_share]" class="form-control" min="0" max="100" step="0.5" value="{{ $co['revenue_share'] ?? 0 }}" {{ empty($canManagePricingAndTeam) ? 'readonly' : '' }}>
                                                                        <span class="input-group-text">%</span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label class="form-check-label small d-flex align-items-center gap-1">
                                                                        <input type="checkbox" name="co_instructors[{{ $idx }}][can_edit]" value="1" class="form-check-input mt-0" {{ !empty($co['can_edit']) ? 'checked' : '' }} {{ empty($canManagePricingAndTeam) ? 'disabled' : '' }}>
                                                                        Can edit
                                                                    </label>
                                                                </div>
                                                                @if(!empty($canManagePricingAndTeam))
                                                                    <div class="col-md-1 text-end">
                                                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.closest('.co-instructor-row').remove()"><i class="fa-solid fa-xmark"></i></button>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <small class="text-muted d-block" style="font-size:0.72rem;">The person needs an approved instructor account on this site. Revenue share is the % of the instructor earnings from each sale.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="step-right">
                                    <!-- Visibility -->
                                    <div class="form-group">
                                        <label>Visibility</label>
                                        <select name="visibility" class="form-control">
                                            <option value="public"
                                                @isset($course){{ old('visibility', $course->visibility) === 'public' ? 'selected' : '' }}@else{{ old('visibility') === 'public' ? 'selected' : '' }}@endisset>
                                                <i class="fa-solid fa-eye"></i> Public
                                            </option>
                                            <option value="private"
                                                @isset($course){{ old('visibility', $course->visibility) === 'private' ? 'selected' : '' }}@else{{ old('visibility') === 'private' ? 'selected' : '' }}@endisset>
                                                Private</option>
                                            <option value="draft"
                                                @isset($course){{ old('visibility', $course->visibility) === 'draft' ? 'selected' : '' }}@else{{ old('visibility') === 'draft' ? 'selected' : '' }}@endisset>
                                                Draft</option>
                                        </select>
                                    </div>

                                    <!-- Schedule -->
                                    <div class="form-group">
                                        <label class="custom-control custom-control--checkbox">
                                            <input type="checkbox" name="schedule_enabled" id="schedule_enabled"
                                                @isset($course){{ old('schedule_enabled', $course->schedule_enabled) ? 'checked' : '' }}@else{{ old('schedule_enabled') ? 'checked' : '' }}@endisset>
                                            <span class="control-indicator"></span>
                                            <span class="control-label">Schedule</span>
                                        </label>
                                        @php
                                            $scheduleDisplayStyle = 'none';
                                            $scheduleDateValue = old('schedule_date', '');
                                            if (isset($course)) {
                                                $scheduleEnabled = old('schedule_enabled', $course->schedule_enabled);
                                                $scheduleDisplayStyle = $scheduleEnabled ? 'block' : 'none';
                                                if ($course->schedule_date) {
                                                    $scheduleDateValue = old(
                                                        'schedule_date',
                                                        $course->schedule_date->format('Y-m-d\TH:i'),
                                                    );
                                                }
                                            } else {
                                                $scheduleEnabled = old('schedule_enabled', false);
                                                $scheduleDisplayStyle = $scheduleEnabled ? 'block' : 'none';
                                            }
                                        @endphp
                                        <div id="schedule-date-picker"
                                            style="display: {{ $scheduleDisplayStyle }}; margin-top: 12px;">
                                            <label
                                                style="font-size: 14px; font-weight: 600; color: var(--header); margin-bottom: 8px; display: block;">Select
                                                Date & Time</label>
                                            <input type="datetime-local" name="schedule_date" id="schedule_date"
                                                class="form-control" style="width: 100%;" value="{{ $scheduleDateValue }}">
                                        </div>
                                    </div>

                                    <!-- Featured Image -->
                                    <div class="form-group">
                                        <label>
                                            Featured Image
                                            <i class="fa-solid fa-sparkles" style="color: #228ddd; margin-left: 5px;"></i>
                                        </label>
                                        <div class="image-upload-area">
                                            <input type="file" name="featured_image" id="featuredImage" accept="image/*"
                                                style="display: none;">
                                            <div class="image-preview" id="featuredImagePreview">
                                                <i class="fa-solid fa-image"></i>
                                                <span>Click to upload</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Intro Video -->
                                    <div class="form-group">
                                        <label>Intro Video</label>
                                        <div class="video-upload-area">
                                            <input type="file" name="intro_video" id="introVideo" accept="video/*"
                                                style="display: none;">
                                            <div class="video-preview" id="introVideoPreview">
                                                <i class="fa-solid fa-video"></i>
                                                <span>Upload video</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pricing Model -->
                                    <div class="form-group">
                                        <label>Pricing Model</label>
                                        <div class="pricing-options">
                                            <label class="custom-control custom-control--radio">
                                                <input type="radio" name="pricing_model" value="free"
                                                    @isset($course){{ old('pricing_model', $course->pricing_model) === 'free' ? 'checked' : '' }}@else{{ old('pricing_model', 'free') === 'free' ? 'checked' : '' }}@endisset>
                                                <span class="control-indicator"></span>
                                                <span class="control-label">Free</span>
                                            </label>
                                            <label class="custom-control custom-control--radio">
                                                <input type="radio" name="pricing_model" value="paid"
                                                    @isset($course){{ old('pricing_model', $course->pricing_model) === 'paid' ? 'checked' : '' }}@else{{ old('pricing_model') === 'paid' ? 'checked' : '' }}@endisset>
                                                <span class="control-indicator"></span>
                                                <span class="control-label">Paid</span>
                                            </label>
                                        </div>
                                        <div class="pricing-fields" id="pricingFields"
                                            style="display: @isset($course){{ old('pricing_model', $course->pricing_model) === 'paid' ? 'block' : 'none' }}@else{{ old('pricing_model') === 'paid' ? 'block' : 'none' }}@endisset;">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Regular Price</label>
                                                    <div class="price-input-wrapper">
                                                        <span class="currency">$</span>
                                                        <input type="number" name="regular_price" class="form-control"
                                                            step="0.01" min="0" placeholder="0.00"
                                                            value="@isset($course){{ old('regular_price', $course->regular_price) }}@else{{ old('regular_price') }}@endisset">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Sale Price</label>
                                                    <div class="price-input-wrapper">
                                                        <span class="currency">$</span>
                                                        <input type="number" name="sale_price" class="form-control"
                                                            step="0.01" min="0" placeholder="0.00"
                                                            value="@isset($course){{ old('sale_price', $course->sale_price) }}@else{{ old('sale_price') }}@endisset">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Curriculum -->
                        <div class="builder-step" data-step="2">
                            <div class="step-header">
                                <i class="fa-solid fa-arrow-left" onclick="changeStep(1)"
                                    style="cursor: pointer; margin-right: 10px;"></i>
                                <h3>Curriculum</h3>
                            </div>

                            <div class="curriculum-container" id="curriculumContainer">
                                <!-- Topics will be added here dynamically -->
                            </div>

                            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mt-3">
                                <button type="button" class="btn-add-topic" onclick="addTopic()">
                                    <i class="fa-solid fa-plus"></i>
                                    Add Topic
                                </button>
                                <div class="input-group input-group-sm" style="max-width: 460px;">
                                    <span class="input-group-text bg-white"><i class="fa-brands fa-youtube text-danger"></i></span>
                                    <input type="url" id="youtubePlaylistUrl" class="form-control" placeholder="Paste YouTube playlist URL to import lessons...">
                                    <button type="button" class="btn btn-outline-danger" id="importYoutubeBtn">Import Playlist</button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Additional -->
                        <div class="builder-step" data-step="3">
                            <div class="step-header">
                                <i class="fa-solid fa-arrow-left" onclick="changeStep(2)"
                                    style="cursor: pointer; margin-right: 10px;"></i>
                                <h3>Additional Settings</h3>
                            </div>

                            <div class="additional-settings">
                                <div class="form-group">
                                    <label>Course Tags</label>
                                    <input type="text" name="tags" id="course_tags_input" class="form-control"
                                        placeholder="Enter tags separated by commas"
                                        value="@isset($course){{ old('tags', $course->tags) }}@else{{ old('tags') }}@endisset">
                                    <small class="form-hint">Separate tags with commas</small>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>SEO Title</label>
                                        <input type="text" name="seo_title" id="seo_title_input" class="form-control"
                                            value="{{ old('seo_title', $course->seo_title ?? '') }}" placeholder="Search engine title">
                                    </div>
                                    <div class="form-group">
                                        <label>SEO Tags</label>
                                        <input type="text" name="seo_tags" id="seo_tags_input" class="form-control"
                                            value="{{ old('seo_tags', $course->seo_tags ?? '') }}" placeholder="keyword1, keyword2">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>SEO Description</label>
                                    <textarea name="seo_description" id="seo_description_input" class="form-control" rows="2" placeholder="Concise search meta description">{{ old('seo_description', $course->seo_description ?? '') }}</textarea>
                                </div>

                                <div class="form-group border rounded p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="mb-0"><i class="fa-solid fa-circle-question me-1 text-primary"></i>Course FAQs</label>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="addFaqRow()">
                                            <i class="fa-solid fa-plus me-1"></i>Add FAQ
                                        </button>
                                    </div>
                                    <div id="courseFaqsList">
                                        @php $existingFaqs = old('faqs', $courseDataArray['faqs'] ?? []); @endphp
                                        @foreach($existingFaqs as $fIdx => $faq)
                                            <div class="border rounded bg-white p-2 mb-2 faq-row">
                                                <div class="d-flex justify-content-between gap-2 mb-1">
                                                    <input type="text" name="faqs[{{ $fIdx }}][question]" class="form-control form-control-sm" placeholder="Question" value="{{ $faq['question'] ?? '' }}">
                                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.closest('.faq-row').remove()"><i class="fa-solid fa-xmark"></i></button>
                                                </div>
                                                <textarea name="faqs[{{ $fIdx }}][answer]" class="form-control form-control-sm" rows="2" placeholder="Answer">{{ $faq['answer'] ?? '' }}</textarea>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Course Category</label>
                                    <select name="course_category_id" id="course_category_id" class="form-control">
                                        <option value="">Select Category</option>
                                        @foreach ($categoryOptions as $option)
                                            <option value="{{ $option->id }}"
                                                {{ (string) $selectedCategoryId === (string) $option->id ? 'selected' : '' }}>
                                                {{ $option->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($selectedCategoryLabel && !$selectedCategoryId)
                                        <small class="text-muted d-block mt-1">Current:
                                            {{ $selectedCategoryLabel }}</small>
                                    @endif
                                    @if ($categoryOptions->isEmpty())
                                        <small class="text-muted d-block mt-1">No categories available yet. Please contact
                                            the administrator.</small>
                                    @endif
                                    <input type="hidden" name="category_label" id="category_label"
                                        value="{{ $selectedCategoryLabel }}">
                                </div>

                                <div class="form-group">
                                    <label>Course Language</label>
                                    <select name="course_language_id" id="course_language_id" class="form-control">
                                        <option value="">Select Language</option>
                                        @foreach ($languageOptions as $option)
                                            <option value="{{ $option->id }}"
                                                {{ (string) $selectedLanguageId === (string) $option->id ? 'selected' : '' }}>
                                                {{ $option->name }}@if ($option->native_name)
                                                    ({{ $option->native_name }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($selectedLanguageLabel && !$selectedLanguageId)
                                        <small class="text-muted d-block mt-1">Current:
                                            {{ $selectedLanguageLabel }}</small>
                                    @endif
                                    @if ($languageOptions->isEmpty())
                                        <small class="text-muted d-block mt-1">No languages available yet. Please contact
                                            the administrator.</small>
                                    @endif
                                    <input type="hidden" name="language_label" id="language_label"
                                        value="{{ $selectedLanguageLabel }}">
                                </div>

                                <div class="form-group">
                                    <label>Course Requirements</label>
                                    <textarea name="requirements" class="form-control" rows="4"
                                        placeholder="List any prerequisites or requirements...">
                            @isset($course)
                            {{ old('requirements', $course->requirements) }}@else{{ old('requirements') }}
                            @endisset
                            </textarea>
                                </div>

                                <div class="form-group">
                                    <label>Learning Objectives</label>
                                    <textarea name="objectives" class="form-control" rows="4"
                                        placeholder="What will students learn?">
                            @isset($course)
                            {{ old('objectives', $course->objectives) }}@else{{ old('objectives') }}
                            @endisset
                            </textarea>
                                    <small class="form-hint">Separate objectives with new lines</small>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="builder-navigation">
                            <button type="button" class="btn-nav btn-prev" onclick="previousStep()" style="display: none;">
                                <i class="fa-solid fa-chevron-left"></i>
                                Previous
                            </button>
                            <button type="button" class="btn-nav btn-next" onclick="nextStep()">
                                Next
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    @push('styles')
        @if(!empty($isAdminBuilder))
            <link rel="stylesheet" href="{{ asset('assets/front/css/instructor/style.css') }}">
            <style>
                /* Admin Bootstrap 4 + Stisla compatibility for shared Course Builder */
                .section-bg { background: transparent; padding: 0; }
                .course-builder-layout { padding: 0; margin: 0; }
                .course-builder-main { width: 100%; background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                .course-builder-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #e9ecef; }
                .gap-2 { gap: 0.5rem !important; }
                .me-1 { margin-right: 0.25rem !important; }
                .ms-1 { margin-left: 0.25rem !important; }
                .btn-close { background: transparent; border: 0; font-size: 1.25rem; line-height: 1; opacity: .6; cursor: pointer; }
                .btn-close::before { content: "\00d7"; }
                .form-select { display: block; width: 100%; height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; font-size: .875rem; color: #495057; background-color: #fff; border: 1px solid #ced4da; border-radius: .25rem; }
                .form-select-sm { height: calc(1.5em + .5rem + 2px); padding: .25rem .5rem; font-size: .8125rem; }
            </style>
        @endif
        <style>
            .rich-editor-wrapper {
                position: relative;
            }

            .rich-editor-loading-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(229, 70, 192, 0.15);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 10;
                backdrop-filter: blur(2px);
                border-radius: 8px;
            }

            .ai-spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #228ddd;
                /* Using the theme secondary color */
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-bottom: 15px;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }

            .ai-loading-text {
                color: #333;
                font-weight: 500;
                font-size: 14px;
                animation: pulse 1.5s infinite;
            }

            @keyframes pulse {
                0% {
                    opacity: 0.6;
                }

                50% {
                    opacity: 1;
                }

                100% {
                    opacity: 0.6;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/front/js/quill.js') }}"></script>
        <link href="{{ asset('assets/front/css/quill.snow.css') }}" rel="stylesheet">
        <script src="{{ asset('assets/front/js/sortable.min.js') }}"></script>
        @isset($courseDataArray)
            <script>
                const courseData = {!! json_encode($courseDataArray) !!};
            </script>
        @endisset
        <script src="{{ asset('assets/front/js/instructor/course-builder.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const categorySelect = document.getElementById('course_category_id');
                const categoryLabel = document.getElementById('category_label');
                const languageSelect = document.getElementById('course_language_id');
                const languageLabel = document.getElementById('language_label');

                if (categorySelect && categoryLabel) {
                    categorySelect.addEventListener('change', function () {
                        const selected = this.selectedOptions[0];
                        categoryLabel.value = selected ? selected.text.trim() : '';
                    });
                }

                if (languageSelect && languageLabel) {
                    languageSelect.addEventListener('change', function () {
                        const selected = this.selectedOptions[0];
                        languageLabel.value = selected ? selected.text.trim() : '';
                    });
                }

                // AI Description Generation
                const generateBtn = document.getElementById('generateDescriptionBtn');
                if (generateBtn) {
                    generateBtn.addEventListener('click', function () {
                        const titleInput = document.querySelector('input[name="title"]');
                        const title = titleInput.value.trim();

                        if (!title) {
                            alert('Please enter a course title first.');
                            titleInput.focus();
                            return;
                        }

                        // Show button loading state
                        const originalText = generateBtn.innerHTML;
                        generateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
                        generateBtn.disabled = true;

                        // Show editor loading overlay
                        const editorWrapper = document.querySelector('.rich-editor-wrapper');
                        let overlay = editorWrapper.querySelector('.rich-editor-loading-overlay');
                        if (!overlay) {
                            overlay = document.createElement('div');
                            overlay.className = 'rich-editor-loading-overlay';
                            overlay.innerHTML = `
                                                                        <div class="ai-spinner"></div>
                                                                        <div class="ai-loading-text">Generating Description with AI...</div>
                                                                    `;
                            editorWrapper.appendChild(overlay);
                        }
                        overlay.style.display = 'flex';

                        fetch('{{ route('instructor.courses.generate-description') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ title: title })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update Quill editor
                                    if (window.quillEditor) {
                                        // Use Quill API to set content
                                        window.quillEditor.clipboard.dangerouslyPasteHTML(data.description);
                                    } else {
                                        // Fallback to DOM manipulation
                                        const quill = document.querySelector('.ql-editor');
                                        if (quill) {
                                            quill.innerHTML = data.description;
                                        }
                                    }

                                    // Update textarea
                                    const textarea = document.querySelector('textarea[name="description"]');
                                    if (textarea) {
                                        textarea.value = data.description;
                                    }
                                } else {
                                    alert(data.message || 'Failed to generate description.');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while generating the description.');
                            })
                            .finally(() => {
                                // Reset button
                                generateBtn.innerHTML = originalText;
                                generateBtn.disabled = false;
                                // Hide overlay
                                if (overlay) {
                                    overlay.style.display = 'none';
                                }
                            });
                    });
                }
            });

            window.courseBuilderRoutes = {
                aiBuilder: "{{ !empty($isAdminBuilder) ? route('admin.courses.ai-builder') : route('instructor.courses.ai-builder') }}",
                aiStudio: "{{ !empty($isAdminBuilder) ? route('admin.courses.ai-studio') : route('instructor.courses.ai-studio') }}",
                importYoutube: "{{ !empty($isAdminBuilder) ? route('admin.courses.import-youtube-playlist') : route('instructor.courses.import-youtube-playlist') }}",
                csrf: "{{ csrf_token() }}"
            };

            function openBuilderModal(id) {
                const el = document.getElementById(id);
                if (!el) return;
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(el).show();
                } else if (window.jQuery && typeof jQuery(el).modal === 'function') {
                    jQuery(el).modal('show');
                }
            }

            function closeBuilderModal(id) {
                const el = document.getElementById(id);
                if (!el) return;
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getInstance(el)?.hide();
                } else if (window.jQuery && typeof jQuery(el).modal === 'function') {
                    jQuery(el).modal('hide');
                }
            }

            let coInstructorIdx = document.querySelectorAll('.co-instructor-row').length;
            function addCoInstructorRow() {
                const container = document.getElementById('coInstructorsList');
                if (!container) return;
                const row = document.createElement('div');
                row.className = 'row g-2 align-items-center mb-2 co-instructor-row';
                row.innerHTML = `
                    <div class="col-md-5"><input type="email" name="co_instructors[${coInstructorIdx}][email]" class="form-control form-control-sm" placeholder="name@example.com"></div>
                    <div class="col-md-3"><div class="input-group input-group-sm"><input type="number" name="co_instructors[${coInstructorIdx}][revenue_share]" class="form-control" min="0" max="100" step="0.5" value="0"><span class="input-group-text">%</span></div></div>
                    <div class="col-md-3"><label class="form-check-label small d-flex align-items-center gap-1"><input type="checkbox" name="co_instructors[${coInstructorIdx}][can_edit]" value="1" class="form-check-input mt-0">Can edit</label></div>
                    <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.closest('.co-instructor-row').remove()"><i class="fa-solid fa-xmark"></i></button></div>
                `;
                container.appendChild(row);
                coInstructorIdx++;
            }

            let faqIdx = document.querySelectorAll('.faq-row').length;
            function addFaqRow(q = '', a = '') {
                const container = document.getElementById('courseFaqsList');
                if (!container) return;
                const row = document.createElement('div');
                row.className = 'border rounded bg-white p-2 mb-2 faq-row';
                row.innerHTML = `
                    <div class="d-flex justify-content-between gap-2 mb-1">
                        <input type="text" name="faqs[${faqIdx}][question]" class="form-control form-control-sm" placeholder="Question" value="${q.replace(/"/g, '&quot;')}">
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.closest('.faq-row').remove()"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <textarea name="faqs[${faqIdx}][answer]" class="form-control form-control-sm" rows="2" placeholder="Answer">${a}</textarea>
                `;
                container.appendChild(row);
                faqIdx++;
            }

            document.getElementById('importYoutubeBtn')?.addEventListener('click', async function () {
                const urlInput = document.getElementById('youtubePlaylistUrl');
                const url = urlInput?.value.trim();
                if (!url) { alert('Please paste a YouTube playlist URL first.'); return; }
                const btn = this;
                btn.disabled = true;
                btn.textContent = 'Importing...';
                try {
                    const res = await fetch(window.courseBuilderRoutes.importYoutube, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.courseBuilderRoutes.csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ playlist_url: url })
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        alert(data.message || 'Could not import playlist.');
                    } else if (typeof topics !== 'undefined') {
                        topics.push({
                            id: null,
                            title: data.title || 'YouTube Playlist',
                            description: 'Imported from YouTube playlist',
                            lessons: (data.videos || []).map(v => ({
                                id: null,
                                title: v.title,
                                description: '',
                                video_type: 'url',
                                video_url: v.video_url,
                                duration: (v.duration_minutes || 10) + 'm',
                                is_preview: !!v.is_preview,
                                is_live: false
                            })),
                            quizzes: [],
                            assignments: []
                        });
                        if (typeof renderTopics === 'function') renderTopics();
                        else if (typeof renderCurriculum === 'function') renderCurriculum();
                        urlInput.value = '';
                        alert(data.message || 'Playlist imported into curriculum!');
                    }
                } catch (e) {
                    alert('Error importing YouTube playlist.');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Import Playlist';
                }
            });

            let lastAiCourseDraft = null;
            async function runAiCourseBuilder() {
                const promptEl = document.getElementById('aiCoursePrompt');
                const docEl = document.getElementById('aiCourseDoc');
                const previewEl = document.getElementById('aiCoursePreview');
                const applyBtn = document.getElementById('applyAiCourseBtn');
                const prompt = promptEl?.value.trim();
                if (!prompt) { alert('Enter a course prompt.'); return; }
                const fd = new FormData();
                fd.append('prompt', prompt);
                if (docEl?.files?.[0]) fd.append('document', docEl.files[0]);
                previewEl.innerHTML = '<div class="text-muted small"><i class="fa-solid fa-spinner fa-spin me-1"></i>Drafting full course...</div>';
                try {
                    const res = await fetch(window.courseBuilderRoutes.aiBuilder, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': window.courseBuilderRoutes.csrf, 'Accept': 'application/json' },
                        body: fd
                    });
                    const data = await res.json();
                    if (!data.success || !data.draft) { previewEl.innerHTML = '<div class="text-danger small">Failed to draft course.</div>'; return; }
                    lastAiCourseDraft = data.draft;
                    previewEl.innerHTML = `
                        <div class="border rounded p-2 bg-light small">
                            <div><strong>Title:</strong> ${data.draft.title || ''}</div>
                            <div class="mt-1"><strong>SEO Tags:</strong> ${data.draft.seo_tags || ''}</div>
                            <div class="mt-1"><strong>Modules:</strong> ${(data.draft.topics || []).length}</div>
                        </div>`;
                    applyBtn.disabled = false;
                } catch (e) {
                    previewEl.innerHTML = '<div class="text-danger small">Error connecting to AI Course Builder.</div>';
                }
            }

            function applyAiCourseDraft() {
                if (!lastAiCourseDraft) return;
                const d = lastAiCourseDraft;
                const titleInput = document.querySelector('input[name="title"]');
                if (titleInput && d.title) titleInput.value = d.title;
                if (window.quillEditor && d.description) window.quillEditor.root.innerHTML = d.description;
                const descArea = document.querySelector('textarea[name="description"]');
                if (descArea && d.description) descArea.value = d.description;
                const reqArea = document.querySelector('textarea[name="requirements"]');
                if (reqArea && Array.isArray(d.requirements)) reqArea.value = d.requirements.join('\n');
                const objArea = document.querySelector('textarea[name="objectives"]');
                if (objArea && Array.isArray(d.outcomes)) objArea.value = d.outcomes.join('\n');
                if (document.getElementById('seo_title_input') && d.seo_title) document.getElementById('seo_title_input').value = d.seo_title;
                if (document.getElementById('seo_description_input') && d.seo_description) document.getElementById('seo_description_input').value = d.seo_description;
                if (document.getElementById('seo_tags_input') && d.seo_tags) document.getElementById('seo_tags_input').value = d.seo_tags;
                if (document.getElementById('course_tags_input') && d.seo_tags) document.getElementById('course_tags_input').value = d.seo_tags;
                if (Array.isArray(d.topics) && typeof topics !== 'undefined') {
                    d.topics.forEach(t => {
                        topics.push({
                            id: null,
                            title: t.title || 'Module',
                            description: t.description || '',
                            lessons: (t.lessons || []).map(l => ({
                                id: null,
                                title: l.title || 'Lesson',
                                description: l.content_text || '',
                                video_type: 'url',
                                video_url: '',
                                duration: (l.duration_minutes || 10) + 'm',
                                is_preview: !!l.is_preview,
                                is_live: false
                            })),
                            quizzes: [],
                            assignments: []
                        });
                    });
                    if (typeof renderTopics === 'function') renderTopics();
                    else if (typeof renderCurriculum === 'function') renderCurriculum();
                }
                closeBuilderModal('aiCourseBuilderModal');
            }

            let lastAiStudioPayload = null;
            async function runAiStudio() {
                const mode = document.getElementById('aiStudioMode')?.value || 'outline';
                const prompt = document.getElementById('aiStudioPrompt')?.value.trim();
                const docEl = document.getElementById('aiStudioDoc');
                const previewEl = document.getElementById('aiStudioPreview');
                const applyBtn = document.getElementById('applyAiStudioBtn');
                if (!prompt) { alert('Enter a prompt for AI Studio.'); return; }
                const fd = new FormData();
                fd.append('mode', mode);
                fd.append('prompt', prompt);
                if (docEl?.files?.[0]) fd.append('document', docEl.files[0]);
                previewEl.innerHTML = '<div class="text-muted small"><i class="fa-solid fa-spinner fa-spin me-1"></i>Generating...</div>';
                try {
                    const res = await fetch(window.courseBuilderRoutes.aiStudio, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': window.courseBuilderRoutes.csrf, 'Accept': 'application/json' },
                        body: fd
                    });
                    const data = await res.json();
                    if (!data.success) { previewEl.innerHTML = '<div class="text-danger small">Generation failed.</div>'; return; }
                    lastAiStudioPayload = { mode, data: data.data };
                    previewEl.innerHTML = `<pre class="bg-light p-2 rounded small mb-0" style="max-height:220px;overflow:auto;">${JSON.stringify(data.data, null, 2)}</pre>`;
                    applyBtn.disabled = false;
                } catch (e) {
                    previewEl.innerHTML = '<div class="text-danger small">Error calling AI Studio.</div>';
                }
            }

            function applyAiStudio() {
                if (!lastAiStudioPayload) return;
                const { mode, data } = lastAiStudioPayload;
                if (mode === 'faqs' && Array.isArray(data.faqs)) {
                    data.faqs.forEach(f => addFaqRow(f.question || '', f.answer || ''));
                } else if (mode === 'marketing_copy') {
                    if (document.getElementById('seo_title_input') && data.seo_title) document.getElementById('seo_title_input').value = data.seo_title;
                    if (document.getElementById('seo_description_input') && data.seo_description) document.getElementById('seo_description_input').value = data.seo_description;
                    if (document.getElementById('seo_tags_input') && data.seo_tags) document.getElementById('seo_tags_input').value = data.seo_tags;
                } else if (mode === 'outline' && Array.isArray(data.topics) && typeof topics !== 'undefined') {
                    data.topics.forEach(t => topics.push({
                        id: null, title: t.title, description: t.description || '',
                        lessons: (t.lessons || []).map(l => ({ id: null, title: l.title, description: '', video_type: 'url', video_url: '', duration: (l.duration_minutes || 10) + 'm', is_preview: !!l.is_preview, is_live: false })),
                        quizzes: [], assignments: []
                    }));
                    if (typeof renderTopics === 'function') renderTopics();
                    else if (typeof renderCurriculum === 'function') renderCurriculum();
                } else if (mode === 'lesson_text' && window.quillEditor && data.content_text) {
                    window.quillEditor.root.innerHTML += data.content_text;
                }
                closeBuilderModal('aiStudioModal');
            }
        </script>

        <div class="modal fade" id="aiCourseBuilderModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title h6"><i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i>AI Course Builder (One-Prompt Full Course Draft)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" onclick="closeBuilderModal('aiCourseBuilderModal')"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Course Prompt</label>
                            <textarea id="aiCoursePrompt" class="form-control form-control-sm" rows="3" placeholder="Describe your course topic, target audience, and goals..."></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Optional Reference Document (.txt, .md, .csv)</label>
                            <input type="file" id="aiCourseDoc" class="form-control form-control-sm" accept=".txt,.md,.csv,.html">
                        </div>
                        <div id="aiCoursePreview" class="mt-2"></div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="runAiCourseBuilder()"><i class="fa-solid fa-sparkles me-1"></i>Generate Draft</button>
                        <button type="button" class="btn btn-sm btn-primary" id="applyAiCourseBtn" disabled onclick="applyAiCourseDraft()">Apply Draft to Course</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="aiStudioModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title h6"><i class="fa-solid fa-robot text-primary me-1"></i>AI Studio (Review Before Applying)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" onclick="closeBuilderModal('aiStudioModal')"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Studio Tool</label>
                                <select id="aiStudioMode" class="form-select form-select-sm">
                                    <option value="outline">Course Outline (Prompt / Doc)</option>
                                    <option value="lesson_text">Lesson Text</option>
                                    <option value="quiz_questions">Quiz Questions</option>
                                    <option value="assignment_rubric">Assignment + Rubric</option>
                                    <option value="faqs">Course FAQs</option>
                                    <option value="marketing_copy">Marketing Copy &amp; SEO</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Optional Document (.txt, .md, .csv)</label>
                                <input type="file" id="aiStudioDoc" class="form-control form-control-sm" accept=".txt,.md,.csv,.html">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Prompt</label>
                            <textarea id="aiStudioPrompt" class="form-control form-control-sm" rows="2" placeholder="What would you like AI Studio to draft?"></textarea>
                        </div>
                        <div id="aiStudioPreview" class="mt-2"></div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="runAiStudio()"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generate Preview</button>
                        <button type="button" class="btn btn-sm btn-primary" id="applyAiStudioBtn" disabled onclick="applyAiStudio()">Apply to Builder</button>
                    </div>
                </div>
            </div>
        </div>
    @endpush
    @push('scripts')
    <script>
        (function () {
            var modeEl = document.getElementById('delivery_mode');
            var campusBox = document.getElementById('campusDeliveryFields');
            if (modeEl && campusBox) {
                modeEl.addEventListener('change', function () {
                    campusBox.style.display = (this.value === 'face_to_face' || this.value === 'hybrid') ? 'block' : 'none';
                });
            }
        })();
    </script>
    @endpush
@endsection