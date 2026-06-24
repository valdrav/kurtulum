<?php

return [
    'EXW' => [
        'name' => 'Ex Works — İşyerinde Teslim',
        'desc' => 'Satıcı malları kendi tesisinde hazır hale getirir. Taşıma, gümrük ve risk alıcıya aittir.',
    ],
    'FCA' => [
        'name' => 'Free Carrier — Taşıyıcıya Teslim',
        'desc' => 'Satıcı malları belirlenen yerde taşıyıcıya teslim eder. Karayolu ve multimodal için uygundur.',
    ],
    'CPT' => [
        'name' => 'Carriage Paid To — Taşıma Ödenmiş',
        'desc' => 'Satıcı taşıma ücretini varış noktasına kadar öder; risk ilk taşıyıcıya teslimde alıcıya geçer.',
    ],
    'CIP' => [
        'name' => 'Carriage and Insurance Paid — Taşıma ve Sigorta Ödenmiş',
        'desc' => 'CPT gibi; ek olarak satıcı asgari sigortayı da düzenler.',
    ],
    'DAP' => [
        'name' => 'Delivered At Place — Varış Yerinde Teslim',
        'desc' => 'Satıcı malları varış noktasına getirir; boşaltma ve ithalat gümrüğü alıcıya aittir.',
    ],
    'DPU' => [
        'name' => 'Delivered at Place Unloaded — Boşaltılmış Teslim',
        'desc' => 'Satıcı malları varış yerinde boşaltılmış olarak teslim eder.',
    ],
    'DDP' => [
        'name' => 'Delivered Duty Paid — Gümrük Resmi Ödenmiş Teslim',
        'desc' => 'Satıcı tüm taşıma, gümrük ve vergiler dahil teslim eder; alıcı için en az risk.',
    ],
    'FAS' => [
        'name' => 'Free Alongside Ship — Gemi Doğrultusunda Teslim',
        'desc' => 'Satıcı malları yükleme limanında gemi yanına getirir. Deniz taşımacılığına özeldir.',
    ],
    'FOB' => [
        'name' => 'Free On Board — Gemide Teslim',
        'desc' => 'Satıcı malları gemiye yükler; risk ve maliyet gemide alıcıya geçer. İhracatta en yaygın koşullardan biridir.',
    ],
    'CFR' => [
        'name' => 'Cost and Freight — Mal Bedeli ve Navlun',
        'desc' => 'Satıcı navlun bedelini öder; risk gemiye yüklendiğinde alıcıya geçer.',
    ],
    'CIF' => [
        'name' => 'Cost, Insurance and Freight — Navlun ve Sigorta Dahil',
        'desc' => 'Satıcı navlun ve asgari deniz sigortasını öder. Konteyner deniz taşımalarında sık kullanılır.',
    ],
    'help_title' => 'Incoterm Nedir?',
    'help_intro' => 'Incoterm (International Commercial Terms), alıcı ile satıcı arasında taşıma, sigorta ve gümrük sorumluluklarını tanımlayan uluslararası ticaret kurallarıdır. Hangi noktada risk ve maliyetin geçtiğini netleştirir.',
];
