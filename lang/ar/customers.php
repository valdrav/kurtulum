<?php

return [
    'company_name' => 'Company Name',
    'contact_person' => 'Contact Person',
    'country' => 'Country',
    'type' => 'Type',
    'profile' => 'Customer Profile',
    'sale_orders' => 'Sales Orders',
    'products_sold' => 'Products Sold',
    'sale_total' => 'Total Sales',
    'collected_total' => 'Collected',
    'remaining_receivable' => 'Remaining Receivable',
    'order_count' => 'Orders',
    'line_items' => 'Line Item Details',
    'product' => 'Product',
    'total_qty' => 'Total Quantity',
    'total_sale' => 'Sales Amount',
    'supplier_purchase' => 'Supplier (Purchase)',
    'sale_amount' => 'Sale',
    'new_sale_order' => 'Create Sales Order',
    'no_orders_hint' => 'No orders linked to this customer. Select the customer when creating an order or edit an existing order.',
    'cannot_delete_has_orders' => 'This customer has active orders; cancel or delete orders first.',
    'cannot_delete_has_balance' => 'Ledger account has a balance. Fix finance records first or run `php artisan finance:repair-cari-balances`.',
    'cannot_delete_has_shipments' => 'Active shipment records exist; complete or cancel shipments first.',
    'delete_confirm' => 'Are you sure you want to delete this customer? Ledger account and contact records will also be removed.',
    'types' => [
        'buyer' => 'Buyer',
        'agent' => 'Agent',
        'distributor' => 'Distributor',
        'partner' => 'Partner'
    ],
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'prospect' => 'Prospect'
    ]
];
