<div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
    <small class="text-muted">
        @if ($students->total() > 0)
            Showing <strong>{{ $students->firstItem() }}</strong>&ndash;<strong>{{ $students->lastItem() }}</strong>
            of <strong>{{ number_format($students->total()) }}</strong> matching students
            ({{ $perPage }} per page)
        @else
            Showing <strong>0</strong> matching students
        @endif
    </small>
    <small class="text-muted">Total directory: {{ number_format($totalStudents) }} students</small>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover mb-0" id="students-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Branch &amp; Status</th>
                <th>Enrollments</th>
                <th>Completed</th>
                <th>Total Spent</th>
                <th>Joined Date</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            @if ($student->profile_photo)
                                <img src="{{ asset('storage/' . $student->profile_photo) }}"
                                    alt="{{ $student->name }}" class="mr-3 rounded-circle"
                                    style="width: 44px; height: 44px; object-fit: cover;">
                            @else
                                <div class="mr-3 bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 44px; height: 44px; flex-shrink: 0;">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                            @endif
                            <div>
                                <h6 class="mb-0 font-weight-bold">
                                    <a href="{{ route('admin.students.show', $student->id) }}" class="text-dark">
                                        {{ $student->name }}
                                    </a>
                                </h6>
                                <small class="text-muted d-block">{{ $student->email }}</small>
                                @if ($student->phone || $student->registration_number)
                                    <small class="text-muted">
                                        @if ($student->registration_number)
                                            <code>{{ $student->registration_number }}</code>
                                        @endif
                                        @if ($student->phone)
                                            <span class="ml-1"><i class="fas fa-phone-alt"></i> {{ $student->phone }}</span>
                                        @endif
                                    </small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="mb-1">
                            <span class="badge badge-{{ $student->account_status_badge ?? 'success' }}">
                                {{ $student->account_status_label ?? 'Active' }}
                            </span>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-code-branch mr-1"></i>{{ $student->branch?->name ?? 'Online / Unassigned' }}
                        </small>
                    </td>
                    <td>
                        <span class="badge badge-primary" title="Total enrollments">{{ $student->total_enrollments ?? 0 }} Total</span>
                        @if (($student->active_enrollments ?? 0) > 0)
                            <span class="badge badge-info" title="Active enrollments">{{ $student->active_enrollments }} Active</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-success">{{ $student->completed_courses ?? 0 }}</span>
                    </td>
                    <td>
                        <strong>{{ currency_format($student->total_spent ?? 0) }}</strong>
                    </td>
                    <td>
                        <small>{{ optional($student->created_at)?->format('M d, Y') ?? '—' }}</small>
                    </td>
                    <td class="text-right">
                        <div class="btn-group" role="group">
                            <form action="{{ route('admin.impersonate.student', $student->id) }}"
                                method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary"
                                    title="Login as Student">
                                    <i class="fas fa-user-secret"></i>
                                </button>
                            </form>
                            <a href="{{ route('admin.students.edit', $student->id) }}"
                                class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="{{ route('admin.students.show', $student->id) }}"
                                class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <form action="{{ route('admin.students.destroy', $student->id) }}"
                                method="POST" class="d-inline"
                                onsubmit="return confirm('Are you sure you want to delete student {{ addslashes($student->name) }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="py-4">
                            <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No students match the current search or filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($students->hasPages())
    <div class="d-flex justify-content-center mt-4" id="students-pagination-links">
        {{ $students->links('vendor.pagination.stisla-default') }}
    </div>
@endif
