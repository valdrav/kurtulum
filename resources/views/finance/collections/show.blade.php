@extends('layouts.app')
@section('title', $collection->collection_number)
@section('content')
@include('partials.page-header', ['title' => __('finance.collection_detail')])
@include('partials.finance-nav')

<div class="card mb-3" style="max-width:720px">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Tahsilat No</dt><dd class="col-sm-8">{{ $collection->collection_number }}</dd>
            <dt class="col-sm-4">{{ __('app.date') }}</dt><dd class="col-sm-8">{{ $collection->collection_date->format('d.m.Y') }}</dd>
            <dt class="col-sm-4">Hesap</dt><dd class="col-sm-8"><a href="{{ route('finance.accounts.show', $collection->account) }}">{{ $collection->account?->name }}</a></dd>
            <dt class="col-sm-4">{{ __('extensions.payment_method') }}</dt><dd class="col-sm-8">{{ $collection->paymentMethod?->name ?? $collection->collection_method }}</dd>
            <dt class="col-sm-4">{{ __('app.amount') }}</dt><dd class="col-sm-8 h3 text-green mb-0">{{ number_format($collection->amount, 2, ',', '.') }} {{ $collection->currency }}</dd>
            @if($collection->reference)<dt class="col-sm-4">Referans</dt><dd class="col-sm-8">{{ $collection->reference }}</dd>@endif
            @if($collection->notes)<dt class="col-sm-4">{{ __('app.description') }}</dt><dd class="col-sm-8">{{ $collection->notes }}</dd>@endif
        </dl>
        <div class="mt-3">
            @if(can_access('finance.edit'))<a href="{{ route('finance.collections.edit', $collection) }}" class="btn btn-primary btn-sm"><i class="ti ti-edit"></i> Düzenle</a>@endif
            <a href="{{ route('finance.collections') }}" class="btn btn-ghost-secondary btn-sm">Listeye dön</a>
        </div>
    </div>
</div>
@endsection
