{{-- @include('partials.delete-form', ['action' => route(...), 'confirm' => __('app.confirm_delete'), 'class' => 'btn-sm btn-ghost-danger', 'iconOnly' => true]) --}}
<form action="{{ $action }}" method="POST" class="d-inline {{ $formClass ?? '' }}"
      onsubmit="return confirm(@json($confirm ?? __('app.confirm_delete')))">
    @csrf @method('DELETE')
    <button type="submit" class="btn {{ $class ?? 'btn-outline-danger btn-sm' }}" aria-label="{{ __('app.delete') }}">
        @if($iconOnly ?? false)
        <i class="ti ti-trash"></i>
        @else
        <i class="ti ti-trash me-1"></i>{{ __('app.delete') }}
        @endif
    </button>
</form>
