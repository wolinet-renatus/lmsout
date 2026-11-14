@extends('layouts.instructor')

@section('content')
    <!-- Manage Bundles Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">
                Manage Bundles
            </span>
            <h2 class="fw-bold mb-1" style="color: #0f172a; font-size: 24px;">My Course Bundles</h2>
            <p class="text-muted mb-0" style="font-size: 13.5px;">Group your courses into discounted bundles to increase learner enrollment.</p>
        </div>
        <div>
            <a href="{{ route('instructor.bundles.create') }}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold" style="box-shadow: 0 4px 12px rgba(1, 59, 166, 0.25);">
                <i class="fa-solid fa-plus me-1"></i> Create Bundle
            </a>
        </div>
    </div>

    <!-- Bundles Table Card -->
    <div class="out-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="padding-left: 20px;">Bundle Title</th>
                        <th>Price</th>
                        <th>Courses Included</th>
                        <th>Status</th>
                        <th>Approval</th>
                        <th style="padding-right: 20px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bundles as $bundle)
                        <tr>
                            <td style="padding-left: 20px;">
                                <div class="d-flex align-items-center gap-3">
                                    @if ($bundle->featured_image)
                                        <img src="{{ asset('storage/' . $bundle->featured_image) }}"
                                            alt="{{ $bundle->title }}" class="rounded"
                                            style="width: 52px; height: 52px; object-fit: cover;">
                                    @else
                                        <div class="rounded d-flex align-items-center justify-content-center bg-light text-secondary border"
                                            style="width: 52px; height: 52px;">
                                            <i class="fa-solid fa-layer-group"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark" style="font-size: 14px;">{{ Str::limit($bundle->title, 50) }}</h6>
                                        <span class="text-muted" style="font-size: 12px;">ID: #{{ $bundle->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="fw-bold text-dark">{{ currency_format($bundle->price) }}</span></td>
                            <td><span class="badge bg-light text-primary border"><i class="fa-solid fa-book me-1"></i> {{ $bundle->courses->count() }} Courses</span></td>
                            <td>
                                @if($bundle->status === 'active')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Active</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if($bundle->approval_status === 'approved')
                                    <span class="badge bg-success px-2 py-1">Approved</span>
                                @elseif($bundle->approval_status === 'rejected')
                                    <span class="badge bg-danger px-2 py-1">Rejected</span>
                                @else
                                    <span class="badge bg-warning text-dark px-2 py-1">Pending</span>
                                @endif
                            </td>
                            <td style="padding-right: 20px; text-align: right;">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('instructor.bundles.edit', $bundle->id) }}"
                                        class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="fa-solid fa-edit"></i>
                                    </a>
                                    <form action="{{ route('instructor.bundles.destroy', $bundle->id) }}"
                                        method="POST" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this bundle?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="out-empty-state-icon mb-2">
                                    <i class="fa-solid fa-layer-group text-muted" style="font-size: 36px;"></i>
                                </div>
                                <h5 class="fw-bold mb-1">No course bundles found</h5>
                                <p class="text-muted mb-3" style="font-size: 13.5px;">Group your courses into discounted bundles to increase learner enrollment.</p>
                                <a href="{{ route('instructor.bundles.create') }}" class="btn btn-primary btn-sm rounded-pill px-3 py-2 fw-semibold">
                                    <i class="fa-solid fa-plus me-1"></i> Create Your First Bundle
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(method_exists($bundles, 'links') && $bundles->hasPages())
        <div class="mt-4">
            {{ $bundles->links() }}
        </div>
    @endif
@endsection
