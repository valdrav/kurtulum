@extends('layouts.app')
@section('title', $shipment->exists ? $shipment->shipment_number : __('app.create'))
@section('content')
@include('partials.page-header', ['title' => __('app.shipments')])
@php
    $mode = old('transport_mode', $shipment->transport_mode ?? 'road');
    $modeTypes = [
        'road' => port_types_for_mode('road'),
        'sea' => port_types_for_mode('sea'),
        'air' => port_types_for_mode('air'),
        'rail' => port_types_for_mode('rail'),
        'multimodal' => port_types_for_mode('multimodal'),
    ];
    $defaultPortTypes = [
        'road' => default_port_type_for_mode('road'),
        'sea' => default_port_type_for_mode('sea'),
        'air' => default_port_type_for_mode('air'),
        'rail' => default_port_type_for_mode('rail'),
        'multimodal' => default_port_type_for_mode('multimodal'),
    ];
    $countryOptions = collect(['TR', 'DE', 'NL', 'BG', 'GR', 'RO', 'IQ', 'SY', 'RU', 'UA', 'CN', 'AE', 'SA', 'LY', 'EG', 'IT', 'FR', 'ES', 'GB', 'US'])
        ->mapWithKeys(fn ($c) => [$c => country_label($c)])
        ->all();
@endphp
<div class="card"><div class="card-body">
<form method="POST" action="{{ $shipment->exists ? route('shipments.update', $shipment) : route('shipments.store') }}">@csrf @if($shipment->exists)@method('PUT')@endif
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ __('logistics.shipment_number') }} *</label>
        <input type="text" name="shipment_number" class="form-control" maxlength="50"
               value="{{ old('shipment_number', $shipment->shipment_number) }}"
               placeholder="{{ $shipment->exists ? '' : 'SHP-2026-00001' }}"
               @if($shipment->exists) required @endif>
        @unless($shipment->exists)<div class="form-hint">{{ __('logistics.shipment_number_hint') }}</div>@endunless
    </div>
    <div class="col-md-4 mb-3"><label class="form-label">{{ __('logistics.transport_mode') }} *</label>
        <select name="transport_mode" class="form-select" required id="transport_mode">
            @foreach(['road','sea','air','rail','multimodal'] as $m)<option value="{{ $m }}" @selected($mode===$m)>{{ __('logistics.'.$m) }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3"><label class="form-label">{{ __('app.status') }}</label>@include('partials.status-select', ['group' => 'shipment', 'selected' => old('status', $shipment->status ?? 'draft')])</div>
    <div class="col-md-4 mb-3">@include('partials.incoterm-field', ['selected' => old('incoterm', $shipment->incoterm ?? '')])</div>
    <div class="col-md-6 mb-3"><label class="form-label">{{ __('app.customers') }}</label><select name="customer_id" class="form-select"><option value="">-</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id',$shipment->customer_id)==$c->id)>{{ $c->company_name }}</option>@endforeach</select></div>
    <div class="col-md-6 mb-3"><label class="form-label">{{ __('app.orders') }}</label><select name="order_id" class="form-select"><option value="">-</option>@foreach($orders as $o)<option value="{{ $o->id }}" @selected(old('order_id',$shipment->order_id)==$o->id)>{{ $o->order_number }}</option>@endforeach</select></div>
    <div class="col-md-6 mb-3">
        <label class="form-label" id="origin-label">{{ shipment_location_label($mode, 'origin') }}</label>
        <div class="input-group">
            <select name="origin_port_id" class="form-select" id="origin_port_id"><option value="">-</option></select>
            <button type="button" class="btn btn-outline-secondary" onclick="openPortModal('origin_port_id')" title="{{ __('logistics.add_port') }}">
                <i class="ti ti-plus"></i>
            </button>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" id="destination-label">{{ shipment_location_label($mode, 'destination') }}</label>
        <div class="input-group">
            <select name="destination_port_id" class="form-select" id="destination_port_id"><option value="">-</option></select>
            <button type="button" class="btn btn-outline-secondary" onclick="openPortModal('destination_port_id')" title="{{ __('logistics.add_port') }}">
                <i class="ti ti-plus"></i>
            </button>
        </div>
    </div>
    <div class="col-md-3 mb-3"><label class="form-label">{{ __('logistics.etd') }}</label><input type="datetime-local" name="etd" class="form-control" value="{{ old('etd', $shipment->etd?->format('Y-m-d\TH:i')) }}"></div>
    <div class="col-md-3 mb-3"><label class="form-label">{{ __('logistics.eta') }}</label><input type="datetime-local" name="eta" class="form-control" value="{{ old('eta', $shipment->eta?->format('Y-m-d\TH:i')) }}"></div>
    <div class="col-md-3 mb-3"><label class="form-label">{{ __('logistics.atd') }}</label><input type="datetime-local" name="atd" class="form-control" value="{{ old('atd', $shipment->atd?->format('Y-m-d\TH:i')) }}"></div>
    <div class="col-md-3 mb-3"><label class="form-label">{{ __('logistics.ata') }}</label><input type="datetime-local" name="ata" class="form-control" value="{{ old('ata', $shipment->ata?->format('Y-m-d\TH:i')) }}"></div>
    <div class="col-md-4 mb-3 sea-field"><label class="form-label">{{ __('logistics.bl_number') }}</label><input type="text" name="bl_number" class="form-control" value="{{ old('bl_number', $shipment->bl_number) }}"></div>
    <div class="col-md-4 mb-3 air-field"><label class="form-label">{{ __('logistics.awb_number') }}</label><input type="text" name="awb_number" class="form-control" value="{{ old('awb_number', $shipment->awb_number) }}"></div>
    <div class="col-md-4 mb-3 road-field"><label class="form-label">{{ __('logistics.cmr_number') }}</label><input type="text" name="cmr_number" class="form-control" value="{{ old('cmr_number', $shipment->cmr_number) }}"></div>
    <div class="col-md-4 mb-3 sea-field"><label class="form-label">{{ __('logistics.vessel') }}</label><select name="vessel_id" class="form-select"><option value="">-</option>@foreach($vessels as $v)<option value="{{ $v->id }}" @selected(old('vessel_id',$shipment->vessel_id)==$v->id)>{{ $v->name }}</option>@endforeach</select></div>
    <div class="col-md-4 mb-3 air-field"><label class="form-label">{{ __('logistics.flight') }}</label><input type="text" name="flight_number" class="form-control" value="{{ old('flight_number', $shipment->flight_number) }}" placeholder="TK1234"></div>
    <div class="col-md-4 mb-3 air-field"><label class="form-label">{{ __('logistics.airline') }}</label><input type="text" name="airline" class="form-control" value="{{ old('airline', $shipment->airline) }}"></div>
    <div class="col-md-4 mb-3 road-field"><label class="form-label">{{ __('logistics.vehicle') }}</label><select name="vehicle_id" class="form-select"><option value="">-</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected(old('vehicle_id',$shipment->vehicle_id)==$v->id)>{{ $v->plate_number }}</option>@endforeach</select></div>
    <div class="col-md-4 mb-3 road-field"><label class="form-label">{{ __('logistics.driver') }}</label><select name="driver_id" class="form-select"><option value="">-</option>@foreach($drivers as $d)<option value="{{ $d->id }}" @selected(old('driver_id',$shipment->driver_id)==$d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-md-4 mb-3"><label class="form-label">{{ __('logistics.carrier') }}</label><input type="text" name="carrier" class="form-control" value="{{ old('carrier', $shipment->carrier) }}"></div>
    <div class="col-md-4 mb-3"><label class="form-label">{{ __('logistics.forwarder') }}</label><input type="text" name="forwarder" class="form-control" value="{{ old('forwarder', $shipment->forwarder) }}"></div>
    <div class="col-md-4 mb-3"><label class="form-label">{{ __('app.currency') }}</label><select name="currency" class="form-select">@foreach(config('ticari.currencies') as $cur)<option value="{{ $cur }}" @selected(old('currency',$shipment->currency??'USD')===$cur)>{{ currency_name($cur) }} ({{ $cur }})</option>@endforeach</select></div>
    <div class="col-12 mb-3"><label class="form-label">{{ __('logistics.cargo_description') }}</label><textarea name="cargo_description" class="form-control" rows="2">{{ old('cargo_description', $shipment->cargo_description) }}</textarea></div>
    <div class="col-12 mb-3"><label class="form-label">{{ __('app.notes') }}</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $shipment->notes) }}</textarea></div>
</div>
<button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
<a href="{{ route('shipments.index') }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a>
</form></div></div>

<div class="modal modal-blur fade" id="portQuickAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('logistics.add_port_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('app.cancel') }}"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="port_quick_name">{{ __('logistics.port_name') }} *</label>
                    <input type="text" class="form-control" id="port_quick_name" maxlength="255" placeholder="Kapıkule, Mersin Limanı…">
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label" for="port_quick_country">{{ __('customers.country') }} *</label>
                        <select class="form-select" id="port_quick_country">
                            @foreach($countryOptions as $code => $label)
                            <option value="{{ $code }}" @selected($code === 'TR')>{{ $label }} ({{ $code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="port_quick_city">{{ __('logistics.port_city') }}</label>
                        <input type="text" class="form-control" id="port_quick_city" maxlength="100">
                    </div>
                </div>
                <div class="row g-2 mt-0">
                    <div class="col-md-6">
                        <label class="form-label" for="port_quick_code">{{ __('logistics.port_code') }}</label>
                        <input type="text" class="form-control text-uppercase" id="port_quick_code" maxlength="20" placeholder="TRKAP">
                        <div class="form-hint">{{ __('logistics.port_code_hint') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="port_quick_type">{{ __('logistics.port_type') }} *</label>
                        <select class="form-select" id="port_quick_type">
                            @foreach(__('logistics.port_types') as $type => $typeLabel)
                            <option value="{{ $type }}">{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="text-danger small mt-2" id="port_quick_add_error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('app.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="port_quick_save_btn" onclick="saveQuickPort()">
                    <i class="ti ti-device-floppy"></i> {{ __('app.save') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
const ALL_PORTS = @json($portsJson ?? []);
const ORIGIN_LABELS = {
    road: @json(__('logistics.origin_border')),
    sea: @json(__('logistics.origin_port')),
    air: @json(__('logistics.origin_airport')),
    rail: @json(__('logistics.origin_station')),
    multimodal: @json(__('logistics.origin_port')),
};
const DEST_LABELS = {
    road: @json(__('logistics.destination_border')),
    sea: @json(__('logistics.destination_port')),
    air: @json(__('logistics.destination_airport')),
    rail: @json(__('logistics.destination_station')),
    multimodal: @json(__('logistics.destination_port')),
};
const MODE_TYPES = @json($modeTypes);
const DEFAULT_PORT_TYPES = @json($defaultPortTypes);
const selectedOrigin = @json(old('origin_port_id', $shipment->origin_port_id));
const selectedDest = @json(old('destination_port_id', $shipment->destination_port_id));
let portAddTarget = null;
let originSelected = selectedOrigin;
let destSelected = selectedDest;

function portOptionLabel(p) {
    return p.label || ((p.name || '') + (p.code ? ' (' + p.code + ')' : ''));
}

function fillPortSelect(el, mode, selected) {
    const types = MODE_TYPES[mode] || [];
    const filtered = ALL_PORTS.filter(p => types.includes(p.type));
    el.innerHTML = '<option value="">-</option>';
    filtered.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = portOptionLabel(p);
        if (String(p.id) === String(selected)) opt.selected = true;
        el.appendChild(opt);
    });
}

function openPortModal(targetSelectId) {
    portAddTarget = targetSelectId;
    const mode = document.getElementById('transport_mode').value;
    document.getElementById('port_quick_type').value = DEFAULT_PORT_TYPES[mode] || 'sea';
    document.getElementById('port_quick_add_error').textContent = '';
    document.getElementById('port_quick_name').value = '';
    document.getElementById('port_quick_city').value = '';
    document.getElementById('port_quick_code').value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('portQuickAddModal')).show();
    setTimeout(() => document.getElementById('port_quick_name').focus(), 200);
}

async function saveQuickPort() {
    const btn = document.getElementById('port_quick_save_btn');
    const errEl = document.getElementById('port_quick_add_error');
    errEl.textContent = '';

    const payload = {
        name: document.getElementById('port_quick_name').value.trim(),
        country: document.getElementById('port_quick_country').value,
        city: document.getElementById('port_quick_city').value.trim() || null,
        code: document.getElementById('port_quick_code').value.trim().toUpperCase() || null,
        type: document.getElementById('port_quick_type').value,
    };

    if (!payload.name) {
        errEl.textContent = '{{ __('logistics.port_name') }} gerekli.';
        return;
    }

    btn.disabled = true;
    try {
        const res = await fetch(@json(route('ports.store')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok) {
            const msg = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Kaydedilemedi';
            throw new Error(msg);
        }

        ALL_PORTS.push(data.port);
        const mode = document.getElementById('transport_mode').value;
        if (portAddTarget === 'origin_port_id') {
            originSelected = data.port.id;
        } else {
            destSelected = data.port.id;
        }
        fillPortSelect(document.getElementById('origin_port_id'), mode, originSelected);
        fillPortSelect(document.getElementById('destination_port_id'), mode, destSelected);
        bootstrap.Modal.getInstance(document.getElementById('portQuickAddModal')).hide();
    } catch (e) {
        errEl.textContent = e.message || 'Bağlantı hatası';
    } finally {
        btn.disabled = false;
    }
}

function updateShipmentForm() {
    const mode = document.getElementById('transport_mode').value;
    document.getElementById('origin-label').textContent = ORIGIN_LABELS[mode] || ORIGIN_LABELS.sea;
    document.getElementById('destination-label').textContent = DEST_LABELS[mode] || DEST_LABELS.sea;
    fillPortSelect(document.getElementById('origin_port_id'), mode, originSelected);
    fillPortSelect(document.getElementById('destination_port_id'), mode, destSelected);
    document.querySelectorAll('.sea-field').forEach(el => el.style.display = ['sea','multimodal'].includes(mode) ? '' : 'none');
    document.querySelectorAll('.air-field').forEach(el => el.style.display = ['air','multimodal'].includes(mode) ? '' : 'none');
    document.querySelectorAll('.road-field').forEach(el => el.style.display = ['road','multimodal'].includes(mode) ? '' : 'none');
}

document.getElementById('transport_mode')?.addEventListener('change', updateShipmentForm);
updateShipmentForm();
</script>
@endpush
