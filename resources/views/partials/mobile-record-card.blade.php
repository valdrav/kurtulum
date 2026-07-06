{{-- @include('partials.mobile-record-card', [...]) --}}
<div class="ef-mobile-card">
    @if(!empty($url))
    <a href="{{ $url }}" class="ef-mobile-card-link">
    @else
    <div class="ef-mobile-card-link ef-mobile-card-link-static">
    @endif
        <div class="ef-mobile-card-top">
            <div class="ef-mobile-card-main">
                <div class="ef-mobile-card-title">{{ $title }}</div>
                @if(!empty($subtitle))
                <div class="ef-mobile-card-sub">{{ $subtitle }}</div>
                @endif
                @if(!empty($meta))
                <div class="ef-mobile-card-meta">{{ $meta }}</div>
                @endif
            </div>
            @if(!empty($badge))
            <span class="badge ef-mobile-card-badge {{ $badgeClass ?? '' }}">{{ $badge }}</span>
            @endif
        </div>
    @if(!empty($url))
    </a>
    @else
    </div>
    @endif
    @php
        $hasEdit = !empty($editUrl) && (empty($editPermission) || can_access($editPermission));
        $hasDelete = !empty($deleteUrl) && (empty($deletePermission) || can_access($deletePermission));
        $hasRestore = !empty($restoreUrl) && (empty($deletePermission) || can_access($deletePermission));
        $hasDeleteBlock = !empty($deleteBlockReason) && (empty($deletePermission) || can_access($deletePermission));
        $hasActions = $hasEdit || $hasDelete || $hasRestore || $hasDeleteBlock;
    @endphp
    @if($hasActions)
    <div class="ef-mobile-card-actions" role="group" aria-label="{{ __('app.actions') }}">
        @if($hasEdit)
        <a href="{{ $editUrl }}" class="btn btn-sm btn-outline-primary ef-mobile-action-btn">
            <i class="ti ti-edit"></i><span>{{ __('app.edit') }}</span>
        </a>
        @endif
        @if($hasRestore)
        <form action="{{ $restoreUrl }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-success ef-mobile-action-btn">
                <i class="ti ti-rotate"></i><span>{{ __('app.restore') }}</span>
            </button>
        </form>
        @endif
        @if($hasDelete)
        <form action="{{ $deleteUrl }}" method="POST" class="d-inline"
              data-confirm="{{ $deleteConfirm ?? __('app.confirm_delete') }}"
              data-confirm-title="{{ $deleteConfirmTitle ?? __('app.confirm_title') }}"
              data-confirm-button="{{ $deleteConfirmButton ?? __('app.delete') }}">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger ef-mobile-action-btn">
                <i class="ti ti-trash"></i><span>{{ __('app.delete') }}</span>
            </button>
        </form>
        @elseif($hasDeleteBlock)
        <button type="button" class="btn btn-sm btn-outline-danger ef-mobile-action-btn disabled" title="{{ $deleteBlockReason }}" disabled>
            <i class="ti ti-trash"></i><span>{{ __('app.delete') }}</span>
        </button>
        @endif
    </div>
    @endif
</div>
