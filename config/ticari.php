<?php

return [
    'name' => env('APP_NAME', 'Kurtulum İç ve Dış Ticaret'),
    'version' => '1.0.0',
    'installed' => env('APP_INSTALLED', false),
    'portal_url' => env('APP_URL', 'https://portal.kurtulum.com'),

    'locales' => [
        'tr' => ['name' => 'Türkçe', 'dir' => 'ltr', 'flag' => 'tr'],
        'en' => ['name' => 'English', 'dir' => 'ltr', 'flag' => 'gb'],
        'ar' => ['name' => 'العربية', 'dir' => 'rtl', 'flag' => 'sa'],
        'de' => ['name' => 'Deutsch', 'dir' => 'ltr', 'flag' => 'de'],
        'fr' => ['name' => 'Français', 'dir' => 'ltr', 'flag' => 'fr'],
    ],

    'default_locale' => 'tr',
    'default_theme' => 'light',

    'transport_modes' => [
        'road' => ['icon' => 'ti-truck', 'color' => 'blue'],
        'sea' => ['icon' => 'ti-ship', 'color' => 'cyan'],
        'air' => ['icon' => 'ti-plane', 'color' => 'purple'],
        'rail' => ['icon' => 'ti-train', 'color' => 'orange'],
        'multimodal' => ['icon' => 'ti-arrows-shuffle', 'color' => 'green'],
    ],

    'incoterms' => [
        'EXW', 'FCA', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP',
        'FAS', 'FOB', 'CFR', 'CIF',
    ],

    'shipment_statuses' => [
        'draft', 'planned', 'booked', 'loading', 'in_transit',
        'port_waiting', 'awaiting_transit', 'at_port', 'discharging',
        'customs', 'delivered', 'completed', 'cancelled',
    ],

    'order_statuses' => [
        'draft', 'confirmed', 'production', 'ready', 'shipped', 'delivered', 'cancelled',
    ],

    'currencies' => ['TRY', 'USD', 'EUR', 'SAR'],

    /** Sipariş / ticari KPI'ların gösterileceği para birimi */
    'trade_currency' => env('TRADE_CURRENCY', 'USD'),

    'bar_currencies' => ['USD', 'EUR', 'SAR'],

    'exchange_rates' => [
        'auto_sync_minutes' => (int) env('EXCHANGE_RATE_SYNC_MINUTES', 15),
        'verify_ssl' => env('HTTP_VERIFY_SSL'),
    ],

    'document_tools' => [
        'soffice_path' => env('SOFFICE_PATH'),
    ],

    'vessel_tracking' => [
        'provider' => env('VESSEL_TRACKING_PROVIDER', 'marinesia'),
        'refresh_minutes' => (int) env('VESSEL_TRACKING_REFRESH_MINUTES', 15),
        'cache_minutes' => (int) env('VESSEL_TRACKING_CACHE_MINUTES', 20),
        'marinesia' => [
            'api_key' => env('MARINESIA_API_KEY'),
            'base_url' => 'https://api.marinesia.com/api/v2',
        ],
        'api_key' => env('VESSEL_TRACKING_API_KEY'),
        'base_url' => env('VESSEL_TRACKING_URL', 'https://api.datalastic.com/api/v0'),
    ],

    'marinetraffic' => [
        'api_key' => env('MARINETRAFFIC_API_KEY'),
        'base_url' => 'https://services.marinetraffic.com/api',
    ],

    'port_coordinates' => [
        'TRIST' => ['lat' => 41.0082, 'lng' => 28.9784],
        'TRIZM' => ['lat' => 38.4237, 'lng' => 27.1428],
        'TRMER' => ['lat' => 36.8121, 'lng' => 34.6415],
        'TRNEM' => ['lat' => 38.796, 'lng' => 26.884],
        'TRLYM' => ['lat' => 36.645, 'lng' => 29.115],
        'LYMIS' => ['lat' => 32.375, 'lng' => 15.092],
        'SAJED' => ['lat' => 21.485, 'lng' => 39.192],
        'EGSOK' => ['lat' => 29.934, 'lng' => 32.553],
        'NLRTM' => ['lat' => 51.9496, 'lng' => 4.1453],
        'DEHAM' => ['lat' => 53.5511, 'lng' => 9.9937],
        'CNSHA' => ['lat' => 31.2304, 'lng' => 121.4737],
    ],

    /** AIS hedef kodu → veritabanı liman kodu */
    'ais_port_aliases' => [
        'LYMRA' => 'TRLYM',
        'LYMIS' => 'LYMIS',
        'ISTANBUL' => 'TRIST',
        'AMBARLI' => 'TRIST',
        'MERSIN' => 'TRMER',
        'ROTTERDAM' => 'NLRTM',
        'HAMBURG' => 'DEHAM',
    ],

    /** Veritabanında olmayan AIS kısaltmaları */
    'ais_port_places' => [
        'LYMRA' => ['country' => 'TR', 'name' => 'Limra Limanı', 'code' => 'TRLYM'],
        'LYMIS' => ['country' => 'LY', 'name' => 'Misurata Limanı', 'code' => 'LYMIS'],
    ],

    'shipment_transitions' => [
        'sea' => [
            'draft' => ['planned', 'booked', 'cancelled'],
            'planned' => ['booked', 'loading', 'cancelled'],
            'booked' => ['loading', 'port_waiting', 'at_port', 'in_transit', 'cancelled'],
            'loading' => ['in_transit', 'port_waiting', 'at_port', 'cancelled'],
            'in_transit' => ['port_waiting', 'awaiting_transit', 'at_port', 'discharging', 'customs', 'cancelled'],
            'port_waiting' => ['awaiting_transit', 'loading', 'discharging', 'in_transit', 'at_port', 'customs', 'cancelled'],
            'awaiting_transit' => ['in_transit', 'port_waiting', 'at_port', 'loading', 'cancelled'],
            'at_port' => ['port_waiting', 'awaiting_transit', 'loading', 'discharging', 'customs', 'in_transit', 'cancelled'],
            'discharging' => ['customs', 'delivered', 'port_waiting', 'cancelled'],
            'customs' => ['delivered', 'in_transit', 'discharging', 'cancelled'],
            'delivered' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ],
        'road' => [
            'draft' => ['planned', 'booked', 'cancelled'],
            'planned' => ['booked', 'cancelled'],
            'booked' => ['in_transit', 'port_waiting', 'customs', 'cancelled'],
            'loading' => ['in_transit', 'cancelled'],
            'in_transit' => ['port_waiting', 'customs', 'delivered', 'cancelled'],
            'port_waiting' => ['in_transit', 'customs', 'cancelled'],
            'awaiting_transit' => ['in_transit', 'cancelled'],
            'at_port' => ['in_transit', 'customs', 'cancelled'],
            'discharging' => ['customs', 'delivered', 'cancelled'],
            'customs' => ['delivered', 'cancelled'],
            'delivered' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ],
        'default' => [
            'draft' => ['planned', 'booked', 'cancelled'],
            'planned' => ['booked', 'cancelled'],
            'booked' => ['in_transit', 'port_waiting', 'at_port', 'cancelled'],
            'loading' => ['in_transit', 'cancelled'],
            'in_transit' => ['port_waiting', 'awaiting_transit', 'at_port', 'customs', 'delivered', 'cancelled'],
            'port_waiting' => ['awaiting_transit', 'in_transit', 'at_port', 'customs', 'cancelled'],
            'awaiting_transit' => ['in_transit', 'port_waiting', 'cancelled'],
            'at_port' => ['customs', 'discharging', 'delivered', 'in_transit', 'cancelled'],
            'discharging' => ['customs', 'delivered', 'cancelled'],
            'customs' => ['delivered', 'cancelled'],
            'delivered' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ],
    ],

    /** Deniz sevkiyatı için hızlı operasyon durumu şablonları */
    'shipment_status_presets' => [
        ['status' => 'port_waiting', 'location' => 'Cidde'],
        ['status' => 'awaiting_transit', 'location' => 'Cidde'],
        ['status' => 'port_waiting', 'location' => 'Misurata'],
        ['status' => 'loading', 'location' => null],
        ['status' => 'in_transit', 'location' => null],
        ['status' => 'discharging', 'location' => null],
        ['status' => 'customs', 'location' => null],
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'openai'),
        'api_key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL', 'gpt-4o-mini'),
    ],

    'update' => [
        'check_url' => env('UPDATE_CHECK_URL', 'https://api.exportflow.app/updates/check'),
    ],
];
