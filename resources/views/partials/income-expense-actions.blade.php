@props(['item'])

<div class="d-inline-flex align-items-center gap-1 text-nowrap">
    @if(can_access('finance.edit'))
    <a href="{{ route('finance.income-expenses.edit', $item) }}" class="btn btn-sm btn-ghost-primary" title="{{ __('app.edit') }}">
        <i class="ti ti-edit"></i>
    </a>
    @endif
    @if(can_access('finance.delete') || can_access('finance.create'))
    <form method="POST" action="{{ route('finance.income-expenses.destroy', $item) }}" class="d-inline"
          onsubmit="return confirm(@json(__('app.confirm_delete')))">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-ghost-danger" title="{{ __('app.delete') }}">
            <i class="ti ti-trash"></i>
        </button>
    </form>
    @endif
</div>
