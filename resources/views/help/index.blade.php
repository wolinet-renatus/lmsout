@extends($layout)

@section('title', 'Online Help & Security Guide')

@section($layout === 'layouts.admin' ? 'main-content' : 'content')
<div class="{{ $layout === 'layouts.admin' ? '' : 'container-fluid py-3' }}">
    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="text-primary mb-1"><i class="fas fa-question-circle mr-2"></i>{{ site_name() }} Online Help Center</h4>
                <p class="text-muted mb-0">Signed in as <strong>{{ $user->name }}</strong> (<span class="badge badge-primary">{{ $roleName }}</span>)</p>
            </div>
            <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary mt-2 mt-md-0">Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0 text-primary">Student Workflows (Online &amp; Campus F2F)</h5></div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li class="mb-2"><strong>Enrollments:</strong> Enroll directly in Online courses via checkout or choose Face-to-Face / Hybrid delivery by selecting a regional branch, venue, session, and start date.</li>
                        <li class="mb-2"><strong>Invoices &amp; Control Numbers:</strong> Invoices and installment plans issue automatically; use <em>Invoices &amp; Control Nos</em> to generate payment control numbers, upload payment slips, and print official receipts.</li>
                        <li class="mb-0"><strong>QR Attendance &amp; Certificates:</strong> Scan tutor QR check-in codes during sessions. Once fees are paid and attendance thresholds are met, download your verified <code>OUT-CERT-...</code> PDF certificate.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0 text-primary">Branch Manager, Coordinator, Marketing, Director &amp; Tutor</h5></div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li class="mb-2"><strong>Branch Managers &amp; Coordinators:</strong> Register walk-in applicants, assign tutors and physical venues cleanly (double-booking protected), manage installments, and verify offline receipts within your center.</li>
                        <li class="mb-2"><strong>Reports &amp; Analytics:</strong> Export Weekly/Monthly/Quarterly <em>Center Course Reports</em> and <em>Applicant Reports</em> (PDF, Excel, CSV), inspect active cohorts, waivers, and tutor workloads.</li>
                        <li class="mb-0"><strong>Tutors / Instructors:</strong> View personal and branch schedules, track Paid vs Unpaid student rosters, record manual/QR attendance, and receive alerts when student certificates become ready.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 text-primary">Account &amp; Session Security Policies (SRS Section 5.1)</h5></div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li class="mb-1"><strong>Password Complexity &amp; Expiry:</strong> Passwords require at least 8 characters combining uppercase, lowercase, numbers, and special characters. Periodic password rotation is enforced when enabled by System Admin.</li>
                        <li class="mb-1"><strong>Lockout Protection:</strong> Consecutive failed sign-in attempts trigger temporary cooldown lockouts and escalate to account suspension until unblocked by an administrator.</li>
                        <li class="mb-1"><strong>Session Integrity:</strong> Idle sessions expire automatically (default 5 minutes of inactivity) and single active session rules prevent simultaneous logins across browsers.</li>
                        <li class="mb-0"><strong>Form Draft Recovery:</strong> Unsaved non-password form data is automatically cached in your browser for 15 minutes so accidental refreshes never lose work.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
