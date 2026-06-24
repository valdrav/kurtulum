@extends('layouts.app')
@section('title', 'E-posta Yaz')
@section('content')
@include('partials.page-header', ['title' => 'E-posta Yaz'])
<div class="card"><div class="card-body"><form method="POST" action="{{ route('emails.send') }}">@csrf
<div class="mb-3"><select name="email_account_id" class="form-select" required>@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }} ({{ $a->email }})</option>@endforeach</select></div>
<div class="mb-3"><input type="text" name="to_emails" class="form-control" placeholder="Alıcılar (virgülle ayırın)" required></div>
<div class="mb-3"><input type="text" name="subject" class="form-control" placeholder="Konu" required></div>
<div class="mb-3"><textarea name="body" class="form-control" rows="10" required></textarea></div>
<button type="submit" class="btn btn-primary">Gönder</button></form></div></div>
@endsection
