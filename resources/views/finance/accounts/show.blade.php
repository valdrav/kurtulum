@extends('layouts.app')
@section('title', $account->name)
@section('content')
@include('partials.page-header', ['title' => $account->name])
@include('partials.finance-nav')

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card"><div class="card-body">
        <div class="subheader">{{ __('finance.current_balance') }}</div>
        <div class="h1 {{ $account->balance >= 0 ? 'text-green' : 'text-red' }}">{{ format_money($account->balance, $account->currency, 2) }}</div>
    </div></div></div>
    <div class="col-md-8"><div class="card"><div class="card-body">
        <dl class="row mb-0 small">
            <dt class="col-sm-3">Kod</dt><dd class="col-sm-9">{{ $account->code }}</dd>
            <dt class="col-sm-3">Tip</dt><dd class="col-sm-9">{{ $account->typeLabel() }}</dd>
            @if($account->customer)<dt class="col-sm-3">Müşteri</dt><dd class="col-sm-9">{{ $account->customer->company_name }}</dd>@endif
            @if($account->supplier)<dt class="col-sm-3">Tedarikçi</dt><dd class="col-sm-9">{{ $account->supplier->company_name }}</dd>@endif
            @if($account->notes)<dt class="col-sm-3">Not</dt><dd class="col-sm-9">{{ $account->notes }}</dd>@endif
        </dl>
        @if(can_access('finance.edit'))
        <a href="{{ route('finance.accounts.edit', $account) }}" class="btn btn-sm btn-outline-primary mt-2"><i class="ti ti-edit"></i> Düzenle</a>
        @endif
    </div></div></div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">{{ __('finance.transactions') }}</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('app.date') }}</th>
                    <th>{{ __('finance.counterparty') }}</th>
                    <th>Tip</th>
                    <th>{{ __('app.description') }}</th>
                    <th>{{ __('app.amount') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($account->transactions as $t)
                <tr>
                    <td>{{ $t->transaction_date->format('d.m.Y') }}</td>
                    <td>{{ $t->counterpartyLabel() ?: '—' }}</td>
                    <td><span class="badge bg-{{ $t->type==='credit'?'green':'red' }}-lt">{{ $t->typeLabelTr() }}</span></td>
                    <td>{{ $t->description }}</td>
                    <td>{{ format_money((float) $t->amount, $t->currency, 2) }}</td>
                    <td class="text-end">
                        @if(can_access('finance.edit') && $t->editUrl())
                        <a href="{{ $t->editUrl() }}" class="btn btn-sm btn-ghost-primary" title="{{ __('finance.edit_transaction') }}"><i class="ti ti-edit"></i></a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-muted text-center">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
