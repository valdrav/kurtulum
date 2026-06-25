@extends('layouts.app')
@section('title', __('finance.wallet'))
@section('content')
@include('partials.page-header', ['title' => __('finance.wallet'), 'subtitle' => __('finance.wallet_subtitle')])
@include('partials.finance-nav')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="alert alert-info py-2 small mb-3">{{ __('finance.wallet_hint') }}</div>

@if($walletUser->id !== auth()->id())
<div class="alert alert-warning py-2 small mb-3">
    <i class="ti ti-user me-1"></i>{{ __('finance.wallet_view_user') }}: <strong>{{ $walletUser->name }}</strong>
</div>
@else
<div class="text-muted small mb-3"><i class="ti ti-user me-1"></i>{{ __('finance.wallet_my_account') }}: <strong>{{ $walletUser->name }}</strong></div>
@endif

<form method="GET" class="row g-2 align-items-end mb-3">
    @if($selectableUsers->isNotEmpty())
    <div class="col-md-3">
        <label class="form-label small mb-0">{{ __('finance.wallet_owner') }}</label>
        <select name="user" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($selectableUsers as $u)
            <option value="{{ $u->uuid }}" @selected($walletUser->id === $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="col-md-3">
        <label class="form-label small mb-0">{{ __('finance.wallet_account') }}</label>
        <select name="wallet" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">{{ __('finance.wallet_all_accounts') }}</option>
            @foreach($walletList as $w)
            <option value="{{ $w->uuid }}" @selected($selectedWallet?->uuid === $w->uuid)>{{ $w->displayLabel() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small mb-0">{{ __('finance.fiscal_year') }}</label>
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
            @for($y = now()->year; $y >= now()->year - 5; $y--)
            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
            @endfor
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-0">{{ __('finance.entry_type') }}</label>
        <select name="type" class="form-select form-select-sm">
            <option value="">{{ __('finance.all_types') }}</option>
            <option value="deposit" @selected(request('type') === 'deposit')>{{ __('finance.wallet_types.deposit') }}</option>
            <option value="expense" @selected(request('type') === 'expense')>{{ __('finance.wallet_types.expense') }}</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-0">{{ __('finance.filter_search') }}</label>
        <input type="search" name="search" class="form-control form-control-sm" value="{{ request('search') }}">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">{{ __('app.filter') ?? 'Filtrele' }}</button>
    </div>
</form>

<div class="row row-cards mb-4">
    <div class="col-md-4">
        <div class="card stat-card border-primary h-100">
            <div class="card-body">
                <div class="subheader">{{ $selectedWallet ? __('finance.wallet_balance') : __('finance.wallet_total_balance') }}</div>
                <div class="h1 mb-0 {{ $totalBalance >= 0 ? 'text-primary' : 'text-red' }}">
                    {{ number_format($totalBalance, 2, ',', '.') }} {{ $selectedWallet?->currency ?? 'TRY' }}
                </div>
                @if($selectedWallet)
                <div class="text-muted small mt-1">{{ $selectedWallet->displayLabel() }}</div>
                @if($selectedWallet->iban)
                <div class="text-muted small">{{ $selectedWallet->bank_name }} · {{ $selectedWallet->iban }}</div>
                @endif
                @else
                <div class="text-muted small mt-1">{{ $walletList->count() }} {{ __('finance.wallet_accounts') }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row g-3">
            <div class="col-4">
                <div class="card stat-card h-100"><div class="card-body py-3">
                    <div class="subheader small">{{ __('finance.wallet_deposit_ytd') }}</div>
                    <div class="h3 text-green mb-0">{{ number_format($summary['deposit'], 2, ',', '.') }} ₺</div>
                </div></div>
            </div>
            <div class="col-4">
                <div class="card stat-card h-100"><div class="card-body py-3">
                    <div class="subheader small">{{ __('finance.wallet_expense_ytd') }}</div>
                    <div class="h3 text-red mb-0">{{ number_format($summary['expense'], 2, ',', '.') }} ₺</div>
                </div></div>
            </div>
            <div class="col-4">
                <div class="card stat-card h-100"><div class="card-body py-3">
                    <div class="subheader small">{{ __('finance.wallet_net_ytd') }}</div>
                    <div class="h3 mb-0 {{ $summary['net'] >= 0 ? 'text-green' : 'text-red' }}">{{ number_format($summary['net'], 2, ',', '.') }} ₺</div>
                </div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    @if(can_access('finance.create'))
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('finance.wallet_quick_entry') }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.wallet.transactions.store') }}">
                    @csrf
                    @if($selectableUsers->isNotEmpty())
                    <input type="hidden" name="user" value="{{ $walletUser->uuid }}">
                    @endif
                    <div class="mb-2">
                        <label class="form-label">{{ __('finance.wallet_account') }} *</label>
                        <select name="company_wallet_id" class="form-select" required>
                            @foreach($walletList as $w)
                            <option value="{{ $w->id }}" @selected($selectedWallet?->id === $w->id)>{{ $w->displayLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-5">
                            <label class="form-label">{{ __('finance.entry_type') }} *</label>
                            <select name="type" class="form-select" required>
                                <option value="deposit">{{ __('finance.wallet_types.deposit') }}</option>
                                <option value="expense">{{ __('finance.wallet_types.expense') }}</option>
                            </select>
                        </div>
                        <div class="col-7">
                            <label class="form-label">{{ __('app.date') }} *</label>
                            <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('finance.wallet_description') }} *</label>
                        <input type="text" name="description" class="form-control" placeholder="{{ __('finance.wallet_description_hint') }}" required maxlength="255">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('app.amount') }} *</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required min="0.01">
                    </div>
                    <details class="mb-2 small">
                        <summary class="text-muted mb-2" style="cursor:pointer">{{ __('finance.optional_details') }}</summary>
                        <div class="mb-2">
                            <label class="form-label">{{ __('finance.wallet_counterparty') }}</label>
                            <input type="text" name="counterparty" class="form-control form-control-sm" maxlength="255" placeholder="{{ __('finance.wallet_counterparty_hint') }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">{{ __('finance.receipt_no') }}</label>
                            <input type="text" name="receipt_no" class="form-control form-control-sm" maxlength="100">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ __('finance.notes') }}</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" maxlength="2000"></textarea>
                        </div>
                    </details>
                    <button type="submit" class="btn btn-primary w-100">{{ __('app.save') }}</button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="col-lg-{{ can_access('finance.create') ? '8' : '12' }}">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('finance.wallet_accounts') }}</h3>
                @if(can_access('finance.create'))
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#newWalletAccount">
                    <i class="ti ti-plus"></i>
                </button>
                @endif
            </div>
            @if(can_access('finance.create'))
            <div class="collapse" id="newWalletAccount">
                <div class="card-body border-bottom bg-light py-3">
                    <form method="POST" action="{{ route('finance.wallet.accounts.store') }}" class="row g-2">
                        @csrf
                        @if($selectableUsers->isNotEmpty())
                        <input type="hidden" name="user" value="{{ $walletUser->uuid }}">
                        @endif
                        <div class="col-md-4">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Hesap adı (örn. Kişisel IBAN)" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="holder_name" class="form-control form-control-sm" placeholder="{{ __('finance.wallet_holder') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="currency" class="form-select form-select-sm">
                                @foreach(registry()->currencyCodes() as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" step="0.01" name="opening_balance" class="form-control form-control-sm" placeholder="{{ __('finance.opening_balance') }}">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="ti ti-device-floppy"></i></button>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="bank_name" class="form-control form-control-sm" placeholder="{{ __('finance.wallet_bank') }}">
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="iban" class="form-control form-control-sm" placeholder="{{ __('finance.wallet_iban') }}">
                        </div>
                    </form>
                </div>
            </div>
            @endif
            <div class="list-group list-group-flush">
                @forelse($walletList as $w)
                <a href="{{ route('finance.wallet', array_filter(['wallet' => $w->uuid, 'user' => $selectableUsers->isNotEmpty() ? $walletUser->uuid : null, 'year' => $year])) }}"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $selectedWallet?->id === $w->id ? 'active' : '' }}">
                    <div>
                        <strong>{{ $w->name }}</strong>
                        @if($w->holder_name)<div class="small opacity-75">{{ $w->holder_name }}</div>@endif
                        @if($w->bank_name)<div class="small opacity-75">{{ $w->bank_name }}</div>@endif
                    </div>
                    <span class="fw-bold {{ $w->current_balance >= 0 ? 'text-green' : 'text-red' }}">
                        {{ number_format($w->current_balance, 2, ',', '.') }} {{ $w->currency }}
                    </span>
                </a>
                @empty
                <div class="list-group-item text-muted">{{ __('app.no_records') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title mb-0">{{ __('finance.wallet_recent') }} — {{ $year }}</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter table-sm card-table mb-0">
            <thead>
                <tr>
                    <th>{{ __('app.date') }}</th>
                    @unless($selectedWallet)<th>{{ __('finance.wallet_account') }}</th>@endunless
                    <th>{{ __('finance.entry_type') }}</th>
                    <th>{{ __('finance.wallet_description') }}</th>
                    <th>{{ __('finance.wallet_counterparty') }}</th>
                    <th class="text-end">{{ __('app.amount') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $item)
                <tr>
                    <td>{{ $item->transaction_date->format('d.m.Y') }}</td>
                    @unless($selectedWallet)<td>{{ $item->wallet?->name }}</td>@endunless
                    <td>
                        <span class="badge bg-{{ $item->isDeposit() ? 'success' : 'danger' }}-lt">{{ $item->typeLabel() }}</span>
                    </td>
                    <td>{{ $item->description }}</td>
                    <td class="text-muted">{{ $item->counterparty ?: '—' }}</td>
                    <td class="text-end {{ $item->isDeposit() ? 'text-green' : 'text-red' }}">
                        {{ $item->isDeposit() ? '+' : '−' }}{{ number_format($item->amount, 2, ',', '.') }} {{ $item->currency }}
                    </td>
                    <td class="text-end text-nowrap">
                        @if(can_access('finance.edit'))
                        <button type="button" class="btn btn-sm btn-ghost-primary" data-bs-toggle="collapse" data-bs-target="#edit-wallet-{{ $item->uuid }}">
                            <i class="ti ti-edit"></i>
                        </button>
                        @endif
                        @if(can_access('finance.delete') || can_access('finance.create'))
                        <form action="{{ route('finance.wallet.transactions.destroy', $item) }}" method="POST" class="d-inline"
                              onsubmit="return confirm(@json(__('app.delete_confirm') ?? 'Silinsin mi?'))">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @if(can_access('finance.edit'))
                <tr class="collapse" id="edit-wallet-{{ $item->uuid }}">
                    <td colspan="{{ $selectedWallet ? 6 : 7 }}">
                        <form method="POST" action="{{ route('finance.wallet.transactions.update', $item) }}" class="row g-2 align-items-end p-2 bg-light rounded">
                            @csrf @method('PUT')
                            <div class="col-md-2">
                                <label class="form-label small mb-0">{{ __('finance.wallet_account') }}</label>
                                <select name="company_wallet_id" class="form-select form-select-sm">
                                    @foreach($walletList as $w)
                                    <option value="{{ $w->id }}" @selected($item->company_wallet_id === $w->id)>{{ $w->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-0">{{ __('finance.entry_type') }}</label>
                                <select name="type" class="form-select form-select-sm">
                                    <option value="deposit" @selected($item->type === 'deposit')>{{ __('finance.wallet_types.deposit') }}</option>
                                    <option value="expense" @selected($item->type === 'expense')>{{ __('finance.wallet_types.expense') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-0">{{ __('app.date') }}</label>
                                <input type="date" name="transaction_date" class="form-control form-control-sm" value="{{ $item->transaction_date->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-0">{{ __('app.amount') }}</label>
                                <input type="number" step="0.01" name="amount" class="form-control form-control-sm" value="{{ $item->amount }}" required min="0.01">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-0">{{ __('finance.wallet_description') }}</label>
                                <input type="text" name="description" class="form-control form-control-sm" value="{{ $item->description }}" required>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-sm btn-primary w-100">{{ __('app.save') }}</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @endif
                @empty
                <tr><td colspan="{{ $selectedWallet ? 6 : 7 }}" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
