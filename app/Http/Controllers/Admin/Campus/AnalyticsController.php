<?php

namespace App\Http\Controllers\Admin\Campus;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CourseAssignment;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Operational & Executive Analytics Controller (migrated from SCMS AnalyticsController, SRS R2/R4/R5).
 * @author Renatus Bernardo
 */
class AnalyticsController extends Controller
{
    /**
     * Branches.
     */
    public function branches(Request $request): View
    {
        $effectiveBranch = auth()->user()?->effectiveBranchId($request->integer('branch_id') ?: null);

        $branches = Branch::with(['venues:id,branch_id'])
            ->withCount(['venues', 'applicants'])
            ->when($effectiveBranch, fn ($q) => $q->where('id', $effectiveBranch))
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) {
                $totals = Invoice::withoutGlobalScope('branch')
                    ->where('branch_id', $branch->id)
                    ->selectRaw('COALESCE(SUM(net_amount),0) as net_total, COALESCE(SUM(paid_amount),0) as paid_total, COALESCE(SUM(balance),0) as balance_total, COUNT(*) as invoice_count')
                    ->first();

                return [
                    'id'               => $branch->id,
                    'name'             => $branch->name,
                    'code'             => $branch->branch_code ?? '-',
                    'address'          => $branch->address ?? '-',
                    'venues_count'     => $branch->venues_count,
                    'applicants_count' => $branch->applicants_count,
                    'invoice_count'    => (int) ($totals->invoice_count ?? 0),
                    'invoiced'         => (float) ($totals->net_total ?? 0),
                    'collected'        => (float) ($totals->paid_total ?? 0),
                    'outstanding'      => (float) ($totals->balance_total ?? 0),
                ];
            });

        return view('admin.campus.analytics.branches', compact('branches'));
    }

    /**
     * Discounts.
     */
    public function discounts(Request $request): View
    {
        $effectiveBranch = auth()->user()?->effectiveBranchId($request->integer('branch_id') ?: null);

        $discountedInvoices = Invoice::with(['applicant:id,first_name,middle_name,last_name,full_name,email,discount_reason', 'branch:id,name', 'course:id,title', 'user:id,name,email'])
            ->where('discount_amount', '>', 0)
            ->when($effectiveBranch, fn ($q) => $q->where('branch_id', $effectiveBranch))
            ->latest('issued_at')
            ->paginate(25)
            ->withQueryString();

        $totalWaived = (float) Invoice::when($effectiveBranch, fn ($q) => $q->where('branch_id', $effectiveBranch))
            ->sum('discount_amount');

        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.campus.analytics.discounts', compact('discountedInvoices', 'totalWaived', 'branches', 'effectiveBranch'));
    }

    /**
     * Tutor workload.
     */
    public function tutorWorkload(Request $request): View
    {
        $effectiveBranch = auth()->user()?->effectiveBranchId($request->integer('branch_id') ?: null);

        $tutors = User::where('role', 'instructor')
            ->with([
                'branch:id,name',
                'courseAssignments' => fn ($q) => $q->when($effectiveBranch, fn ($b) => $b->where('branch_id', $effectiveBranch)),
            ])
            ->when($effectiveBranch, fn ($q) => $q->where(fn ($w) => $w->where('branch_id', $effectiveBranch)
                ->orWhereHas('courseAssignments', fn ($ca) => $ca->where('branch_id', $effectiveBranch))))
            ->orderBy('name')
            ->get()
            ->map(function (User $tutor) {
                $active = $tutor->courseAssignments->where('status', 'active')->count();
                $completed = $tutor->courseAssignments->where('status', 'completed')->count();

                return [
                    'id'                 => $tutor->id,
                    'name'               => $tutor->name,
                    'email'              => $tutor->email,
                    'branch'             => $tutor->branch?->name ?? '-',
                    'specialization'     => $tutor->specialization ?? '-',
                    'active_sessions'    => $active,
                    'completed_sessions' => $completed,
                    'total_sessions'     => $active + $completed,
                ];
            });

        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.campus.analytics.tutor-workload', compact('tutors', 'branches', 'effectiveBranch'));
    }

    /**
     * Active classes.
     */
    public function activeClasses(Request $request): View
    {
        $effectiveBranch = auth()->user()?->effectiveBranchId($request->integer('branch_id') ?: null);

        $classes = CourseAssignment::with(['course:id,title,delivery_mode', 'branch:id,name', 'venue:id,name,capacity', 'instructor:id,name,email'])
            ->withCount('enrollments')
            ->where('status', 'active')
            ->when($effectiveBranch, fn ($q) => $q->where('branch_id', $effectiveBranch))
            ->orderBy('start_date')
            ->paginate(25)
            ->withQueryString();

        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.campus.analytics.active-classes', compact('classes', 'branches', 'effectiveBranch'));
    }
}
