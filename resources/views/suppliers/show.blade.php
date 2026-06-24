@extends('layouts.app')
@section('title', $supplier->company_name)
@section('content')
@include('partials.page-header', ['title' => $supplier->company_name])
<div class="card"><div class="card-body"><h3>{{ $supplier->company_name }}</h3><p>{{ $supplier->email }} | {{ $supplier->phone }}</p><a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary">{{ __('app.edit') }}</a></div></div>
@endsection
