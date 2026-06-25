@php
    $currentRoute = request()->route()?->getName() ?? '';
    $links = [
        ['route' => 'finance.treasury', 'icon' => 'ti-cash', 'label' => 'finance.treasury', 'routes' => ['finance.treasury', 'finance.index']],
        ['route' => 'finance.wallet', 'icon' => 'ti-wallet', 'label' => 'finance.wallet', 'routes' => ['finance.wallet']],
        ['route' => 'finance.income-expenses', 'icon' => 'ti-list', 'label' => 'finance.movements', 'routes' => ['finance.income-expenses', 'finance.income-expenses.edit']],
        ['route' => 'finance.accounts', 'icon' => 'ti-address-book', 'label' => 'finance.cari_accounts', 'routes' => ['finance.accounts', 'finance.accounts.create', 'finance.accounts.edit', 'finance.accounts.show', 'finance.payments', 'finance.payments.show', 'finance.payments.edit', 'finance.collections', 'finance.collections.show', 'finance.collections.edit']],
        ['route' => 'finance.profit-loss', 'icon' => 'ti-chart-bar', 'label' => 'finance.profit_loss', 'routes' => ['finance.profit-loss']],
    ];
@endphp
<div class="ef-finance-nav mb-3">
    <div class="nav nav-pills flex-wrap gap-1">
        @foreach($links as $link)
        <a href="{{ route($link['route']) }}" class="nav-link {{ in_array($currentRoute, $link['routes'], true) ? 'active' : '' }}">
            <i class="ti {{ $link['icon'] }} me-1"></i>{{ __($link['label']) }}
        </a>
        @endforeach
    </div>
</div>
