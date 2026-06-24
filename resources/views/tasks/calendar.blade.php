@extends('layouts.app')
@section('title', __('app.calendar'))
@section('content')
@include('partials.page-header', ['title' => __('app.calendar')])
<div class="card"><div class="list-group list-group-flush">@forelse($events as $e)<div class="list-group-item"><strong>{{ $e->title }}</strong><div class="text-muted">{{ $e->start_at->format('d.m.Y H:i') }}</div></div>@empty<div class="list-group-item text-muted">{{ __('app.no_records') }}</div>@endforelse</div></div>
@endsection
