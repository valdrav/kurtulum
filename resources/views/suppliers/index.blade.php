@extends('layouts.app')
@section('title', __('app.suppliers'))
@section('content')
@include('partials.page-header', ['title' => __('app.suppliers'), 'createRoute' => route('suppliers.create'), 'createPermission' => 'suppliers.create'])

<div class="d-md-none ef-mobile-list mb-3">
    @forelse($suppliers as $s)
    @include('partials.mobile-record-card', [
        'url' => route('suppliers.show', $s),
        'title' => $s->company_name,
        'subtitle' => type_label($s->type, 'suppliers'),
        'meta' => $s->country,
        'badge' => type_label($s->status, 'suppliers'),
        'editUrl' => route('suppliers.edit', $s),
        'editPermission' => 'suppliers.edit',
    ])
    @empty
    <div class="card"><div class="card-body text-muted">{{ __('app.no_records') }}</div></div>
    @endforelse
</div>

<div class="card hide-mobile">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern">
            <thead><tr><th>Firma</th><th>Tip</th><th>Ülke</th><th>{{ __('app.status') }}</th><th></th></tr></thead>
            <tbody>
                @forelse($suppliers as $s)
                <tr>
                    <td><a href="{{ route('suppliers.show', $s) }}">{{ $s->company_name }}</a></td>
                    <td>{{ type_label($s->type, 'suppliers') }}</td>
                    <td>{{ $s->country }}</td>
                    <td>{{ type_label($s->status, 'suppliers') }}</td>
                    <td>
                        @if(can_access('suppliers.edit'))
                        <a href="{{ route('suppliers.edit', $s) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($suppliers->hasPages())<div class="card-footer">{{ $suppliers->links() }}</div>@endif
</div>
@if($suppliers->hasPages())<div class="d-md-none mt-2">{{ $suppliers->links() }}</div>@endif
@endsection
