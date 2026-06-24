@extends('layouts.install')

@section('content')
<h2 class="h2 text-center mb-4">{{ __('install.welcome_title') }}</h2>
<p class="text-muted text-center mb-4">{{ __('install.welcome_desc') }}</p>
<div class="text-center">
    <a href="{{ route('install.requirements') }}" class="btn btn-primary btn-lg">
        <i class="ti ti-arrow-right me-1"></i> {{ __('install.start') }}
    </a>
</div>
@endsection
