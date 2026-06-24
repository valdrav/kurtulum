@extends('layouts.app')
@section('title', __('app.customers'))
@section('content')
@include('partials.page-header', ['title' => __('app.customers'), 'createRoute' => route('customers.create'), 'createPermission' => 'customers.create'])

<div class="card mb-3">
    <div class="card-body py-3">
        <form class="row g-2" method="GET">
            <div class="col-12 col-md-auto flex-fill"><input type="text" name="search" class="form-control" placeholder="{{ __('app.search') }}" value="{{ request('search') }}"></div>
            <div class="col-12 col-md-auto"><button class="btn btn-outline-primary w-100">{{ __('app.filter') }}</button></div>
        </form>
    </div>
</div>

<div class="d-md-none ef-mobile-list mb-3">
    @forelse($customers as $c)
    @include('partials.mobile-record-card', [
        'url' => route('customers.show', $c),
        'title' => $c->company_name,
        'subtitle' => $c->email ?? $c->phone,
        'meta' => $c->country,
        'badge' => type_label($c->status, 'customers'),
        'badgeClass' => 'bg-'.($c->status === 'active' ? 'success' : 'secondary').'-lt',
        'editUrl' => route('customers.edit', $c),
        'editPermission' => 'customers.edit',
    ])
    @empty
    <div class="card"><div class="card-body text-muted">{{ __('app.no_records') }}</div></div>
    @endforelse
</div>

<div class="card hide-mobile">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern">
            <thead><tr><th>Firma</th><th>İletişim</th><th>Ülke</th><th>{{ __('app.status') }}</th><th class="w-1"></th></tr></thead>
            <tbody>
                @forelse($customers as $c)
                <tr>
                    <td><a href="{{ route('customers.show', $c) }}">{{ $c->company_name }}</a></td>
                    <td>{{ $c->email ?? $c->phone }}</td>
                    <td>{{ $c->country }}</td>
                    <td><span class="badge bg-{{ $c->status === 'active' ? 'success' : 'secondary' }}-lt">{{ type_label($c->status, 'customers') }}</span></td>
                    <td>
                        @if(can_access('customers.edit'))
                        <a href="{{ route('customers.edit', $c) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())<div class="card-footer">{{ $customers->links() }}</div>@endif
</div>
@if($customers->hasPages())<div class="d-md-none mt-2">{{ $customers->links() }}</div>@endif
@endsection
