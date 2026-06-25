@php
    $showShipmentColumn = $showShipmentColumn ?? true;
    $redirect = $redirect ?? 'index';
@endphp

<div class="table-responsive">
    <table class="table table-vcenter table-sm card-table mb-0 ef-shipment-costs-table">
        <thead>
            <tr>
                @if($showShipmentColumn)<th>{{ __('app.shipments') }}</th>@endif
                <th>{{ __('logistics.cost_invoice') }}</th>
                <th>{{ __('app.date') }}</th>
                <th>{{ __('logistics.cost_item') }}</th>
                <th>{{ __('logistics.cost_payee') }}</th>
                <th>{{ __('logistics.cost_country') }}</th>
                <th class="text-end">{{ __('app.amount') }}</th>
                <th class="text-end">{{ __('finance.try_equivalent') }}</th>
                <th>{{ __('logistics.cost_notes') }}</th>
                <th>{{ __('logistics.cost_status_label') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                @if($showShipmentColumn)
                <td class="small">
                    @if($item->shipment)
                    <a href="{{ route('shipments.show', $item->shipment) }}">{{ $item->shipment->displayLabel() }}</a>
                    @else — @endif
                </td>
                @endif
                <td>{{ $item->invoice_number ?: '—' }}</td>
                <td class="text-nowrap">{{ $item->expense_date?->format('d.m.Y') ?? '—' }}</td>
                <td><strong>{{ $item->displayTitle() }}</strong></td>
                <td class="small">{{ $item->payee ?: '—' }}</td>
                <td>{{ $item->country ?: '—' }}</td>
                <td class="text-end text-nowrap">
                    {{ number_format($item->amount, 2, ',', '.') }} {{ $item->currency }}
                </td>
                <td class="text-end text-nowrap">
                    @if($item->amount_try)
                    <strong>{{ number_format($item->amount_try, 2, ',', '.') }} ₺</strong>
                    @elseif($try = format_try_equivalent((float)$item->amount, $item->currency, (float)$item->exchange_rate))
                    <span class="text-muted">{{ $try }}</span>
                    @else
                    —
                    @endif
                </td>
                <td class="small text-muted" style="max-width:220px">{{ Str::limit($item->notes, 80) ?: '—' }}</td>
                <td>
                    <span class="badge bg-{{ match($item->status) { 'delivered' => 'success', 'paid' => 'azure', default => 'secondary' } }}-lt">
                        {{ $item->statusLabel() }}
                    </span>
                </td>
                <td class="text-nowrap">
                    @if(can_access('shipments.edit'))
                    <button type="button" class="btn btn-sm btn-ghost-primary" data-bs-toggle="collapse" data-bs-target="#edit-cost-{{ $item->uuid }}">
                        <i class="ti ti-edit"></i>
                    </button>
                    @endif
                    @if(can_access('shipments.delete') || can_access('shipments.create'))
                    <form method="POST" action="{{ route('shipments.costs.destroy', $item) }}" class="d-inline"
                          onsubmit="return confirm(@json(__('app.confirm_delete')))">
                        @csrf @method('DELETE')
                        <input type="hidden" name="redirect" value="{{ $redirect }}">
                        <button type="submit" class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                    </form>
                    @endif
                </td>
            </tr>
            @if(can_access('shipments.edit'))
            <tr class="collapse" id="edit-cost-{{ $item->uuid }}">
                <td colspan="{{ $showShipmentColumn ? 11 : 10 }}">
                    <form method="POST" action="{{ route('shipments.costs.update', $item) }}" class="p-3 bg-light rounded">
                        @csrf @method('PUT')
                        @include('logistics.shipments.costs._form', [
                            'cost' => $item,
                            'shipment' => $item->shipment,
                            'compact' => true,
                            'redirect' => $redirect,
                        ])
                        <button type="submit" class="btn btn-sm btn-primary mt-2">{{ __('app.save') }}</button>
                    </form>
                </td>
            </tr>
            @endif
            @empty
            <tr><td colspan="{{ $showShipmentColumn ? 11 : 10 }}" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
