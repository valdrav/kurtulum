<ul class="navbar-nav">
    @foreach(navbar()->sidebarItems() as $item)
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
            <span class="nav-link-icon"><i class="ti {{ $item['icon'] }}"></i></span>
            <span class="nav-link-title">{{ __($item['label']) }}</span>
        </a>
    </li>
    @endforeach
    @foreach($moduleMenuItems ?? [] as $menuItem)
    @if(Route::has($menuItem['route'] ?? ''))
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs(($menuItem['route'] ?? '').'*') ? 'active' : '' }}" href="{{ route($menuItem['route']) }}">
            <span class="nav-link-icon"><i class="ti {{ $menuItem['icon'] ?? 'ti-puzzle' }}"></i></span>
            <span class="nav-link-title">{{ $menuItem['title'] }}</span>
        </a>
    </li>
    @endif
    @endforeach
</ul>
