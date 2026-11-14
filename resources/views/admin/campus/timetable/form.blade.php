@extends('layouts.admin')

@section('title', $assignment->exists ? 'Edit Timetable Cohort' : 'Schedule Timetable Cohort')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.campus.timetable.index') }}">Timetable</a></div>
        <div class="breadcrumb-item active">{{ $assignment->exists ? 'Edit' : 'Create' }}</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header">
            <h4>{{ $assignment->exists ? 'Edit Cohort' : 'New Cohort Assignment' }}</h4>
            <div class="card-header-action">
                <a href="{{ route('admin.campus.timetable.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <form action="{{ $assignment->exists ? route('admin.campus.timetable.update', $assignment) : route('admin.campus.timetable.store') }}" method="POST">
            @csrf
            @if ($assignment->exists) @method('PUT') @endif
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>F2F / Hybrid Course <span class="text-danger">*</span></label>
                        <select name="course_id" class="form-control @error('course_id') is-invalid @enderror" required>
                            <option value="">-- Select Course --</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}" {{ old('course_id', $assignment->course_id) == $c->id ? 'selected' : '' }}>{{ $c->title }}</option>
                            @endforeach
                        </select>
                        @error('course_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label>Tutor / Instructor</label>
                        <select name="instructor_id" class="form-control">
                            <option value="">-- Unassigned --</option>
                            @foreach ($instructors as $inst)
                                <option value="{{ $inst->id }}" {{ old('instructor_id', $assignment->instructor_id) == $inst->id ? 'selected' : '' }}>{{ $inst->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                            <option value="">-- Select Branch --</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ old('branch_id', $assignment->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label>Venue</label>
                        <select name="venue_id" class="form-control @error('venue_id') is-invalid @enderror">
                            <option value="">-- Select Venue --</option>
                            @foreach ($venues as $v)
                                <option value="{{ $v->id }}" {{ old('venue_id', $assignment->venue_id) == $v->id ? 'selected' : '' }}>{{ $v->name }} ({{ $v->branch?->name }})</option>
                            @endforeach
                        </select>
                        @error('venue_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label>Session <span class="text-danger">*</span></label>
                        <select name="session" class="form-control" required>
                            @foreach (['morning' => 'Morning', 'afternoon' => 'Afternoon', 'evening' => 'Evening', 'weekend' => 'Weekend'] as $sv => $sl)
                                <option value="{{ $sv }}" {{ old('session', strtolower((string) $assignment->session)) === $sv ? 'selected' : '' }}>{{ $sl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', optional($assignment->start_date)->format('Y-m-d')) }}" required>
                        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', optional($assignment->end_date)->format('Y-m-d')) }}">
                        @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label>Start Time</label>
                        <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $assignment->start_time ? substr($assignment->start_time, 0, 5) : '') }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>End Time</label>
                        <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $assignment->end_time ? substr($assignment->end_time, 0, 5) : '') }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            @foreach (['active', 'completed', 'cancelled'] as $st)
                                <option value="{{ $st }}" {{ old('status', $assignment->status) === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-8">
                        <label class="d-block">Class Days</label>
                        @php $pickedDays = old('schedule_days', $assignment->schedule_days ?? []); @endphp
                        @foreach (['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'] as $dk => $dl)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="schedule_days[]" id="day_{{ $dk }}" value="{{ $dk }}" {{ in_array($dk, $pickedDays, true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="day_{{ $dk }}">{{ $dl }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-group col-md-4">
                        <label>Max Capacity</label>
                        <input type="number" name="max_capacity" class="form-control" min="1" value="{{ old('max_capacity', $assignment->max_capacity) }}">
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary">{{ $assignment->exists ? 'Save Cohort' : 'Create Cohort' }}</button>
            </div>
        </form>
    </div>
@endsection
