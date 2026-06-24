<?php

return [
    'order' => [
        'draft' => 'Draft',
        'confirmed' => 'Confirmed',
        'production' => 'In Production',
        'ready' => 'Ready to Ship',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ],
    'shipment' => [
        'draft' => 'Draft',
        'planned' => 'Planned',
        'booked' => 'Booked / Loading',
        'loading' => 'Loading',
        'in_transit' => 'In Transit',
        'port_waiting' => 'Waiting at Port',
        'awaiting_transit' => 'Awaiting Transit / Crossing',
        'at_port' => 'At Port (Operations)',
        'discharging' => 'Discharging',
        'customs' => 'Customs',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
    'milestone' => [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'delayed' => 'Delayed',
    ],
    'leg' => [
        'pending' => 'Pending',
        'in_transit' => 'In Transit',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
];
