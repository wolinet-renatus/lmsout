@extends('layouts.admin')

@section('title', 'Notification Templates')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Notification Templates</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        @foreach ($templates as $tpl)
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $tpl->name }}</h4>
                        <span class="badge badge-info">{{ strtoupper($tpl->channel) }}</span>
                    </div>
                    <form action="{{ route('admin.campus.templates.update', $tpl) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label>Subject</label>
                                <input type="text" name="subject" class="form-control" value="{{ $tpl->subject }}">
                            </div>
                            <div class="form-group">
                                <label>Body</label>
                                <textarea name="body" rows="5" class="form-control" required>{{ $tpl->body }}</textarea>
                                @if (!empty($tpl->variables))
                                    <small class="text-muted">Placeholders: @foreach ($tpl->variables as $v) <code>{{ '{'.$v.'}' }}</code> @endforeach</small>
                                @endif
                            </div>
                            <div class="form-group mb-0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="active_{{ $tpl->id }}" name="is_active" value="1" {{ $tpl->is_active ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="active_{{ $tpl->id }}">Active</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary btn-sm">Save Template</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endsection
