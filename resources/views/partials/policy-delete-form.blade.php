{{-- @include('partials.policy-delete-form', ['action' => route(...), 'confirm' => ..., 'blockReason' => $reason, 'class' => '...', 'iconOnly' => true]) --}}
@if(!empty($blockReason))
<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="{{ $blockReason }}">
    <button type="button" class="btn {{ $class ?? 'btn-outline-danger btn-sm' }} disabled" disabled aria-label="{{ __('app.delete') }}">
        @if($iconOnly ?? false)
        <i class="ti ti-trash"></i>
        @else
        <i class="ti ti-trash me-1"></i>{{ $buttonLabel ?? __('app.delete') }}
        @endif
    </button>
</span>
@else
@include('partials.delete-form', [
    'action' => $action,
    'confirm' => $confirm ?? __('app.confirm_delete'),
    'class' => $class ?? 'btn-outline-danger btn-sm',
    'iconOnly' => $iconOnly ?? false,
    'buttonLabel' => $buttonLabel ?? null,
    'formClass' => $formClass ?? '',
])
@endif
