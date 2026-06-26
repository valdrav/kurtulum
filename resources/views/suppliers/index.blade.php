@extends('layouts.app')
@section('title', __('app.suppliers'))
@section('content')
@include('partials.page-header', ['title' => __('app.suppliers'), 'createRoute' => route('suppliers.create'), 'createPermission' => 'suppliers.create'])

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-4">
        <input type="search" name="search" class="form-control" placeholder="{{ __('app.search') }}..." value="{{ request('search') }}">
    </div>
    <div class="col-md-2">
        <select name="type" class="form-select">
            <option value="">{{ __('suppliers.type') }}</option>
            @foreach(['manufacturer','trader','logistics','service'] as $t)
            <option value="{{ $t }}" @selected(request('type') === $t)>{{ type_label($t, 'suppliers') }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">{{ __('app.status') }}</option>
            <option value="active" @selected(request('status') === 'active')>{{ __('suppliers.statuses.active') }}</option>
            <option value="inactive" @selected(request('status') === 'inactive')>{{ __('suppliers.statuses.inactive') }}</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">{{ __('app.filter') }}</button>
    </div>
</form>

<div class="d-md-none ef-mobile-list mb-3">
    @forelse($suppliers as $s)
    @include('partials.mobile-record-card', [
        'url' => route('suppliers.show', $s),
        'title' => $s->company_name,
        'subtitle' => type_label($s->type, 'suppliers'),
        'meta' => ($s->orders_count ?? 0) . ' sipariş · ' . format_money((float) ($s->purchase_total_sum ?? 0), $s->currency ?? 'USD', 0),
        'badge' => type_label($s->status, 'suppliers'),
        'editUrl' => route('suppliers.edit', $s),
        'editPermission' => 'suppliers.edit',
        'deleteUrl' => empty($s->deletion_block_reason) ? route('suppliers.destroy', $s) : null,
        'deletePermission' => 'suppliers.delete',
        'deleteConfirm' => __('suppliers.delete_confirm'),
        'deleteBlockReason' => $s->deletion_block_reason,
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
                    <th>Tip</th>
                    <th>Ülke</th>
                    <th class="text-end">{{ __('app.orders') }}</th>
                    <th class="text-end">{{ __('suppliers.purchase_total') }}</th>
                    <th class="text-end">{{ __('finance.current_balance') }}</th>
                    <th>{{ __('app.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $s)
                <tr>
                    <td><a href="{{ route('suppliers.show', $s) }}"><strong>{{ $s->company_name }}</strong></a></td>
                    <td>{{ type_label($s->type, 'suppliers') }}</td>
                    <td>{{ country_label($s->country) ?: '—' }}</td>
                    <td class="text-end">{{ $s->orders_count ?? 0 }}</td>
                    <td class="text-end">{{ format_money((float) ($s->purchase_total_sum ?? 0), $s->currency ?? 'USD', 0) }}</td>
                    <td class="text-end">
                        @if($s->account)
                        {{ format_money((float) $s->account->current_balance, $s->account->currency, 0) }}
                        @else — @endif
                    </td>
                    <td>{{ type_label($s->status, 'suppliers') }}</td>
                    <td>
                        @if(can_access('suppliers.edit'))
                        <a href="{{ route('suppliers.edit', $s) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a>
                        @endif
                        @include('partials.crm-delete-button', [
                            'destroyRoute' => route('suppliers.destroy', $s),
                            'confirm' => __('suppliers.delete_confirm'),
                            'blockReason' => $s->deletion_block_reason ?? null,
                            'permission' => 'suppliers.delete',
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
    @if($suppliers->hasPages())<div class="card-footer">{{ $suppliers->links() }}</div>@endif
</div>
@if($suppliers->hasPages())<div class="d-md-none mt-2">{{ $suppliers->links() }}</div>@endif
@endsection
