@extends('layouts.app')
@section('title', __('finance.edit_collection'))
@section('content')
@include('partials.page-header', ['title' => __('finance.edit_collection')])
@include('partials.finance-nav')

<div class="card" style="max-width:560px">
    <div class="card-body">
        <form method="POST" action="{{ route('finance.collections.update', $collection) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label">{{ __('app.date') }}</label>
                <input type="date" name="collection_date" class="form-control" value="{{ old('collection_date', $collection->collection_date->format('Y-m-d')) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Referans</label>
                <input type="text" name="reference" class="form-control" value="{{ old('reference', $collection->reference) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('app.description') }}</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $collection->notes) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
        </form>
    </div>
</div>
@endsection
