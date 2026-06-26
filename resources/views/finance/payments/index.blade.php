@extends('layouts.app')
@section('title', __('app.payments'))
@section('content')
@include('partials.page-header', ['title' => __('app.payments')])
@include('partials.finance-nav')

<div class="row g-3">
    @if(can_access('finance.create'))
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">{{ __('extensions.new_payment') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.payments.store') }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Cari Hesap *</label>
                        <select name="account_id" class="form-select" required>
                            <option value="">{{ __('extensions.select_account') }}</option>
                            @foreach($accounts as $a)
                            <option value="{{ $a->id }}">{{ $a->name }} ({{ $a->currency }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('finance.treasury_account') }}</label>
                        <select name="treasury_account_id" class="form-select" required>
                            @foreach($treasuryAccounts as $ta)
                            <option value="{{ $ta->id }}">{{ $ta->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @include('partials.payment-method-form', ['paymentMethods' => $paymentMethods])
                    <button type="submit" class="btn btn-primary w-100 mt-2">{{ __('app.save') }}</button>
                </form>
            </div>
        </div>
    </div>
    @endif
    <div class="col-lg-{{ can_access('finance.create') ? '8' : '12' }}">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>No</th><th>{{ __('app.date') }}</th><th>Hesap</th><th>Yöntem</th><th>{{ __('app.amount') }}</th><th>{{ __('finance.try_equivalent') }}</th><th></th></tr></thead>
                    <tbody>
                        @forelse($payments as $p)
                        <tr>
                            <td><a href="{{ route('finance.payments.show', $p) }}">{{ $p->payment_number }}</a></td>
                            <td>{{ $p->payment_date->format('d.m.Y') }}</td>
                            <td>{{ $p->account?->name }}</td>
                            <td>{{ $p->paymentMethod?->name ?? $p->payment_method }}</td>
                            <td>{{ number_format($p->amount, 2, ',', '.') }} {{ $p->currency }}</td>
                            <td class="text-muted small">{{ format_try_equivalent((float)$p->amount, $p->currency, (float)$p->exchange_rate) ?: '—' }}</td>
                            <td class="text-end">
                                @if(can_access('finance.edit'))<a href="{{ route('finance.payments.edit', $p) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a>@endif
                                @if(can_access('finance.delete') || can_access('finance.create'))
                                @include('partials.delete-form', [
                                    'action' => route('finance.payments.destroy', $p),
                                    'confirm' => __('finance.delete_payment_confirm'),
                                    'class' => 'btn btn-sm btn-ghost-danger',
                                    'iconOnly' => true,
                                ])
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-muted">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())<div class="card-footer">{{ $payments->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
