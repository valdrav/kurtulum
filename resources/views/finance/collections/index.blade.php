@extends('layouts.app')
@section('title', __('app.collections'))
@section('content')
@include('partials.page-header', ['title' => __('app.collections')])
@include('partials.finance-nav')

<div class="row g-3">
    @if(can_access('finance.create'))
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">{{ __('extensions.new_collection') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.collections.store') }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Cari Hesap *</label>
                        <select name="account_id" class="form-select" required>
                            <option value="">{{ __('extensions.select_account') }}</option>
                            @foreach($accounts as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
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
                    @include('partials.payment-method-form', ['paymentMethods' => $paymentMethods, 'dateField' => 'collection_date'])
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
                    <thead><tr><th>No</th><th>{{ __('app.date') }}</th><th>Hesap</th><th>Yöntem</th><th>{{ __('app.amount') }}</th><th></th></tr></thead>
                    <tbody>
                        @forelse($collections as $c)
                        <tr>
                            <td><a href="{{ route('finance.collections.show', $c) }}">{{ $c->collection_number }}</a></td>
                            <td>{{ $c->collection_date->format('d.m.Y') }}</td>
                            <td>{{ $c->account?->name }}</td>
                            <td>{{ $c->paymentMethod?->name ?? $c->collection_method }}</td>
                            <td>{{ number_format($c->amount, 2, ',', '.') }} {{ $c->currency }}</td>
                            <td>@if(can_access('finance.edit'))<a href="{{ route('finance.collections.edit', $c) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a>@endif</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-muted">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($collections->hasPages())<div class="card-footer">{{ $collections->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
