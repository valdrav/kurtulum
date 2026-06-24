{{-- @include('partials.mobile-record-card', ['url' => ..., 'title' => ..., 'subtitle' => ..., 'meta' => ..., 'badge' => ..., 'editUrl' => ..., 'editPermission' => 'orders.edit']) --}}
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
        </div>
    </div>
</div>
