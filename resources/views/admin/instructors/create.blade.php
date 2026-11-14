@extends('layouts.admin')

@section('title', 'Add New Instructor')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.instructors.index') }}">Instructors</a></div>
        <div class="breadcrumb-item active">Add Instructor</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Add New Instructor</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.instructors.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Back to Instructors
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.instructors.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}" placeholder="e.g. Dr. Jane Smith" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" id="email" name="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email') }}" placeholder="instructor@example.com" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Password <span class="text-danger">*</span></label>
                                    <input type="password" id="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="Minimum 8 characters" required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                        class="form-control" placeholder="Re-enter password" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" id="phone" name="phone"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        value="{{ old('phone') }}" placeholder="+1234567890">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="specialization">Specialization / Designation</label>
                                    <input type="text" id="specialization" name="specialization"
                                        class="form-control @error('specialization') is-invalid @enderror"
                                        value="{{ old('specialization') }}" placeholder="e.g. Full-Stack Web Development">
                                    @error('specialization')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="years_experience">Years of Experience</label>
                                    <input type="number" id="years_experience" name="years_experience" min="0"
                                        class="form-control @error('years_experience') is-invalid @enderror"
                                        value="{{ old('years_experience', 1) }}">
                                    @error('years_experience')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="linkedin">LinkedIn Profile URL</label>
                                    <input type="url" id="linkedin" name="linkedin"
                                        class="form-control @error('linkedin') is-invalid @enderror"
                                        value="{{ old('linkedin') }}" placeholder="https://linkedin.com/in/...">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="bio">Biography / Summary</label>
                            <textarea id="bio" name="bio" rows="4"
                                class="form-control @error('bio') is-invalid @enderror"
                                placeholder="Brief background, credentials, and achievements...">{{ old('bio') }}</textarea>
                            @error('bio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-0 text-right">
                            <a href="{{ route('admin.instructors.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-check"></i> Create Instructor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

