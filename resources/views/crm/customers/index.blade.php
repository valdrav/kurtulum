@extends('layouts.app')
@section('title', __('app.customers'))
@section('content')
@include('partials.page-header', ['title' => __('app.customers'), 'createRoute' => route('customers.create'), 'createPermission' => 'customers.create'])

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-4">
        <input type="search" name="search" class="form-control" placeholder="{{ __('app.search') }}..." value="{{ request('search') }}">
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">{{ __('app.status') }}</option>
            @foreach(['active','inactive','prospect'] as $st)
            <option value="{{ $st }}" @selected(request('status') === $st)>{{ type_label($st, 'customers') }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">{{ __('app.filter') }}</button>
    </div>
</form>

<div class="d-md-none ef-mobile-list mb-3">
    @forelse($customers as $c)
    @include('partials.mobile-record-card', [
        'url' => route('customers.show', $c),
        'title' => $c->company_name,
        'subtitle' => $c->email ?? $c->phone,
        'meta' => ($c->orders_count ?? 0) . ' sipariş · ' . format_money((float) ($c->sale_total_sum ?? 0), $c->currency ?? 'USD', 0),
        'badge' => type_label($c->status, 'customers'),
        'badgeClass' => 'bg-'.($c->status === 'active' ? 'success' : 'secondary').'-lt',
        'editUrl' => route('customers.edit', $c),
        'editPermission' => 'customers.edit',
        'deleteUrl' => empty($c->deletion_block_reason) ? route('customers.destroy', $c) : null,
        'deletePermission' => 'customers.delete',
        'deleteConfirm' => __('customers.delete_confirm'),
        'deleteBlockReason' => $c->deletion_block_reason,
    ])
    @empty
    <div class="card"><div class="card-body text-muted">{{ __('app.no_records') }}</div></div>
    @endforelse
</div>

<div class="card hide-mobile">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern">
            <thead>
                <tr>
                    <th>Firma</th>
                    <th>İletişim</th>
                    <th>Ülke</th>
                    <th class="text-end">{{ __('app.orders') }}</th>
                    <th class="text-end">{{ __('customers.sale_total') }}</th>
                    <th class="text-end">{{ __('finance.current_balance') }}</th>
                    <th>{{ __('app.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                <tr>
                    <td><a href="{{ route('customers.show', $c) }}"><strong>{{ $c->company_name }}</strong></a></td>
                    <td>{{ $c->email ?? $c->phone ?? '—' }}</td>
                    <td>{{ country_label($c->country) ?: '—' }}</td>
                    <td class="text-end">{{ $c->orders_count ?? 0 }}</td>
                    <td class="text-end">{{ format_money((float) ($c->sale_total_sum ?? 0), $c->currency ?? 'USD', 0) }}</td>
                    <td class="text-end">
                        @if($c->account)
                        {{ format_money((float) $c->account->current_balance, $c->account->currency, 0) }}
                        @else — @endif
                    </td>
                    <td>{{ type_label($c->status, 'customers') }}</td>
                    <td>
                        @if(can_access('customers.edit'))
                        <a href="{{ route('customers.edit', $c) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a>
                        @endif
                        @include('partials.crm-delete-button', [
                            'destroyRoute' => route('customers.destroy', $c),
                            'confirm' => __('customers.delete_confirm'),
                            'blockReason' => $c->deletion_block_reason ?? null,
                            'permission' => 'customers.delete',
                            'class' => 'btn btn-sm btn-ghost-danger',
                            'iconOnly' => true,
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())<div class="card-footer">{{ $customers->links() }}</div>@endif
</div>
@if($customers->hasPages())<div class="d-md-none mt-2">{{ $customers->links() }}</div>@endif
@endsection
