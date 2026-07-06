{{-- İşlem sütunu: @include('partials.table-actions-cell', ['content' => ...]) veya slot --}}
<td class="ef-table-actions text-nowrap text-end">
    {{ $content ?? $slot ?? '' }}
</td>
