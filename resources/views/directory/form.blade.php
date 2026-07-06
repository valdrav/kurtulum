@extends('layouts.app')
@section('title', $contact->exists ? $contact->fullName() : __('directory.new_contact'))

@section('content')
@include('partials.page-header', ['title' => $contact->exists ? $contact->fullName() : __('directory.new_contact'), 'backRoute' => route('directory.index')])

<form method="POST" action="{{ $contact->exists ? route('directory.update', $contact) : route('directory.store') }}">
    @csrf
    @if($contact->exists) @method('PUT') @endif
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('directory.first_name') }} *</label><input type="text" name="first_name" class="form-control" value="{{ old('first_name', $contact->first_name) }}" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('directory.last_name') }} *</label><input type="text" name="last_name" class="form-control" value="{{ old('last_name', $contact->last_name) }}" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('directory.phone') }} *</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $contact->phone) }}" required></div>
                <div class="col-12 mb-3"><label class="form-label">{{ __('directory.description') }}</label><textarea name="description" class="form-control" rows="3">{{ old('description', $contact->description) }}</textarea></div>
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
            <a href="{{ route('directory.index') }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a>
        </div>
    </div>
</form>
@endsection
