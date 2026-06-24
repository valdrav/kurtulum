<?php

return [
    'EXW' => ['name' => 'Ex Works', 'desc' => 'Seller makes goods available at their premises. Buyer bears all transport and risk.'],
    'FCA' => ['name' => 'Free Carrier', 'desc' => 'Seller delivers goods to the carrier at the named place. Common for road transport.'],
    'CPT' => ['name' => 'Carriage Paid To', 'desc' => 'Seller pays freight to destination; risk passes when goods are handed to the first carrier.'],
    'CIP' => ['name' => 'Carriage and Insurance Paid', 'desc' => 'Like CPT; seller also provides minimum insurance.'],
    'DAP' => ['name' => 'Delivered At Place', 'desc' => 'Seller delivers to destination; buyer handles import clearance and unloading.'],
    'DPU' => ['name' => 'Delivered at Place Unloaded', 'desc' => 'Seller delivers and unloads at the named place.'],
    'DDP' => ['name' => 'Delivered Duty Paid', 'desc' => 'Seller bears all costs including import duties. Maximum obligation for seller.'],
    'FAS' => ['name' => 'Free Alongside Ship', 'desc' => 'Seller places goods alongside the vessel at the port. Sea transport only.'],
    'FOB' => ['name' => 'Free On Board', 'desc' => 'Seller loads goods on board the vessel; risk passes on board. Very common for sea exports.'],
    'CFR' => ['name' => 'Cost and Freight', 'desc' => 'Seller pays freight; risk passes when goods are on board.'],
    'CIF' => ['name' => 'Cost, Insurance and Freight', 'desc' => 'Seller pays freight and minimum marine insurance.'],
    'help_title' => 'What is an Incoterm?',
    'help_intro' => 'Incoterms define who pays for transport, insurance and customs between buyer and seller in international trade.',
];
