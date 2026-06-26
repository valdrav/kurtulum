@extends('layouts.app')
@section('title', __('finance.treasury'))
@section('content')
@include('partials.page-header', ['title' => __('finance.treasury'), 'subtitle' => __('finance.treasury_subtitle')])
@include('partials.finance-nav')

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-auto">
        <label class="form-label small mb-0">{{ __('finance.fiscal_year') }}</label>
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
            @for($y = now()->year; $y >= now()->year - 5; $y--)
            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
            @endfor
        </select>
    </div>
</form>

<div class="row row-cards mb-4">
    <div class="col-md-4">
        <div class="card stat-card border-primary h-100">
            <div class="card-body">
                <div class="subheader">{{ __('finance.total_cash_balance') }}</div>
                <div class="h1 mb-0 text-primary">{{ number_format($totalCash, 2, ',', '.') }} ₺</div>
                <div class="text-muted small mt-1">{{ $treasuryAccounts->count() }} {{ __('finance.treasury_account') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row g-3">
            <div class="col-4">
                <div class="card stat-card h-100"><div class="card-body py-3">
                    <div class="subheader small">{{ __('finance.income_ytd') }}</div>
                    <div class="h3 text-green mb-0">{{ number_format($summary['income'], 2, ',', '.') }} ₺</div>
                </div></div>
            </div>
            <div class="col-4">
                <div class="card stat-card h-100"><div class="card-body py-3">
                    <div class="subheader small">{{ __('finance.expense_ytd') }}</div>
                    <div class="h3 text-red mb-0">{{ number_format($summary['expense'], 2, ',', '.') }} ₺</div>
                </div></div>
            </div>
            <div class="col-4">
                <div class="card stat-card h-100"><div class="card-body py-3">
                    <div class="subheader small">{{ __('finance.net_ytd') }}</div>
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
            <div class="card-header"><h3 class="card-title mb-0">{{ __('finance.quick_entry') }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.income-expenses.store') }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="treasury">
                    @include('partials.income-expense-form', [
                        'treasuryAccounts' => $treasuryAccounts,
                        'paymentMethods' => $paymentMethods,
                        'defaultTreasuryId' => $defaultTreasury->id,
                        'compact' => true,
                        'orders' => $orders ?? [],
                    ])
                    <button type="submit" class="btn btn-primary w-100 mt-2">{{ __('app.save') }}</button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="col-lg-{{ can_access('finance.create') ? '8' : '12' }}">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('finance.treasury_accounts') }}</h3>
                @if(can_access('finance.create'))
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#newTreasuryAccount">
                    <i class="ti ti-plus"></i>
                </button>
                @endif
            </div>
            @if(can_access('finance.create'))
            <div class="collapse" id="newTreasuryAccount">
                <div class="card-body border-bottom bg-light py-3">
                    <form method="POST" action="{{ route('finance.treasury.accounts.store') }}" class="row g-2">
                        @csrf
                        <div class="col-md-5"><input type="text" name="name" class="form-control form-control-sm" placeholder="Hesap adı" required></div>
                        <div class="col-md-3">
                            <select name="type" class="form-select form-select-sm">
                                <option value="cash">Nakit Kasa</option>
                                <option value="bank">Banka</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="currency" class="form-select form-select-sm">
                                @foreach(registry()->currencyCodes() as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><button type="submit" class="btn btn-sm btn-primary w-100">{{ __('app.save') }}</button></div>
                    </form>
                </div>
            </div>
            @endif
            <div class="list-group list-group-flush">
                @forelse($treasuryAccounts as $ta)
                <a href="{{ route('finance.accounts.show', $ta) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $ta->name }}</strong>
                        <div class="text-muted small">{{ $ta->typeLabel() }}</div>
                    </div>
                    <span class="fw-bold {{ $ta->balance >= 0 ? 'text-green' : 'text-red' }}">{{ number_format($ta->balance, 2, ',', '.') }} {{ $ta->currency }}</span>
                </a>
                @empty
                <div class="list-group-item text-muted">{{ __('app.no_records') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('finance.monthly_summary') }} — {{ $year }}</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm card-table mb-0">
                    <thead><tr><th>Ay</th><th class="text-end">Gelir</th><th class="text-end">Gider</th><th class="text-end">Net</th></tr></thead>
                    <tbody>
                        @foreach($months as $m)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::create(null, $m['month'])->translatedFormat('M') }}</td>
                            <td class="text-green text-end">{{ number_format($m['income'], 0, ',', '.') }}</td>
                            <td class="text-red text-end">{{ number_format($m['expense'], 0, ',', '.') }}</td>
                            <td class="text-end {{ $m['net'] >= 0 ? 'text-green' : 'text-red' }}"><strong>{{ number_format($m['net'], 0, ',', '.') }}</strong></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('finance.recent_entries') }}</h3>
                <div class="d-flex gap-1">
                    <a href="{{ route('finance.income-expenses', ['year' => $year]) }}" class="btn btn-sm btn-ghost-secondary">{{ __('finance.view_all') }}</a>
                    <a href="{{ route('finance.profit-loss', ['period' => 'year', 'date' => $year . '-01-01']) }}" class="btn btn-sm btn-outline-primary">{{ __('finance.profit_loss') }}</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm card-table mb-0">
                    <thead><tr><th>Tarih</th><th>Tip</th><th>Açıklama</th><th class="text-end">Tutar</th><th></th></tr></thead>
                    <tbody>
                        @forelse($recentEntries as $item)
                        <tr>
                            <td>{{ $item->transaction_date->format('d.m') }}</td>
                            <td><span class="badge bg-{{ $item->type==='income'?'success':'danger' }}-lt">{{ $item->type === 'income' ? __('finance.type_income') : __('finance.type_expense') }}</span></td>
                            <td>{{ $item->displayTitle() }}</td>
                            <td class="text-end {{ $item->type==='income'?'text-green':'text-red' }}">{{ number_format($item->amount_base ?? $item->amount, 2, ',', '.') }} ₺</td>
                            <td>@include('partials.income-expense-actions', ['item' => $item])</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
