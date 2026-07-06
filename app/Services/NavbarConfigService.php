<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Arr;

class NavbarConfigService
{
    public const SETTING_KEY = 'navbar_config';

    /** @return list<array{id: string, route: string, icon: string, label: string, perm: string, mobile_label?: string}> */
    public function catalog(): array
    {
        return [
            ['id' => 'dashboard', 'route' => 'dashboard', 'icon' => 'ti-dashboard', 'label' => 'app.dashboard', 'perm' => 'dashboard.view', 'mobile_label' => 'Panel'],
            ['id' => 'customers', 'route' => 'customers.index', 'icon' => 'ti-users', 'label' => 'app.customers', 'perm' => 'customers.view'],
            ['id' => 'suppliers', 'route' => 'suppliers.index', 'icon' => 'ti-building-factory', 'label' => 'app.suppliers', 'perm' => 'suppliers.view'],
            ['id' => 'orders', 'route' => 'orders.index', 'icon' => 'ti-shopping-cart', 'label' => 'app.orders', 'perm' => 'orders.view', 'mobile_label' => 'Sipariş'],
            ['id' => 'shipments', 'route' => 'shipments.index', 'icon' => 'ti-truck-delivery', 'label' => 'app.shipments', 'perm' => 'shipments.view', 'mobile_label' => 'Sevkiyat'],
            ['id' => 'shipment_costs', 'route' => 'shipments.costs.index', 'icon' => 'ti-receipt', 'label' => 'logistics.shipment_costs', 'perm' => 'shipments.view'],
            ['id' => 'vessel_tracking', 'route' => 'vessels.track.index', 'icon' => 'ti-ship', 'label' => 'logistics.vessel_tracking', 'perm' => 'shipments.view'],
            ['id' => 'finance', 'route' => 'finance.index', 'icon' => 'ti-currency-lira', 'label' => 'app.finance', 'perm' => 'finance.view', 'mobile_label' => 'Finans'],
            ['id' => 'documents', 'route' => 'documents.index', 'icon' => 'ti-files', 'label' => 'app.documents', 'perm' => 'documents.view'],
            ['id' => 'tasks', 'route' => 'tasks.index', 'icon' => 'ti-checklist', 'label' => 'app.tasks', 'perm' => 'tasks.view', 'mobile_label' => 'Görev'],
            ['id' => 'schedules', 'route' => 'schedules.index', 'icon' => 'ti-calendar-week', 'label' => 'schedules.title', 'perm' => 'schedules.access'],
            ['id' => 'directory', 'route' => 'directory.index', 'icon' => 'ti-address-book', 'label' => 'directory.title', 'perm' => 'directory.view'],
            ['id' => 'hr', 'route' => 'hr.index', 'icon' => 'ti-id-badge-2', 'label' => 'hr.title', 'perm' => 'hr.access'],
            ['id' => 'reports', 'route' => 'reports.index', 'icon' => 'ti-chart-bar', 'label' => 'app.reports', 'perm' => 'reports.view'],
            ['id' => 'emails', 'route' => 'emails.index', 'icon' => 'ti-mail', 'label' => 'app.emails', 'perm' => 'emails.view'],
            ['id' => 'doc_tools', 'route' => 'documents.tools.index', 'icon' => 'ti-file-settings', 'label' => 'documents.tools.title', 'perm' => 'documents.view'],
            ['id' => 'ai', 'route' => 'ai.index', 'icon' => 'ti-sparkles', 'label' => 'app.ai_assistant', 'perm' => 'ai.view'],
            ['id' => 'settings', 'route' => 'settings.index', 'icon' => 'ti-settings', 'label' => 'app.settings', 'perm' => 'settings.view'],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        $sidebarOrder = array_column($this->catalog(), 'id');

        return [
            'desktop' => [
                'sidebar_width' => '17rem',
                'sidebar_bg_start' => '#1e1b4b',
                'sidebar_bg_mid' => '#312e81',
                'sidebar_bg_end' => '#1e293b',
                'sidebar_text' => '#e2e8f0',
                'sidebar_active_rgb' => '99, 102, 241',
                'show_brand_text' => true,
                'show_user_footer' => true,
                'items' => array_fill_keys($sidebarOrder, ['enabled' => true]),
            ],
            'mobile' => [
                'bottom_height' => '4.25rem',
                'show_currency_bar' => true,
                'show_bottom_nav' => true,
                'bottom_items' => ['dashboard', 'finance', 'orders', 'tasks', 'shipments'],
                'more_items' => ['tasks', 'documents', 'customers', 'suppliers', 'vessel_tracking', 'emails', 'reports', 'settings'],
            ],
            'topbar' => [
                'height' => '3.75rem',
                'show_locale' => true,
                'show_theme_toggle' => true,
                'show_brand_desktop' => true,
                'show_profile_menu' => true,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $stored = Setting::get(self::SETTING_KEY);
        $decoded = is_string($stored) ? json_decode($stored, true) : null;

        return $this->mergeConfig(is_array($decoded) ? $decoded : []);
    }

    /** @param array<string, mixed> $input */
    public function save(array $input): void
    {
        $merged = $this->mergeConfig($input);
        Setting::set(self::SETTING_KEY, json_encode($merged, JSON_UNESCAPED_UNICODE), 'navbar');
    }

    /** @return list<array<string, mixed>> */
    public function sidebarItems(): array
    {
        return $this->resolveItems('desktop', 'items', array_column($this->catalog(), 'id'));
    }

    /** @return list<array<string, mixed>> */
    public function mobileBottomItems(): array
    {
        $ids = $this->all()['mobile']['bottom_items'] ?? [];

        return $this->resolveItemsByIds(is_array($ids) ? $ids : []);
    }

    /** @return list<array<string, mixed>> */
    public function mobileMoreItems(): array
    {
        $ids = $this->all()['mobile']['more_items'] ?? [];

        return $this->resolveItemsByIds(is_array($ids) ? $ids : []);
    }

    public function showCurrencyBar(): bool
    {
        return (bool) ($this->all()['mobile']['show_currency_bar'] ?? true);
    }

    public function showBottomNav(): bool
    {
        return (bool) ($this->all()['mobile']['show_bottom_nav'] ?? true);
    }

    public function showTopbarFlag(string $flag): bool
    {
        return (bool) ($this->all()['topbar'][$flag] ?? true);
    }

    public function showSidebarFlag(string $flag): bool
    {
        return (bool) ($this->all()['desktop'][$flag] ?? true);
    }

    /** @return array<string, string> */
    public function cssVariables(): array
    {
        $cfg = $this->all();

        return [
            '--ef-sidebar-w' => $cfg['desktop']['sidebar_width'] ?? '17rem',
            '--ef-topbar-h' => $cfg['topbar']['height'] ?? '3.75rem',
            '--ef-bottom-nav-h' => $cfg['mobile']['bottom_height'] ?? '4.25rem',
            '--ef-sidebar-text' => $cfg['desktop']['sidebar_text'] ?? '#e2e8f0',
            '--ef-sidebar-active-rgb' => $cfg['desktop']['sidebar_active_rgb'] ?? '99, 102, 241',
        ];
    }

    public function sidebarBackground(): string
    {
        $d = $this->all()['desktop'];

        return sprintf(
            'linear-gradient(180deg, %s 0%%, %s 48%%, %s 100%%)',
            $d['sidebar_bg_start'] ?? '#1e1b4b',
            $d['sidebar_bg_mid'] ?? '#312e81',
            $d['sidebar_bg_end'] ?? '#1e293b',
        );
    }

    /** @param array<string, mixed> $custom */
    protected function mergeConfig(array $custom): array
    {
        return array_replace_recursive($this->defaults(), $custom);
    }

    /** @param list<string> $order */
    protected function resolveItems(string $section, string $itemsKey, array $order): array
    {
        $cfg = $this->all();
        $enabledMap = $cfg[$section][$itemsKey] ?? [];
        $byId = collect($this->catalog())->keyBy('id');
        $items = [];

        foreach ($order as $id) {
            if (! $byId->has($id)) {
                continue;
            }
            if (($enabledMap[$id]['enabled'] ?? true) === false) {
                continue;
            }
            $item = $byId->get($id);
            if (! can_access($item['perm'])) {
                continue;
            }
            if (! \Illuminate\Support\Facades\Route::has($item['route'])) {
                continue;
            }
            $items[] = $item;
        }

        return $items;
    }

    /** @param list<string> $ids */
    protected function resolveItemsByIds(array $ids): array
    {
        $byId = collect($this->catalog())->keyBy('id');
        $items = [];

        foreach ($ids as $id) {
            if (! $byId->has($id)) {
                continue;
            }
            $item = $byId->get($id);
            if (! can_access($item['perm'])) {
                continue;
            }
            if (! \Illuminate\Support\Facades\Route::has($item['route'])) {
                continue;
            }
            $items[] = $item;
        }

        return $items;
    }

    /** @return array<string, array{id: string, route: string, icon: string, label: string, perm: string, mobile_label?: string}> */
    public function catalogKeyed(): array
    {
        return collect($this->catalog())->keyBy('id')->all();
    }
}
