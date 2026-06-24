@extends('layouts.install')

@section('content')
<div class="text-center">
    <div class="mb-4"><span class="avatar avatar-xl bg-success-lt"><i class="ti ti-check fs-1"></i></span></div>
    <h2 class="h2">{{ __('install.complete_title') }}</h2>
    <p class="text-muted mb-4">{{ __('install.complete_desc') }}</p>
    <a href="{{ route('login') }}" class="btn btn-primary btn-lg">{{ __('install.go_login') }}</a>
</div>
@endsection
