@if(navbar()->showBottomNav())
<nav class="ef-bottom-nav d-lg-none" aria-label="Mobil navigasyon">
    @foreach(navbar()->mobileBottomItems() as $item)
    <a href="{{ route($item['route']) }}"
       class="ef-bottom-nav-item {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']) ? 'active' : '' }}">
        <i class="ti {{ $item['icon'] }}"></i>
        <span>{{ $item['mobile_label'] ?? __($item['label']) }}</span>
    </a>
    @endforeach
    <button type="button" class="ef-bottom-nav-item" data-bs-toggle="offcanvas" data-bs-target="#mobileMoreMenu">
        <i class="ti ti-dots"></i>
        <span>Daha</span>
    </button>
</nav>

<div class="offcanvas offcanvas-bottom ef-more-sheet" tabindex="-1" id="mobileMoreMenu">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menü</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="row g-2">
            @foreach(navbar()->mobileMoreItems() as $item)
            <div class="col-4">
                <a href="{{ route($item['route']) }}" class="ef-more-tile">
                    <i class="ti {{ $item['icon'] }}"></i>
                    <span>{{ __($item['label']) }}</span>
                </a>
            </div>
            @endforeach
            <div class="col-4">
                <a href="{{ route('profile.edit') }}" class="ef-more-tile">
                    <i class="ti ti-user"></i>
                    <span>{{ __('app.profile') }}</span>
                </a>
            </div>
            <div class="col-4">
                <form action="{{ route('logout') }}" method="POST" class="h-100">
                    @csrf
                    <button type="submit" class="ef-more-tile w-100 h-100 border-0 text-danger">
                        <i class="ti ti-logout"></i>
                        <span>{{ __('app.logout') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
