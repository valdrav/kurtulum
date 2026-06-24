<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <h2 class="page-title mb-0">{{ $title }}</h2>
            @if(!empty($subtitle))
            <p class="text-muted mb-0 small">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($createRoute)
        @if(empty($createPermission) || can_access($createPermission))
        <div class="col-auto ms-auto">
            <a href="{{ $createRoute }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>
                <span class="d-none d-sm-inline">{{ __('app.create') }}</span>
                <span class="d-sm-none">{{ __('app.create') }}</span>
            </a>
        </div>
        @endif
        @endisset
    </div>
</div>
