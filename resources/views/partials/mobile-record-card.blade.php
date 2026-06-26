{{-- @include('partials.mobile-record-card', ['url' => ..., 'title' => ..., 'subtitle' => ..., 'meta' => ..., 'badge' => ..., 'editUrl' => ..., 'editPermission' => 'orders.edit', 'deleteUrl' => ..., 'deletePermission' => 'orders.delete', 'deleteConfirm' => __('orders.delete_confirm')]) --}}
<div class="ef-mobile-card">
    <div class="ef-mobile-card-body">
        <div class="ef-mobile-card-main">
            @if(!empty($url))
            <a href="{{ $url }}" class="ef-mobile-card-title">{{ $title }}</a>
            @else
            <div class="ef-mobile-card-title">{{ $title }}</div>
            @endif
            @if(!empty($subtitle))
            <div class="ef-mobile-card-sub">{{ $subtitle }}</div>
            @endif
            @if(!empty($meta))
            <div class="ef-mobile-card-meta">{{ $meta }}</div>
            @endif
        </div>
        <div class="ef-mobile-card-side">
            @if(!empty($badge))
            <span class="badge {{ $badgeClass ?? '' }}">{{ $badge }}</span>
            @endif
            @if(!empty($editUrl) && (empty($editPermission) || can_access($editPermission)))
            <a href="{{ $editUrl }}" class="btn btn-sm btn-ghost-primary ef-mobile-card-edit" aria-label="{{ __('app.edit') }}">
                <i class="ti ti-edit"></i>
            </a>
            @endif
            @if(!empty($restoreUrl) && (empty($deletePermission) || can_access($deletePermission)))
            <form action="{{ $restoreUrl }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-ghost-success ef-mobile-card-edit" aria-label="{{ __('app.restore') }}">
                    <i class="ti ti-rotate"></i>
                </button>
            </form>
            @endif
            @if(!empty($deleteUrl) && (empty($deletePermission) || can_access($deletePermission)))
            <form action="{{ $deleteUrl }}" method="POST" class="d-inline"
                  data-confirm="{{ $deleteConfirm ?? __('app.confirm_delete') }}"
                  data-confirm-title="{{ $deleteConfirmTitle ?? __('app.confirm_title') }}"
                  data-confirm-button="{{ $deleteConfirmButton ?? __('app.delete') }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-ghost-danger ef-mobile-card-edit" aria-label="{{ __('app.delete') }}">
                    <i class="ti ti-trash"></i>
                </button>
            </form>
            @elseif(!empty($deleteBlockReason) && (empty($deletePermission) || can_access($deletePermission)))
            <button type="button" class="btn btn-sm btn-ghost-danger ef-mobile-card-edit disabled" title="{{ $deleteBlockReason }}" disabled aria-label="{{ __('app.delete') }}">
                <i class="ti ti-trash"></i>
            </button>
            @endif
        </div>
    </div>
</div>
