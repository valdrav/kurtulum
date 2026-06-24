@extends('layouts.app')
@section('title', __('finance.movements'))
@section('content')
@include('partials.page-header', ['title' => __('finance.movements'), 'subtitle' => __('finance.movements_subtitle')])
@include('partials.finance-nav')

<div class="row row-cards mb-3">
    <div class="col-6 col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="subheader small">{{ __('finance.income_period') }}</div>
            <div class="h3 text-green mb-0">{{ number_format($summary['income'], 2, ',', '.') }} ₺</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="subheader small">{{ __('finance.expense_period') }}</div>
            <div class="h3 text-red mb-0">{{ number_format($summary['expense'], 2, ',', '.') }} ₺</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="subheader small">{{ __('finance.net_period') }}</div>
            <div class="h3 mb-0 {{ $summary['net'] >= 0 ? 'text-green' : 'text-red' }}">{{ number_format($summary['net'], 2, ',', '.') }} ₺</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card"><div class="card-body py-3 d-flex align-items-center">
            <a href="{{ route('finance.profit-loss', request()->only(['period', 'date', 'type', 'search'])) }}" class="btn btn-outline-primary w-100 btn-sm">
                <i class="ti ti-chart-bar me-1"></i>{{ __('finance.detailed_report') }}
            </a>
        </div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        @include('partials.finance-period-filter', ['periodMeta' => $periodMeta, 'summary' => $summary, 'uid' => 'list'])
    </div>
</div>

<div class="row g-3">
    @if(can_access('finance.create'))
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('finance.quick_entry') }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.income-expenses.store') }}">
                    @csrf
                    @include('partials.income-expense-form', [
                        'treasuryAccounts' => $treasuryAccounts,
                        'paymentMethods' => $paymentMethods,
                        'defaultTreasuryId' => $defaultTreasury->id,
                        'compact' => true,
                    ])
                    <button type="submit" class="btn btn-primary w-100 mt-2">{{ __('app.save') }}</button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="col-lg-{{ can_access('finance.create') ? '8' : '12' }}">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('app.date') }}</th>
                            <th>{{ __('finance.entry_type') }}</th>
                            <th>{{ __('finance.entry_title') }}</th>
                            <th class="text-end">{{ __('app.amount') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td>{{ $item->transaction_date->format('d.m.Y') }}</td>
                            <td><span class="badge bg-{{ $item->type==='income'?'success':'danger' }}-lt">{{ $item->type === 'income' ? __('finance.type_income') : __('finance.type_expense') }}</span></td>
                            <td>
                                <strong>{{ $item->displayTitle() }}</strong>
                                @if($item->account)<div class="text-muted small">{{ $item->account->name }}</div>@endif
                            </td>
                            <td class="text-end {{ $item->type==='income'?'text-green':'text-red' }}">{{ number_format($item->amount_base ?? $item->amount, 2, ',', '.') }} ₺</td>
                            <td class="text-nowrap">
                                @if(can_access('finance.edit'))<a href="{{ route('finance.income-expenses.edit', $item) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a>@endif
                                @if(can_access('finance.delete'))
                                <form method="POST" action="{{ route('finance.income-expenses.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())<div class="card-footer">{{ $items->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
