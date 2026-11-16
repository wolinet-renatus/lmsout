<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StudentsExport;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Student Controller
 *
 * Manages student records with real-time search, multi-criteria filtering,
 * branch isolation, configurable pagination (>= 6 per page), and exports.
 *
 * @package App\Http\Controllers\Admin
 * @author Renatus Bernardo
 */
class StudentController extends Controller
{
    private const PER_PAGE_OPTIONS = [6, 10, 15, 25, 50];

    /**
     * Display all students with live search, filtering, pagination, and exports.
     */
    public function index(Request $request)
    {
        if (in_array(strtolower((string) $request->input('export')), ['excel', 'csv', 'pdf'], true)) {
            return $this->export($request);
        }

        [$query, $filters, $effectiveBranch] = $this->buildStudentsQuery($request);

        $requestedPerPage = (int) ($filters['per_page'] ?: 6);
        $perPage = in_array($requestedPerPage, self::PER_PAGE_OPTIONS, true)
            ? $requestedPerPage
            : max(6, min(50, $requestedPerPage));

        $matchingIds = (clone $query)->pluck('users.id');
        $totalMatching = $matchingIds->count();
        $activeCount = (clone $query)->where('is_approved', true)->where('is_blocked', false)->count();
        $enrolledCount = (clone $query)->whereHas('enrollments', fn ($q) => $q->withoutGlobalScope('branch'))->count();
        $totalSpent = $matchingIds->isEmpty()
            ? 0.0
            : (float) Payment::withoutGlobalScope('branch')
                ->where('status', Payment::STATUS_COMPLETED)
                ->where(function (Builder $pq) use ($matchingIds): void {
                    $pq->whereIn('user_id', $matchingIds)
                        ->orWhereHas('enrollment', fn (Builder $eq) => $eq->withoutGlobalScope('branch')->whereIn('user_id', $matchingIds));
                })
                ->sum('amount');

        $students = (clone $query)
            ->with([
                'branch:id,name',
                'enrollments' => fn ($q) => $q->withoutGlobalScope('branch')->with(['course:id,title', 'payment:id,amount,status']),
            ])
            ->paginate($perPage)
            ->appends($request->except('ajax'))
            ->withPath(route('admin.students.index'));

        $this->enrichStudents($students->getCollection());

        $totalStudents = User::where('role', 'student')
            ->when($effectiveBranch, function (Builder $q) use ($effectiveBranch): void {
                $q->where(function (Builder $bq) use ($effectiveBranch): void {
                    $bq->where('branch_id', $effectiveBranch)
                        ->orWhereHas('enrollments', fn (Builder $eq) => $eq->withoutGlobalScope('branch')->where('branch_id', $effectiveBranch));
                });
            })
            ->count();

        $summary = [
            'total_matching' => $totalMatching,
            'active_count' => $activeCount,
            'enrolled_count' => $enrolledCount,
            'total_spent' => round($totalSpent, 2),
            'total_spent_formatted' => currency_format($totalSpent),
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            $html = view('admin.students._table', [
                'students' => $students,
                'summary' => $summary,
                'totalStudents' => $totalStudents,
                'perPage' => $perPage,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'summary' => $summary,
                'total_students' => $totalStudents,
                'per_page' => $perPage,
            ]);
        }

        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $courses = Course::orderBy('title')->get(['id', 'title']);
        $perPageOptions = self::PER_PAGE_OPTIONS;

        return view('admin.students.index', compact(
            'students',
            'totalStudents',
            'branches',
            'courses',
            'filters',
            'summary',
            'perPage',
            'perPageOptions',
            'effectiveBranch'
        ));
    }

    /**
     * Export filtered student directory as Excel (.xlsx), CSV, or PDF.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) ($request->input('format') ?: $request->input('export', 'csv')));
        if (! in_array($format, ['excel', 'csv', 'pdf'], true)) {
            $format = 'csv';
        }

        [$query, $filters] = $this->buildStudentsQuery($request);

        $students = $query->with([
            'branch:id,name',
            'enrollments' => fn ($q) => $q->withoutGlobalScope('branch')->with(['course:id,title', 'payment:id,amount,status']),
        ])->get();

        $this->enrichStudents($students);

        $filenameBase = 'students_' . date('Ymd_His');

        if ($format === 'excel') {
            return Excel::download(new StudentsExport($students), $filenameBase . '.xlsx');
        }

        if ($format === 'csv') {
            return Excel::download(new StudentsExport($students), $filenameBase . '.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        $activeFilters = array_filter([
            $filters['search'] !== '' ? 'Search: ' . $filters['search'] : null,
            $filters['enrollment_status'] !== '' ? 'Enrollment: ' . ucfirst($filters['enrollment_status']) : null,
            $filters['account_status'] !== '' ? 'Account: ' . ucfirst($filters['account_status']) : null,
            $filters['joined_from'] ? 'From: ' . $filters['joined_from'] : null,
            $filters['joined_to'] ? 'To: ' . $filters['joined_to'] : null,
        ]);

        return Pdf::loadView('admin.students.pdf', [
            'students' => $students,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'searchSummary' => implode(' | ', $activeFilters),
        ])->setPaper('a4', 'landscape')->download($filenameBase . '.pdf');
    }

    /**
     * Build filtered, sorted query for student directory listings and exports.
     *
     * @return array{0: Builder, 1: array<string, mixed>, 2: ?int}
     */
    private function buildStudentsQuery(Request $request): array
    {
        /** @var User|null $actor */
        $actor = Auth::user();
        $requestedBranchId = $request->integer('branch_id') ?: null;
        $effectiveBranch = $actor ? $actor->effectiveBranchId($requestedBranchId) : $requestedBranchId;

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'branch_id' => $effectiveBranch,
            'course_id' => $request->integer('course_id') ?: null,
            'enrollment_status' => trim((string) $request->input('enrollment_status', '')),
            'account_status' => trim((string) $request->input('account_status', '')),
            'joined_from' => trim((string) $request->input('joined_from', '')),
            'joined_to' => trim((string) $request->input('joined_to', '')),
            'sort' => trim((string) $request->input('sort', 'latest')),
            'per_page' => (int) $request->input('per_page', 6),
        ];

        $query = User::query()
            ->where('role', 'student')
            ->withCount(['enrollments as total_enrollments_count' => fn (Builder $q) => $q->withoutGlobalScope('branch')])
            ->select('users.*')
            ->selectSub(
                Payment::withoutGlobalScope('branch')
                    ->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('payments.user_id', 'users.id')
                    ->where('payments.status', Payment::STATUS_COMPLETED),
                'completed_spent_sum'
            );

        if ($effectiveBranch) {
            $query->where(function (Builder $q) use ($effectiveBranch): void {
                $q->where('users.branch_id', $effectiveBranch)
                    ->orWhereHas('enrollments', fn (Builder $eq) => $eq->withoutGlobalScope('branch')->where('branch_id', $effectiveBranch));
            });
        }

        if ($filters['search'] !== '') {
            $term = $filters['search'];
            $query->where(function (Builder $q) use ($term): void {
                $q->where('users.name', 'like', "%{$term}%")
                    ->orWhere('users.email', 'like', "%{$term}%")
                    ->orWhere('users.phone', 'like', "%{$term}%")
                    ->orWhere('users.registration_number', 'like', "%{$term}%")
                    ->orWhereHas('enrollments', fn (Builder $eq) => $eq->withoutGlobalScope('branch')
                        ->whereHas('course', fn (Builder $cq) => $cq->where('title', 'like', "%{$term}%")));
            });
        }

        if ($filters['course_id']) {
            $courseId = $filters['course_id'];
            $query->whereHas('enrollments', fn (Builder $eq) => $eq->withoutGlobalScope('branch')->where('course_id', $courseId));
        }

        if ($filters['enrollment_status'] === 'enrolled') {
            $query->whereHas('enrollments', fn (Builder $eq) => $eq->withoutGlobalScope('branch'));
        } elseif ($filters['enrollment_status'] === 'active') {
            $query->whereHas('enrollments', fn (Builder $eq) => $eq->withoutGlobalScope('branch')->where('status', Enrollment::STATUS_APPROVED));
        } elseif ($filters['enrollment_status'] === 'completed') {
            $query->whereHas('enrollments', fn (Builder $eq) => $eq->withoutGlobalScope('branch')->where('status', Enrollment::STATUS_COMPLETED));
        } elseif ($filters['enrollment_status'] === 'unenrolled') {
            $query->whereDoesntHave('enrollments', fn (Builder $eq) => $eq->withoutGlobalScope('branch'));
        }

        if ($filters['account_status'] === 'active') {
            $query->where('users.is_approved', true)->where('users.is_blocked', false);
        } elseif ($filters['account_status'] === 'pending') {
            $query->where('users.is_approved', false)->where('users.is_blocked', false);
        } elseif ($filters['account_status'] === 'blocked') {
            $query->where('users.is_blocked', true);
        }

        if ($filters['joined_from'] !== '') {
            $query->whereDate('users.created_at', '>=', $filters['joined_from']);
        }

        if ($filters['joined_to'] !== '') {
            $query->whereDate('users.created_at', '<=', $filters['joined_to']);
        }

        match ($filters['sort']) {
            'oldest' => $query->orderBy('users.created_at', 'asc')->orderBy('users.id', 'asc'),
            'name_asc' => $query->orderBy('users.name', 'asc'),
            'most_enrolled' => $query->orderByDesc('total_enrollments_count')->orderByDesc('users.id'),
            'highest_spent' => $query->orderByDesc('completed_spent_sum')->orderByDesc('users.id'),
            default => $query->orderByDesc('users.created_at')->orderByDesc('users.id'),
        };

        return [$query, $filters, $effectiveBranch];
    }

    /**
     * Batch-enrich paginated or exported student collections without N+1 queries.
     */
    private function enrichStudents(Collection $students): void
    {
        if ($students->isEmpty()) {
            return;
        }

        $studentIds = $students->pluck('id')->all();
        $enrollmentPaymentIds = $students->flatMap(fn (User $s) => $s->enrollments->pluck('payment_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $completedPayments = Payment::withoutGlobalScope('branch')
            ->where('status', Payment::STATUS_COMPLETED)
            ->where(function (Builder $q) use ($studentIds, $enrollmentPaymentIds): void {
                $q->whereIn('user_id', $studentIds);
                if (! empty($enrollmentPaymentIds)) {
                    $q->orWhereIn('id', $enrollmentPaymentIds);
                }
            })
            ->with('enrollment:id,user_id')
            ->get()
            ->unique('id');

        $spentByUser = [];
        foreach ($completedPayments as $payment) {
            $ownerId = (int) ($payment->user_id ?: $payment->enrollment?->user_id ?: 0);
            if ($ownerId > 0) {
                $spentByUser[$ownerId] = ($spentByUser[$ownerId] ?? 0.0) + (float) $payment->amount;
            }
        }

        $students->transform(function (User $student) use ($spentByUser): User {
            $enrollments = $student->enrollments;
            $student->total_enrollments = $enrollments->count();
            $student->completed_courses = $enrollments->where('status', Enrollment::STATUS_COMPLETED)->count();
            $student->active_enrollments = $enrollments->where('status', Enrollment::STATUS_APPROVED)->count();
            $student->total_spent = round((float) ($spentByUser[$student->id] ?? 0.0), 2);

            if ($student->is_blocked) {
                $student->account_status_label = 'Blocked';
                $student->account_status_badge = 'danger';
            } elseif ($student->is_approved) {
                $student->account_status_label = 'Active';
                $student->account_status_badge = 'success';
            } else {
                $student->account_status_label = 'Pending';
                $student->account_status_badge = 'warning';
            }

            return $student;
        });
    }

    /**
     * Show form for creating a new student.
     */
    public function create()
    {
        return view('admin.students.create');
    }

    /**
     * Store a newly created student.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Please correct the validation errors.');
            return back()->withErrors($validator)->withInput();
        }

        $student = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
            'phone' => $request->input('phone'),
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);

        if (!$student->hasRole('student')) {
            $student->assignRole('student');
        }

        ToastMagic::success("Student '{$student->name}' created successfully.");
        return redirect()->route('admin.students.show', $student->id);
    }

    /**
     * Delete a student
     */
    public function destroy($id)
    {
        $student = User::where('role', 'student')->findOrFail($id);
        $name = $student->name;
        $student->delete();

        ToastMagic::success("Student '{$name}' deleted successfully.");
        return redirect()->route('admin.students.index');
    }

    /**
     * Display student details
     */
    public function show($id)
    {
        $student = User::where('role', 'student')
            ->with([
                'enrollments.course.instructor',
                'enrollments.course.topics.lessons',
                'enrollments.payment',
                'enrollments.lessonProgress',
                'reviews.course'
            ])
            ->findOrFail($id);

        $enrollments = $student->enrollments;
        $totalEnrollments = $enrollments->count();
        $completedCourses = $enrollments->where('status', Enrollment::STATUS_COMPLETED)->count();
        $activeEnrollments = $enrollments->where('status', Enrollment::STATUS_APPROVED)->count();

        $paymentIds = $enrollments->pluck('payment_id')->filter();
        $totalSpent = Payment::whereIn('id', $paymentIds)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $enrollmentsWithProgress = $enrollments->map(function ($enrollment) {
            $course = $enrollment->course;
            if (!$course) {
                return null;
            }

            $completedLessons = $enrollment->lessonProgress()->where('is_completed', true)->count();
            $totalLessons = 0;
            foreach ($course->topics as $topic) {
                $totalLessons += $topic->lessons()->count();
            }
            $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

            $enrollment->progress_percentage = $progress;
            $enrollment->completed_lessons = $completedLessons;
            $enrollment->total_lessons = $totalLessons;

            return $enrollment;
        })->filter();

        return view('admin.students.show', compact(
            'student',
            'totalEnrollments',
            'completedCourses',
            'totalSpent',
            'activeEnrollments',
            'enrollmentsWithProgress'
        ));
    }

    /**
     * Display student edit form
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        $student = User::where('role', 'student')->findOrFail($id);

        return view('admin.students.edit', compact('student'));
    }

    /**
     * Update student details
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $student = User::where('role', 'student')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $student->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Fix the validation errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        $student->name = $request->name;
        $student->email = $request->email;
        $student->phone = $request->input('phone');

        if ($request->filled('password')) {
            $student->password = Hash::make($request->password);
        }

        $student->save();

        ToastMagic::success('Student details updated successfully.');

        return redirect()->route('admin.students.show', $student->id);
    }
}
