@extends('layouts.app')
@section('title', __('app.finance'))
@section('content')
@include('partials.page-header', ['title' => __('finance.hub')])
@include('partials.finance-nav')

<div class="row row-cards mb-4">
    <div class="col-md-3">
        <a href="{{ route('finance.treasury') }}" class="card stat-card text-decoration-none h-100">
            <div class="card-body">
                <div class="subheader">{{ __('finance.treasury') }}</div>
                <div class="h3 mb-0 text-primary"><i class="ti ti-cash"></i></div>
                <div class="text-muted small">{{ __('finance.treasury_hint') }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">{{ __('finance.receivables') }}</div>
            <div class="h1 text-green mb-0">{{ number_format($totalReceivable, 2, ',', '.') }} ₺</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">{{ __('finance.payables') }}</div>
            <div class="h1 text-red mb-0">{{ number_format($totalPayable, 2, ',', '.') }} ₺</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">{{ __('finance.cari_accounts') }}</div>
            <div class="h1 mb-0">{{ $accounts->total() }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h3 class="card-title">{{ __('app.payments') }}</h3></div>
            <div class="list-group list-group-flush">
                @forelse($recentPayments as $p)
                <a href="{{ route('finance.payments.show', $p) }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span>{{ $p->payment_number }} · {{ $p->account?->name }}</span>
                    <strong class="text-red">{{ number_format($p->amount, 2, ',', '.') }} {{ $p->currency }}</strong>
                </a>
                @empty
                <div class="list-group-item text-muted">{{ __('app.no_records') }}</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h3 class="card-title">{{ __('app.collections') }}</h3></div>
            <div class="list-group list-group-flush">
                @forelse($recentCollections as $c)
                <a href="{{ route('finance.collections.show', $c) }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span>{{ $c->collection_number }} · {{ $c->account?->name }}</span>
                    <strong class="text-green">{{ number_format($c->amount, 2, ',', '.') }} {{ $c->currency }}</strong>
                </a>
                @empty
                <div class="list-group-item text-muted">{{ __('app.no_records') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">{{ __('finance.accounts') }}</h3>
        @if(can_access('finance.create'))
        <a href="{{ route('finance.accounts.create') }}" class="btn btn-sm btn-primary"><i class="ti ti-plus"></i> {{ __('finance.new_account') }}</a>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Kod</th><th>Ad</th><th>Tip</th><th>Bakiye</th><th></th></tr></thead>
            <tbody>
                @forelse($accounts as $a)
                <tr>
                    <td>{{ $a->code }}</td>
                    <td><a href="{{ route('finance.accounts.show', $a) }}">{{ $a->name }}</a></td>
                    <td><span class="badge bg-secondary-lt">{{ $a->typeLabel() }}</span></td>
                    <td class="{{ $a->balance >= 0 ? 'text-green' : 'text-red' }}">{{ number_format($a->balance, 2, ',', '.') }} {{ $a->currency }}</td>
                    <td><a href="{{ route('finance.accounts.edit', $a) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($accounts->hasPages())<div class="card-footer">{{ $accounts->links() }}</div>@endif
</div>
@endsection
