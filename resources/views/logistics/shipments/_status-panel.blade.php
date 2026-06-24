        @if(!in_array($shipment->status, ['completed', 'cancelled']) && can_access('shipments.edit'))
        <div class="card mb-3 border-primary">
            <div class="card-header bg-primary-lt">
                <h3 class="card-title mb-0"><i class="ti ti-route"></i> {{ __('logistics.operational_status') }}</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-light py-2 mb-3">
                    <strong>{{ __('app.status') }}:</strong>
                    <span class="badge bg-primary-lt fs-6">{{ $shipment->statusDisplay() }}</span>
                    @if($shipment->status_updated_at)
                    <span class="text-muted small ms-2">{{ __('logistics.status_updated') }}: {{ $shipment->status_updated_at->timezone(app_timezone())->format('d.m.Y H:i') }}</span>
                    @endif
                </div>

                @if($shipment->transport_mode === 'sea')
                <p class="text-muted small mb-2">{{ __('logistics.status_presets_hint') }}</p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach(config('ticari.shipment_status_presets', []) as $preset)
                    <form method="POST" action="{{ route('shipments.status', $shipment) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="status" value="{{ $preset['status'] }}">
                        <input type="hidden" name="status_location" value="{{ $preset['location'] }}">
                        <input type="hidden" name="manual" value="1">
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            {{ shipment_status_display($preset['status'], $preset['location']) }}
                        </button>
                    </form>
                    @endforeach
                </div>
                @endif

                @if(count($nextStatuses))
                <p class="text-muted small mb-2">{{ __('logistics.next_step') }}</p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach($nextStatuses as $next)
                    <form method="POST" action="{{ route('shipments.status', $shipment) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="status" value="{{ $next }}">
                        <input type="hidden" name="status_location" value="{{ $shipment->status_location }}">
                        <button type="submit" class="btn btn-primary btn-sm">{{ status_label($next, 'shipment') }}</button>
                    </form>
                    @endforeach
                </div>
                @endif

                <form method="POST" action="{{ route('shipments.status', $shipment) }}" class="row g-2">
                    @csrf
                    <input type="hidden" name="manual" value="1">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">{{ __('logistics.status_select') }}</label>
                        <select name="status" class="form-select form-select-sm" required>
                            @foreach(config('ticari.shipment_statuses') as $s)
                            <option value="{{ $s }}" @selected($shipment->status===$s)>{{ status_label($s, 'shipment') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">{{ __('logistics.status_location') }}</label>
                        <input type="text" name="status_location" class="form-control form-control-sm" list="status-locations" value="{{ $shipment->status_location }}" placeholder="{{ __('logistics.status_location_hint') }}">
                        <datalist id="status-locations">
                            <option value="Cidde">
                            <option value="Misurata">
                            <option value="Limra">
                            <option value="Mersin">
                            <option value="İskenderun">
                            <option value="Suez">
                        </datalist>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">{{ __('logistics.status_note') }}</label>
                        <input type="text" name="note" class="form-control form-control-sm" placeholder="{{ __('logistics.status_note_hint') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">{{ __('logistics.status_update') }}</button>
                    </div>
                </form>
                <p class="text-muted small mt-2 mb-0">{{ __('logistics.status_manager_hint') }}</p>
            </div>
        </div>
        @endif
