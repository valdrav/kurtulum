@extends('layouts.app')
@section('title', $payment->payment_number)
@section('content')
@include('partials.page-header', ['title' => __('finance.payment_detail')])
@include('partials.finance-nav')

<div class="card mb-3" style="max-width:720px">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Ödeme No</dt><dd class="col-sm-8">{{ $payment->payment_number }}</dd>
            <dt class="col-sm-4">{{ __('app.date') }}</dt><dd class="col-sm-8">{{ $payment->payment_date->format('d.m.Y') }}</dd>
            <dt class="col-sm-4">Hesap</dt><dd class="col-sm-8"><a href="{{ route('finance.accounts.show', $payment->account) }}">{{ $payment->account?->name }}</a></dd>
            <dt class="col-sm-4">{{ __('extensions.payment_method') }}</dt><dd class="col-sm-8">{{ $payment->paymentMethod?->name ?? $payment->payment_method }}</dd>
            <dt class="col-sm-4">{{ __('app.amount') }}</dt><dd class="col-sm-8 h3 text-red mb-0">{{ number_format($payment->amount, 2, ',', '.') }} {{ $payment->currency }}</dd>
            <dt class="col-sm-4">Kur</dt><dd class="col-sm-8">{{ number_format($payment->exchange_rate, 4, ',', '.') }}</dd>
            @if($payment->fee_amount > 0)<dt class="col-sm-4">Komisyon</dt><dd class="col-sm-8">{{ number_format($payment->fee_amount, 2, ',', '.') }}</dd>@endif
            @if($payment->reference)<dt class="col-sm-4">Referans</dt><dd class="col-sm-8">{{ $payment->reference }}</dd>@endif
            @if($payment->notes)<dt class="col-sm-4">{{ __('app.description') }}</dt><dd class="col-sm-8">{{ $payment->notes }}</dd>@endif
        </dl>
        <div class="mt-3 d-flex flex-wrap gap-2">
            @if(can_access('finance.edit'))<a href="{{ route('finance.payments.edit', $payment) }}" class="btn btn-primary btn-sm"><i class="ti ti-edit"></i> Düzenle</a>@endif
            @if(can_access('finance.delete'))
            @include('partials.delete-form', ['action' => route('finance.payments.destroy', $payment), 'confirm' => __('finance.delete_payment_confirm')])
            @endif
            <a href="{{ route('finance.payments') }}" class="btn btn-ghost-secondary btn-sm">Listeye dön</a>
        </div>
    </div>
</div>
@endsection
