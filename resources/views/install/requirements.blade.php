@extends('layouts.install')

@section('content')
<h2 class="h2 mb-4">{{ __('install.requirements') }}</h2>
<div class="table-responsive">
    <table class="table table-vcenter">
        <thead><tr><th>Gereksinim</th><th>Durum</th><th>Mevcut</th></tr></thead>
        <tbody>
            @foreach($requirements as $req)
            <tr>
                <td>{{ $req['label'] }}</td>
                <td>@if($req['passed'])<span class="badge bg-success">OK</span>@else<span class="badge bg-danger">FAIL</span>@endif</td>
                <td class="text-muted">{{ $req['current'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-between mt-4">
    <a href="{{ route('install.welcome') }}" class="btn btn-outline-secondary">{{ __('app.back') }}</a>
    @if($passed)
    <a href="{{ route('install.database') }}" class="btn btn-primary">{{ __('install.continue') }}</a>
    @else
    <span class="text-danger">{{ __('install.requirements_failed') }}</span>
    @endif
</div>
@endsection
