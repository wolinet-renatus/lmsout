{{--
    OUT LMS · Shared Face-to-Face / Hybrid Applicant Form
    Mirrors the SCMS Applicants Form as-is (Sections 1, 1.1, 2, 3).
    Used by: Admin/Registrar desk (layouts.admin) and public self-application (layouts.app).

    Expects: $countries, $courses, $branches, $venues, $educationLevels, $durations, $discountReasons,
             $feeUrl (with ":id" placeholder), $public (bool), optional $selectedCourse
--}}
@php
    $public = $public ?? false;
    $selectedCourseId = old('course_id', isset($selectedCourse) ? $selectedCourse->id : null);
@endphp

<div class="out-applicant-form">
    {{-- ========== 1. Applicant Information ========== --}}
    <div class="out-af-section mb-4">
        <h5 class="out-af-heading"><span class="out-af-num">1</span> Applicant Information</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="full_name">Full Name <span class="text-danger">*</span></label>
                <input type="text" id="full_name" name="full_name" maxlength="255" placeholder="Full Name" required
                    class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name', $prefill['full_name'] ?? '') }}">
                @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="home_address">Home Address <span class="text-danger">*</span></label>
                <input type="text" id="home_address" name="home_address" maxlength="255" placeholder="Home Address" required
                    class="form-control @error('home_address') is-invalid @enderror" value="{{ old('home_address') }}">
                @error('home_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="country_id">Nationality <span class="text-danger">*</span></label>
                <select id="country_id" name="country_id" required class="form-control @error('country_id') is-invalid @enderror">
                    <option value="" disabled {{ old('country_id') ? '' : 'selected' }}>Please select the Country</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->id }}" {{ (string) old('country_id', $loop->first ? $country->id : '') === (string) $country->id ? 'selected' : '' }}>
                            {{ $country->nicename && $country->nicename !== 'UNITED REPUBLIC OF' ? $country->nicename : ucwords(strtolower($country->name)) }}
                        </option>
                    @endforeach
                </select>
                @error('country_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="gender">Gender</label>
                <select id="gender" name="gender" class="form-control">
                    <option value="">-- Select --</option>
                    <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="office_of_work">Office Of Work</label>
                <input type="text" id="office_of_work" name="office_of_work" maxlength="255" placeholder="Office Of Work"
                    class="form-control" value="{{ old('office_of_work') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="occupation">Occupation</label>
                <input type="text" id="occupation" name="occupation" maxlength="255" placeholder="Occupation"
                    class="form-control" value="{{ old('occupation') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="mobile">Mobile No <span class="text-danger">*</span></label>
                <input type="text" id="mobile" name="mobile" maxlength="30" placeholder="e.g. 0712345678" required
                    class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile', $prefill['mobile'] ?? '') }}">
                @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="email">Email @if($public)<span class="text-danger">*</span>@endif</label>
                <input type="email" id="email" name="email" maxlength="255" placeholder="Email" {{ $public ? 'required' : '' }}
                    class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $prefill['email'] ?? '') }}"
                    {{ !empty($prefill['email']) && $public ? 'readonly' : '' }}>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="name_of_sponsor">Name Of Sponsor / Guardian</label>
                <input type="text" id="name_of_sponsor" name="name_of_sponsor" maxlength="255" placeholder="Name Of Sponsor / Guardian"
                    class="form-control" value="{{ old('name_of_sponsor') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="phone_2">His/Her Mobile No</label>
                <input type="text" id="phone_2" name="phone_2" maxlength="30" placeholder="Sponsor Mobile"
                    class="form-control" value="{{ old('phone_2') }}">
            </div>
            <div class="col-12 mb-2">
                <label class="form-label d-block">Education Level <span class="text-danger">*</span></label>
                <div class="out-af-radios">
                    @foreach ($educationLevels as $value => $label)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="education_level" id="education_level_{{ $value }}"
                                value="{{ $value }}" {{ (string) old('education_level') === (string) $value ? 'checked' : '' }} required>
                            <label class="form-check-label" for="education_level_{{ $value }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                @error('education_level') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    {{-- ========== 1.1 Staff Student Validation ========== --}}
    <div class="out-af-section mb-4">
        <h5 class="out-af-heading"><span class="out-af-num">1.1</span> Staff Student Validation</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="form-check">
                    <input type="hidden" name="is_staff_student" value="0">
                    <input class="form-check-input" type="checkbox" id="is_staff_student" name="is_staff_student" value="1"
                        {{ old('is_staff_student') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_staff_student">Is Staff Student (50% Fee, Auto Complete Payment)</label>
                </div>
                <small class="text-muted d-block mt-1">Applies to <strong>Basic Computer Application</strong> only.</small>
            </div>
            <div class="col-md-6 mb-3" id="parent_staff_id_wrap" style="display:none;">
                <label class="form-label d-block" for="parent_staff_id">Upload Parent Staff ID (pdf, jpg, png, jpeg, webp)</label>
                <input type="file" id="parent_staff_id" name="parent_staff_id" accept=".pdf,.png,.jpg,.jpeg,.webp"
                    class="form-control @error('parent_staff_id') is-invalid @enderror">
                @error('parent_staff_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    {{-- ========== 2. Course Details ========== --}}
    <div class="out-af-section mb-4">
        <h5 class="out-af-heading"><span class="out-af-num">2</span> Course Details</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="course_id">Course Title <span class="text-danger">*</span></label>
                <select id="course_id" name="course_id" required class="form-control @error('course_id') is-invalid @enderror"
                    {{ $public && isset($selectedCourse) ? 'data-locked=1' : '' }}>
                    <option value="" disabled {{ $selectedCourseId ? '' : 'selected' }}>Please select the Course</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}" {{ (string) $selectedCourseId === (string) $c->id ? 'selected' : '' }}>{{ $c->title }}</option>
                    @endforeach
                </select>
                @error('course_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="session">Session <span class="text-danger">*</span></label>
                <select id="session" name="session" required class="form-control">
                    @foreach (['morning' => 'Morning', 'afternoon' => 'Afternoon', 'evening' => 'Evening', 'weekend' => 'Weekend'] as $sv => $sl)
                        <option value="{{ $sv }}" {{ old('session', 'morning') === $sv ? 'selected' : '' }}>{{ $sl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="branch_id">Branch / Centre</label>
                <select id="branch_id" name="branch_id" class="form-control">
                    <option value="">-- Select Branch --</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" {{ (string) old('branch_id') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="venue_id">Venue</label>
                <select id="venue_id" name="venue_id" class="form-control">
                    <option value="">Please select the Venue</option>
                    @foreach ($venues as $v)
                        <option value="{{ $v->id }}" data-branch="{{ $v->branch_id }}" {{ (string) old('venue_id') === (string) $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label d-block" for="fee">Course Fee <span class="text-danger">*</span></label>
                <input type="number" id="fee" class="form-control" readonly value="{{ old('fee') }}" placeholder="Auto-filled">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label d-block" for="start_date">Start Date <span class="text-danger">*</span></label>
                <input type="date" id="start_date" name="start_date" required class="form-control @error('start_date') is-invalid @enderror"
                    value="{{ old('start_date') }}" min="{{ $public ? now()->toDateString() : '' }}">
                @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label d-block" for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}">
                @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 mb-2">
                <label class="form-label d-block">Duration <span class="text-danger">*</span></label>
                <div class="out-af-radios">
                    @foreach ($durations as $value => $label)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="duration" id="duration_{{ $value }}"
                                value="{{ $value }}" {{ (string) old('duration') === (string) $value ? 'checked' : '' }}>
                            <label class="form-check-label" for="duration_{{ $value }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label d-block" for="custom_duration">Custom Duration in (weeks)</label>
                <input type="number" id="custom_duration" name="custom_duration" min="1" max="104" class="form-control" value="{{ old('custom_duration') }}">
            </div>
        </div>
    </div>

    {{-- ========== 3. Payment Details ========== --}}
    <div class="out-af-section mb-2">
        <h5 class="out-af-heading"><span class="out-af-num">3</span> Payment Details</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label d-block">Installment <span class="text-danger">*</span></label>
                @foreach (['quarter' => 'Quarter (25%)', 'half' => 'Half (50%)', 'full' => 'Full (100%)'] as $iv => $il)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input installment_type_class" type="radio" name="installment_type" id="installment_{{ $iv }}"
                            value="{{ $iv }}" {{ old('installment_type', 'full') === $iv ? 'checked' : '' }}>
                        <label class="form-check-label" for="installment_{{ $iv }}">{{ $il }}</label>
                    </div>
                @endforeach
                <small class="d-block text-info mt-1" id="installment_message"></small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block" for="payment_amount">Amount Due Now</label>
                <input type="text" id="payment_amount" name="amount" class="form-control" readonly value="{{ old('amount') }}">
            </div>

            <div class="col-12 mb-3" id="course_discount_block" style="display:none;">
                <div class="p-3 rounded border border-success">
                    <h6 class="text-success mb-2">Selected Course Has Discount (<span id="show_discount_amount">0</span>)</h6>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input apply_discount" type="radio" name="apply_discount" id="discount_yes" value="1" {{ old('apply_discount') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="discount_yes">Apply Discount</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input apply_discount" type="radio" name="apply_discount" id="discount_no" value="0" {{ old('apply_discount', '0') === '0' ? 'checked' : '' }}>
                        <label class="form-check-label" for="discount_no">No discount</label>
                    </div>

                    <div id="if_discount_checked_row" class="mt-3" style="display:none;">
                        <h6 class="text-success">Tell us why applicant has discount?</h6>
                        @foreach ($discountReasons as $rv => $rl)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="discount_reason" id="discount_reason_{{ $rv }}" value="{{ $rv }}" {{ old('discount_reason') === $rv ? 'checked' : '' }}>
                                <label class="form-check-label" for="discount_reason_{{ $rv }}">{{ $loop->iteration }}. {{ $rl }}</label>
                            </div>
                        @endforeach
                        <div class="mt-2">
                            <label class="form-label d-block" for="discount_attachment">Discount attachment (ID Card)</label>
                            <input type="file" id="discount_attachment" name="discount_attachment" accept=".pdf,.png,.jpg,.jpeg,.webp" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <label class="form-label d-block">How would you like to pay?</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_option" id="pay_offline" value="offline" {{ old('payment_option', $public ? 'pay_later' : 'offline') === 'offline' ? 'checked' : '' }}>
                    <label class="form-check-label" for="pay_offline">
                        <strong>Offline</strong> &mdash; {{ $public ? 'I have paid via Control Number / bank and will attach the receipt' : 'Payment received at the desk (cash / bank / mobile money)' }}
                    </label>
                </div>
                @if ($public)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_option" id="pay_later" value="pay_later" {{ old('payment_option', 'pay_later') === 'pay_later' ? 'checked' : '' }}>
                        <label class="form-check-label" for="pay_later"><strong>Pay Later</strong> &mdash; issue my Invoice &amp; Control Number now; I will pay before the due date</label>
                    </div>
                @else
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_option" id="pay_later" value="pay_later" {{ old('payment_option') === 'pay_later' ? 'checked' : '' }}>
                        <label class="form-check-label" for="pay_later"><strong>Pay Later</strong> &mdash; issue Invoice &amp; Control Number only</label>
                    </div>
                @endif
                <div class="form-check" id="pay_online_wrap">
                    <input class="form-check-input" type="radio" name="payment_option" id="pay_online" value="online" {{ old('payment_option') === 'online' ? 'checked' : '' }}>
                    <label class="form-check-label" for="pay_online"><strong>Online</strong> &mdash; {{ $public ? 'pay the first installment now with card / mobile money' : 'send the student to online checkout for the first installment' }}</label>
                </div>
            </div>

            <div class="col-md-6 mb-3 offline-only">
                <label class="form-label d-block" for="receipt">Receipt Attachment (pdf, jpg, png, jpeg, webp)</label>
                <input type="file" id="receipt" name="receipt" accept=".pdf,.png,.jpg,.jpeg,.webp" class="form-control @error('receipt') is-invalid @enderror">
                @error('receipt') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3 offline-only">
                <label class="form-label d-block" for="receipt_number">Receipt Number</label>
                <input type="text" id="receipt_number" name="receipt_number" maxlength="100" placeholder="e.g. OUT-012345"
                    class="form-control @error('receipt_number') is-invalid @enderror" value="{{ old('receipt_number') }}">
                @error('receipt_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .out-applicant-form .out-af-section { background:#fff; border:1px solid #e6ebf5; border-radius:12px; padding:20px 20px 6px; }
    .out-applicant-form .out-af-heading { color:#013BA6; font-weight:700; font-size:1.05rem; margin-bottom:16px; display:flex; align-items:center; gap:10px; }
    .out-applicant-form .out-af-num { background:#013BA6; color:#fff; border-radius:8px; min-width:34px; height:28px; display:inline-flex; align-items:center; justify-content:center; font-size:.85rem; padding:0 8px; }
    .out-applicant-form .out-af-radios .form-check-inline { margin-bottom:6px; }
    .out-applicant-form .form-control[readonly] { background:#f4f7fc; }
</style>
@endpush
@push('scripts')
<script>
(function ($) {
    $(function () {
        var feeUrl = @json($feeUrl);
        var $course = $('#course_id'), $fee = $('#fee'), $amount = $('#payment_amount'),
            $start = $('#start_date'), $end = $('#end_date'), $msg = $('#installment_message'),
            $staff = $('#is_staff_student'), courseMeta = {};

        function weeks() {
            var custom = parseInt($('#custom_duration').val(), 10);
            if (custom > 0) return custom;
            var picked = $('input[name="duration"]:checked').val();
            return picked ? parseInt(picked, 10) : (parseInt(courseMeta.duration, 10) || 0);
        }
        function isBasic() { return !!courseMeta.is_basic_computer; }
        function staffLock() { return $staff.is(':checked') && isBasic(); }

        function netFee() {
            var fee = parseFloat($fee.val() || 0);
            if (staffLock()) return fee * 0.5;
            if ($('#discount_yes').is(':checked') && courseMeta.has_discount) return Math.max(0, fee - parseFloat(courseMeta.discount || 0));
            return fee;
        }
        function recompute() {
            var type = $('.installment_type_class:checked').val() || 'full', net = netFee(), amt = net;
            if (staffLock()) {
                $('#installment_quarter, #installment_half').prop('disabled', true).prop('checked', false);
                $('#installment_full').prop('checked', true);
                $msg.text('Staff Student: Amount is 50% of Course Fee. Payment will be considered Full.');
            } else {
                $('#installment_quarter, #installment_half').prop('disabled', false);
                if (type === 'quarter') { amt = net * 0.25; $msg.text('Amount will be set to 25% of the fee'); }
                else if (type === 'half') { amt = net * 0.5; $msg.text('Amount will be set to 50% of the fee'); }
                else { $msg.text(''); }
            }
            $amount.val(net > 0 ? amt.toFixed(2) : '0.00');
        }
        function recomputeEnd() {
            var w = weeks();
            if (!$start.val() || !w) return;
            var d = new Date($start.val());
            d.setDate(d.getDate() + (w * 7));
            $end.val(d.toISOString().slice(0, 10));
        }
        function filterVenues() {
            var b = $('#branch_id').val();
            $('#venue_id option[data-branch]').each(function () {
                var show = !b || String($(this).data('branch')) === String(b);
                $(this).prop('hidden', !show).prop('disabled', !show);
            });
        }
        function loadCourse() {
            var id = $course.val();
            if (!id) { $fee.val(''); recompute(); return; }
            $.getJSON(feeUrl.replace(':id', id), function (data) {
                courseMeta = data || {};
                $fee.val(data.fee);
                if (data.duration && data.duration !== '0') {
                    $('input[name="duration"][value="' + data.duration + '"]').prop('checked', true);
                }
                if (data.branch_id && !$('#branch_id').val()) { $('#branch_id').val(data.branch_id); filterVenues(); }
                if (data.venue_id && !$('#venue_id').val()) { $('#venue_id').val(data.venue_id); }
                $('#show_discount_amount').text(parseFloat(data.discount || 0).toLocaleString() + ' Tshs');
                $('#course_discount_block').toggle(!!data.has_discount);
                $('#parent_staff_id_wrap').toggle($staff.is(':checked'));
                recomputeEnd(); recompute();
            });
        }
        function toggleDiscount() {
            var on = $('#discount_yes').is(':checked');
            $('#if_discount_checked_row').toggle(on);
            if (on && !$('input[name="discount_reason"]:checked').length) $('input[name="discount_reason"]').first().prop('checked', true);
            if (!on) $('input[name="discount_reason"]').prop('checked', false);
            recompute();
        }
        function togglePayment() {
            $('.offline-only').toggle($('#pay_offline').is(':checked'));
        }

        $course.on('change', loadCourse);
        $start.on('change', recomputeEnd);
        $('input[name="duration"], #custom_duration').on('change keyup', recomputeEnd);
        $('.installment_type_class').on('change', recompute);
        $staff.on('change', function () { $('#parent_staff_id_wrap').toggle($staff.is(':checked')); recompute(); });
        $('.apply_discount').on('change', toggleDiscount);
        $('#branch_id').on('change', function () { filterVenues(); $('#venue_id').val(''); });
        $('input[name="payment_option"]').on('change', togglePayment);

        filterVenues(); toggleDiscount(); togglePayment();
        $('#parent_staff_id_wrap').toggle($staff.is(':checked'));
        if ($course.val()) loadCourse();
    });
})(jQuery);
</script>
@endpush
@endonce
