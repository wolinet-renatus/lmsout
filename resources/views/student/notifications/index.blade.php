@extends('layouts.student')

@section('content')
    @include('partials.portal-notifications-list', ['portalType' => 'student'])
@endsection
