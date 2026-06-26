{{-- @include('partials.delete-form', ['action' => route(...), 'confirm' => __('orders.delete_confirm')]) --}}
<form action="{{ $action }}" method="POST" class="d-inline {{ $formClass ?? '' }}"
      data-confirm="{{ $confirm ?? __('app.confirm_delete') }}"
      data-confirm-title="{{ $confirmTitle ?? __('app.confirm_title') }}"
      data-confirm-button="{{ $confirmButton ?? __('app.delete') }}"
      data-confirm-danger="{{ ($danger ?? true) ? 'true' : 'false' }}">
    @csrf @method('DELETE')
    <button type="submit" class="btn {{ $class ?? 'btn-outline-danger btn-sm' }}" aria-label="{{ __('app.delete') }}">
        @if($iconOnly ?? false)
        <i class="ti ti-trash"></i>
        @else
        <i class="ti ti-trash me-1"></i>{{ $buttonLabel ?? __('app.delete') }}
        @endif
    </button>
</form>
