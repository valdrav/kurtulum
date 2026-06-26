{{-- @include('partials.crm-delete-button', ['destroyRoute' => ..., 'confirm' => ..., 'blockReason' => ..., 'permission' => 'customers.delete']) --}}
@if(can_access($permission ?? 'customers.delete'))
    @if(!empty($blockReason))
    <button type="button" class="btn {{ $class ?? 'btn-outline-danger btn-sm' }} disabled" title="{{ $blockReason }}" disabled>
        <i class="ti ti-trash{{ ($iconOnly ?? false) ? '' : ' me-1' }}"></i>@unless($iconOnly ?? false){{ __('app.delete') }}@endunless
    </button>
    @else
    @include('partials.delete-form', [
        'action' => $destroyRoute,
        'confirm' => $confirm ?? __('app.confirm_delete'),
        'class' => $class ?? 'btn-outline-danger btn-sm',
        'iconOnly' => $iconOnly ?? false,
    ])
    @endif
@endif
