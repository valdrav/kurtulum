@extends('layouts.portal')
@section('title', __('portal.directory'))

@section('content')
<div class="card mb-3"><div class="card-body py-3">
    <form method="GET" class="row g-2"><div class="col-md-10"><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('app.search') }}"></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">{{ __('app.filter') }}</button></div></form>
</div></div>
<div class="card"><div class="table-responsive"><table class="table table-vcenter card-table table-modern mb-0">
    <thead><tr><th>{{ __('directory.full_name') }}</th><th>{{ __('directory.phone') }}</th><th>{{ __('directory.description') }}</th></tr></thead>
    <tbody>
        @forelse($contacts as $contact)
        <tr>
            <td>{{ $contact->fullName() }}</td>
            <td><div class="d-flex align-items-center gap-2"><span>{{ $contact->phone }}</span>@include('partials.whatsapp-button', ['phone' => $contact->phone, 'iconOnly' => true])</div></td>
            <td class="text-muted">{{ trans_content($contact->description) ?: '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="3" class="text-center text-muted py-4">{{ __('app.no_records') }}</td></tr>
        @endforelse
    </tbody>
</table></div>
@if($contacts->hasPages())<div class="card-footer">{{ $contacts->links() }}</div>@endif
</div>
@endsection
