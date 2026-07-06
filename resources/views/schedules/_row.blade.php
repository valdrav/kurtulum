<tr>
    @foreach($columns as $key => $col)
    <td>
        @if($key === 'entry_date')
        <input type="date" name="entries[{{ $index }}][entry_date]" class="form-control form-control-sm" value="{{ old("entries.$index.entry_date", $entry?->entry_date?->format('Y-m-d')) }}">
        @else
        <input type="text" name="entries[{{ $index }}][{{ $key }}]" class="form-control form-control-sm" value="{{ old("entries.$index.$key", $entry?->value($key)) }}">
        @endif
    </td>
    @endforeach
    <td class="ef-table-actions text-end">
        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-row><i class="ti ti-trash"></i></button>
    </td>
</tr>
