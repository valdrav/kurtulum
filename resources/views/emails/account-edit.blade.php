@extends('layouts.app')
@section('title', __('emails.edit_account'))
@section('content')
@include('partials.page-header', ['title' => __('emails.edit_account')])

@if(!$imapAvailable)
<div class="alert alert-warning">{{ __('emails.imap_missing') }}</div>
@endif

@if($errors->has('imap'))
<div class="alert alert-danger">{{ $errors->first('imap') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">{{ $account->email }}</h3>
    </div>
    <div class="card-body">
        @include('emails.partials.account-form', [
            'formAction' => route('emails.accounts.update', $account),
            'formMethod' => 'PUT',
            'account' => $account,
            'submitLabel' => __('app.save'),
        ])
    </div>
</div>
@endsection
