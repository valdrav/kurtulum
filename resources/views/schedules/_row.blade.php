<tr>
    @foreach($columns as $key => $col)
    <td>
        @if(($col['type'] ?? 'text') === 'date')
        <input type="date" name="entries[{{ $index }}][{{ $key }}]" class="form-control form-control-sm" value="{{ old("entries.$index.$key", $entry?->rawValue($key)) }}">
        @else
        <input type="text" name="entries[{{ $index }}][{{ $key }}]" class="form-control form-control-sm" value="{{ old("entries.$index.$key", $entry?->rawValue($key)) }}">
        @endif
    </td>
    @endforeach
    <td class="ef-table-actions text-end">
        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-row title="{{ __('app.delete') }}"><i class="ti ti-trash"></i></button>
    </td>
</tr>
