<form action="{{ $action }}" method="POST" class="d-inline {{ $formClass ?? '' }}">
    @csrf
    <button type="submit" class="btn {{ $class ?? 'btn-outline-success btn-sm' }}">
        @if($iconOnly ?? false)
        <i class="ti ti-rotate"></i>
        @else
        <i class="ti ti-rotate me-1"></i>{{ $buttonLabel ?? __('app.restore') }}
        @endif
    </button>
</form>
