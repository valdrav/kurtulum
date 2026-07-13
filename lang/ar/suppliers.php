<?php

return [
    'company_name' => 'Company Name',
    'type' => 'Type',
    'profile' => 'Supplier Profile',
    'purchase_orders' => 'Purchase Orders',
    'products_purchased' => 'Products Purchased',
    'purchase_history' => 'Purchase History',
    'purchase_total' => 'Total Purchase',
    'paid_total' => 'Paid',
    'remaining_payable' => 'Remaining Payable',
    'order_count' => 'Orders',
    'no_orders_hint' => 'No orders linked to this supplier. Select «Supplier (Purchase)» when creating an order or assign a supplier to an existing order.',
    'unlinked_orders_hint' => ':count orders match this supplier from payment/ledger records but are not assigned to the order. Click the button to link permanently.',
    'backfill_orders' => 'Link Orders',
    'backfill_done' => ':count orders linked to this supplier.',
    'cannot_delete_has_orders' => 'This supplier has active orders; cancel or delete orders first.',
    'cannot_delete_has_balance' => 'Ledger account has a balance. Fix finance records first or run `php artisan finance:repair-cari-balances`.',
    'delete_confirm' => 'Are you sure you want to delete this supplier? Ledger account and contact records will also be removed.',
    'new_purchase_order' => 'Create Purchase Order',
    'shipment_costs' => 'Logistics Expenses',
    'product' => 'Product',
    'total_qty' => 'Total Quantity',
    'total_purchase' => 'Purchase Amount',
    'line_items' => 'Line Item Details',
    'customer_sale' => 'Customer (Sale)',
    'purchase_amount' => 'Purchase',
    'types' => [
        'manufacturer' => 'Manufacturer',
        'trader' => 'Trader',
        'logistics' => 'Logistics',
        'service' => 'Service'
    ],
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive'
    ]
];
