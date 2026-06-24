@extends('layouts.app')
@section('title', __('extensions.payment_methods'))
@section('content')
@include('partials.page-header', ['title' => __('extensions.payment_methods'), 'createRoute' => route('settings.payment-methods.create')])
<div class="card"><div class="table-responsive"><table class="table table-vcenter card-table">
    <thead><tr><th>Ad</th><th>Kod</th><th>Tip</th><th>Komisyon</th><th>Özellikler</th><th>{{ __('app.status') }}</th><th></th></tr></thead>
    <tbody>@forelse($methods as $m)<tr>
        <td><i class="ti {{ $m->icon }} me-1"></i> {{ $m->name }}</td>
        <td><code>{{ $m->code }}</code></td>
        <td>{{ $m->type }}</td>
        <td>@if($m->fee_type === 'none')-@elseif($m->fee_type === 'percent')%{{ $m->fee_amount }}@else{{ $m->fee_amount }}@endif</td>
        <td><small class="text-muted">{{ implode(', ', $m->features ?? []) }}</small></td>
        <td><span class="badge bg-{{ $m->is_active ? 'success' : 'secondary' }}-lt">{{ $m->is_active ? __('settings.active') : __('settings.inactive') }}</span></td>
        <td><a href="{{ route('settings.payment-methods.edit', $m) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a></td>
    </tr>@empty<tr><td colspan="7" class="text-muted">{{ __('app.no_records') }}</td></tr>@endforelse</tbody>
</table></div></div>
@endsection
