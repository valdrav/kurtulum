@extends('layouts.app')
@section('title', __('app.emails'))
@section('content')
@include('partials.page-header', ['title' => 'Gelen Kutum'])

<div class="d-flex flex-wrap gap-2 mb-3">
    @if(can_access('emails.create'))
    <a href="{{ route('emails.compose') }}" class="btn btn-primary btn-sm"><i class="ti ti-pencil me-1"></i> Yaz</a>
    <a href="{{ route('emails.accounts') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-settings me-1"></i> Hesaplarım</a>
    @if($imapAvailable)
    <form method="POST" action="{{ route('emails.sync') }}" class="d-inline">@csrf
        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="ti ti-refresh me-1"></i> Senkronize</button>
    </form>
    @else
    <span class="badge bg-yellow-lt align-self-center">IMAP eklentisi sunucuda kapalı</span>
    @endif
    @endif
</div>

@if($accounts->isEmpty())
<div class="alert alert-info">
    Henüz e-posta hesabınız yok. <a href="{{ route('emails.accounts') }}">IMAP/SMTP hesabı ekleyin</a> — maillerinizi uygulama içinden okuyabilirsiniz.
</div>
@else
<div class="btn-group btn-group-sm mb-3 flex-wrap" role="group">
    <a href="{{ route('emails.index') }}" class="btn @if(!request('folder')) btn-primary @else btn-outline-secondary @endif">Tümü</a>
    <a href="{{ route('emails.index', ['folder' => 'unread']) }}" class="btn @if(request('folder')==='unread') btn-primary @else btn-outline-secondary @endif">Okunmamış</a>
    @foreach($accounts as $acc)
    <a href="{{ route('emails.index', ['account' => $acc->id]) }}" class="btn @if(request('account')==$acc->id) btn-primary @else btn-outline-secondary @endif">{{ $acc->email }}</a>
    @endforeach
</div>
@endif

<div class="card">
    <div class="list-group list-group-flush">
        @forelse($emails as $email)
        <a href="{{ route('emails.show', $email) }}" class="email-list-item @if(!$email->is_read) unread @endif">
            <div class="d-flex justify-content-between gap-2">
                <div class="text-truncate">
                    <div class="small text-muted">{{ $email->from_name ?? $email->from_email }}</div>
                    <div>{{ $email->subject }}</div>
                </div>
                <div class="text-muted small text-nowrap">
                    {{ ($email->received_at ?? $email->sent_at)?->format('d.m H:i') }}
                </div>
            </div>
        </a>
        @empty
        <div class="list-group-item text-muted py-4 text-center">{{ __('app.no_records') }}</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $emails->withQueryString()->links() }}</div>
@endsection
