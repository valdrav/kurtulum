@extends('layouts.app')
@section('title', __('directory.title'))

@section('content')
@include('partials.page-header', [
    'title' => __('directory.title'),
    'subtitle' => __('directory.subtitle'),
    'createRoute' => can_access('directory.create') ? route('directory.create') : null,
])

<div class="d-flex flex-wrap gap-2 mb-3">
    @if(can_access('directory.import'))
    <a href="{{ route('directory.import') }}" class="btn btn-outline-primary btn-sm"><i class="ti ti-upload me-1"></i>{{ __('directory.import') }}</a>
    @endif
    @if(can_access('directory.export'))
    <a href="{{ route('directory.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-download me-1"></i>{{ __('app.export') }}</a>
    @endif
</div>

@if(session('import_errors'))
<div class="alert alert-warning small">
    <ul class="mb-0">@foreach(session('import_errors') as $err)<li>{{ $err }}</li>@endforeach</ul>
</div>
@endif

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2">
            <div class="col-md-10"><input type="text" name="search" class="form-control" placeholder="{{ __('app.search') }}..." value="{{ request('search') }}"></div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100">{{ __('app.filter') }}</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern">
            <thead>
                <tr>
                    <th>Ad Soyad</th>
                    <th>Telefon</th>
                    <th>{{ __('directory.description') }}</th>
                    <th class="ef-table-actions"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                <tr>
                    <td class="fw-semibold">{{ $contact->fullName() }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span>{{ $contact->phone }}</span>
                            @include('partials.whatsapp-button', ['phone' => $contact->phone, 'iconOnly' => true])
                        </div>
                    </td>
                    <td class="text-muted">{{ $contact->description ?? '—' }}</td>
                    <td class="ef-table-actions text-nowrap text-end">
                        @if(can_access('directory.edit'))
                        <a href="{{ route('directory.edit', $contact) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i></a>
                        @endif
                        @if(can_access('directory.delete'))
                        <form method="POST" action="{{ route('directory.destroy', $contact) }}" class="d-inline" data-confirm="{{ __('app.confirm_delete') }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button></form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($contacts->hasPages())<div class="card-footer d-flex justify-content-center">{{ $contacts->links() }}</div>@endif
</div>
@endsection
