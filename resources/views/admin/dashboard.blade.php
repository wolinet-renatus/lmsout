@extends('layouts.admin')

@section('title', ($analytics['scope']['role_label'] ?? 'Admin') . ' Dashboard')

@section('main-content')
    <style>
        .dashboard-card-pagination nav {
            margin: 0;
        }
        .dashboard-card-pagination .pagination {
            margin-bottom: 0;
        }
        .dashboard-card-pagination .page-link {
            padding: 0.2rem 0.5rem;
            font-size: 0.78rem;
            line-height: 1.35;
        }
    </style>
    <div class="dashboard-v2">
        @includeIf('admin.dashboards.' . ($analytics['profile'] ?? 'custom'))
    </div>
@endsection
