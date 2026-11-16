@extends('layouts.instructor')

@section('content')
    @include('partials.portal-notifications-list', ['portalType' => 'instructor'])
@endsection
