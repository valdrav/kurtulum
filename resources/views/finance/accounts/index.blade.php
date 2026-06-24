@extends('layouts.app')
@section('title', __('finance.accounts'))
@section('content')
@include('partials.page-header', ['title' => __('finance.cari_accounts')])
@include('partials.finance-nav')

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('finance.payments') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-arrow-up-right"></i> {{ __('app.payments') }}</a>
    <a href="{{ route('finance.collections') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-arrow-down-left"></i> {{ __('app.collections') }}</a>
    @if(can_access('finance.create'))
    <a href="{{ route('finance.accounts.create') }}" class="btn btn-sm btn-primary ms-auto"><i class="ti ti-plus"></i> {{ __('finance.new_account') }}</a>
    @endif
</div>

<div class="d-md-none ef-mobile-list mb-3">
    @forelse($accounts as $a)
    @include('partials.mobile-record-card', [
        'url' => route('finance.accounts.show', $a),
        'title' => $a->name,
        'subtitle' => $a->code,
        'meta' => $a->typeLabel().' · '.$a->currency,
        'badge' => number_format($a->balance, 2, ',', '.'),
        'badgeClass' => $a->balance >= 0 ? 'text-green' : 'text-red',
        'editUrl' => route('finance.accounts.edit', $a),
        'editPermission' => 'finance.edit',
    ])
    @empty
    <div class="card"><div class="card-body text-muted">{{ __('app.no_records') }}</div></div>
    @endforelse
</div>

<div class="card hide-mobile">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern">
            <thead><tr><th>Kod</th><th>Ad</th><th>Tip</th><th>Para Birimi</th><th>Bakiye</th><th></th></tr></thead>
            <tbody>
                @forelse($accounts as $a)
                <tr>
                    <td>{{ $a->code }}</td>
                    <td><a href="{{ route('finance.accounts.show', $a) }}">{{ $a->name }}</a></td>
                    <td>{{ $a->typeLabel() }}</td>
                    <td>{{ $a->currency }}</td>
                    <td class="{{ $a->balance >= 0 ? 'text-green' : 'text-red' }}">{{ number_format($a->balance, 2, ',', '.') }}</td>
                    <td class="text-end">
                        @if(can_access('finance.edit'))
                        <a href="{{ route('finance.accounts.edit', $a) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($accounts->hasPages())<div class="card-footer">{{ $accounts->links() }}</div>@endif
</div>
@if($accounts->hasPages())<div class="d-md-none mt-2">{{ $accounts->links() }}</div>@endif
@endsection
