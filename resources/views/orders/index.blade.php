@extends('layouts.app')
@section('title', __('app.orders'))
@section('content')
@include('partials.page-header', ['title' => __('app.orders'), 'createRoute' => route('orders.create'), 'createPermission' => 'orders.create'])

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <input type="search" name="search" class="form-control" placeholder="Sipariş no..." value="{{ request('search') }}">
    </div>
    <div class="col-md-3">
        <select name="supplier" class="form-select">
            <option value="">{{ __('orders.supplier_purchase') }}</option>
            @foreach($suppliers as $s)
            <option value="{{ $s->id }}" @selected(request('supplier') == $s->id)>{{ $s->company_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">{{ __('app.status') }}</option>
            @foreach(config('ticari.order_statuses') as $st)
            <option value="{{ $st }}" @selected(request('status') === $st)>{{ status_label($st, 'order') }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">{{ __('app.filter') }}</button></div>
</form>

<div class="d-flex flex-wrap gap-2 mb-3">
    @if(can_access('orders.view'))
    <a href="{{ route('orders.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-download me-1"></i>{{ __('app.export') }}
    </a>
    @endif
    @if(request('trashed'))
    <a href="{{ route('orders.index', request()->except('trashed')) }}" class="btn btn-ghost-secondary btn-sm">Aktif siparişler</a>
    @else
    <a href="{{ route('orders.index', array_merge(request()->query(), ['trashed' => 1])) }}" class="btn btn-ghost-secondary btn-sm">Silinen siparişler</a>
    @endif
</div>

<div class="d-md-none ef-mobile-list mb-3">
    @forelse($orders as $o)
    @php $orderDeleteBlock = request('trashed') ? null : app(\App\Services\RecordDeletionPolicy::class)->orderDeleteBlockReason($o); @endphp
    @include('partials.mobile-record-card', [
        'url' => route('orders.show', $o),
        'title' => $o->order_number,
        'subtitle' => $o->customer?->company_name,
        'meta' => ($o->supplier?->company_name ? 'Alış: '.$o->supplier->company_name.' · ' : '') . $o->order_date->format('d.m.Y'),
        'badge' => status_label($o->status, 'order'),
        'editUrl' => request('trashed') ? null : route('orders.edit', $o),
        'editPermission' => 'orders.edit',
        'deleteUrl' => request('trashed') ? null : route('orders.destroy', $o),
        'deletePermission' => 'orders.delete',
        'deleteConfirm' => __('orders.delete_confirm'),
        'deleteBlockReason' => $orderDeleteBlock,
        'restoreUrl' => request('trashed') && can_access('orders.delete') ? route('orders.restore', $o->id) : null,
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
                    <th>No</th>
                    <th>{{ __('app.customers') }}</th>
                    <th>{{ __('orders.supplier_purchase') }}</th>
                    <th>{{ __('app.date') }}</th>
                    <th class="text-end">{{ __('orders.total_purchase') }}</th>
                    <th class="text-end">{{ __('orders.total_sale') }}</th>
                    <th>{{ __('app.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                <tr>
                    <td><a href="{{ route('orders.show', $o) }}">{{ $o->order_number }}</a></td>
                    <td>{{ $o->customer?->company_name ?? '—' }}</td>
                    <td>
                        @if($o->supplier)
                        <a href="{{ route('suppliers.show', $o->supplier) }}">{{ $o->supplier->company_name }}</a>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $o->order_date->format('d.m.Y') }}</td>
                    <td class="text-end">{{ format_money((float) $o->purchase_total, $o->currency, 2) }}</td>
                    <td class="text-end">{{ format_money((float) $o->total_amount, $o->currency, 2) }}</td>
                    <td>{{ status_label($o->status, 'order') }}</td>
                    <td>
                        @if(request('trashed'))
                            @if(can_access('orders.delete'))
                            @include('partials.restore-form', ['action' => route('orders.restore', $o->id), 'class' => 'btn btn-sm btn-outline-success', 'iconOnly' => true])
                            @endif
                        @else
                            @if(can_access('orders.edit'))
                            <a href="{{ route('orders.edit', $o) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a>
                            @endif
                            @if(can_access('orders.delete'))
                            @include('partials.policy-delete-form', [
                                'action' => route('orders.destroy', $o),
                                'confirm' => __('orders.delete_confirm'),
                                'blockReason' => app(\App\Services\RecordDeletionPolicy::class)->orderDeleteBlockReason($o),
                                'class' => 'btn btn-sm btn-ghost-danger',
                                'iconOnly' => true,
                            ])
                            @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())<div class="card-footer">{{ $orders->links() }}</div>@endif
</div>
@if($orders->hasPages())<div class="d-md-none mt-2">{{ $orders->links() }}</div>@endif
@endsection
