@extends('layouts.portal')
@section('title', __('portal.my_shipments'))

@section('content')
<div class="card mb-3"><div class="card-body py-3">
    <form method="GET" class="row g-2"><div class="col-md-10"><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('app.search') }}"></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">{{ __('app.filter') }}</button></div></form>
</div></div>
<div class="card"><div class="table-responsive"><table class="table table-vcenter card-table table-modern mb-0">
    <thead><tr><th>No</th><th>Mod</th><th>{{ __('logistics.origin') }}</th><th>{{ __('logistics.destination') }}</th><th>{{ __('app.status') }}</th></tr></thead>
    <tbody>
        @forelse($shipments as $shipment)
        <tr>
            <td><a href="{{ route('portal.shipments.show', $shipment) }}" class="fw-semibold">{{ $shipment->shipment_number }}</a></td>
            <td>{{ __('logistics.'.$shipment->transport_mode) }}</td>
            <td>{{ $shipment->origin ?? '—' }}</td>
            <td>{{ $shipment->destination ?? '—' }}</td>
            <td>{{ $shipment->statusDisplay() }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('app.no_records') }}</td></tr>
        @endforelse
    </tbody>
</table></div>
@if($shipments->hasPages())<div class="card-footer">{{ $shipments->links() }}</div>@endif
</div>
@endsection
