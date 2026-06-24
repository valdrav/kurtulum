@extends('layouts.app')
@section('title', __('extensions.currencies'))
@section('content')
@include('partials.page-header', ['title' => __('extensions.currencies')])
<div class="row">
    <div class="col-lg-4">
        <div class="card"><div class="card-header">{{ __('extensions.add_currency') }}</div><div class="card-body">
            <form method="POST" action="{{ route('settings.currencies.store') }}">@csrf
                <div class="mb-2"><input type="text" name="code" class="form-control" placeholder="EUR" required maxlength="10"></div>
                <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Euro" required></div>
                <div class="mb-2"><input type="text" name="symbol" class="form-control" placeholder="¥"></div>
                <div class="mb-2"><input type="number" name="exchange_rate" class="form-control" placeholder="Kur" step="0.00000001" required></div>
                <button type="submit" class="btn btn-primary w-100">{{ __('app.save') }}</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="table-responsive"><table class="table table-vcenter card-table">
            <thead><tr><th>Kod</th><th>Sembol</th><th>Kur</th><th>{{ __('app.status') }}</th></tr></thead>
            <tbody>@foreach($currencies as $c)<tr>
                <td><strong>{{ $c->code }}</strong> @if($c->is_default)<span class="badge bg-primary">{{ __('settings.default') }}</span>@endif</td>
                <td>{{ $c->symbol }}</td>
                <td>{{ number_format($c->exchange_rate, 4) }}</td>
                <td><span class="badge bg-{{ $c->is_active ? 'success' : 'secondary' }}-lt">{{ $c->is_active ? __('settings.active') : __('settings.inactive') }}</span></td>
            </tr>@endforeach</tbody>
        </table></div></div>
    </div>
</div>
@endsection
