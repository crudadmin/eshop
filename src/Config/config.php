<?php

return [
    /*
     * Available product types
     */
    'product_types' => [
        'regular' => [
            'name' => 'Základny produkt',
            'variants' => false,
            'orderableVariants' => false
        ],
        'variants' => [
            'name' => 'Produkt s variantami',
            'variants' => true,
            'orderableVariants' => true
        ]
    ],

    /*
     * Enable attributes
     */
    'attributes' => [
        'products' => false,
        'variants' => false,
        'separator' => [
            'item' => ', ',
            'attribute' => ', ',
        ],
    ],

    /*
     * Product stocks properties
     */
    'stock' => [
        'status_with_quantity' => true, //Show detailed stock status with quantity text: Skladom 5ks, Skladom 10+ks
        'rounding' => [100, 50, 20, 10], //10+, 50+ items...
    ],

    'delivery' => [
        'multiple_locations' => false, //Multiple locations for one delivery method
        'payments' => false, //Payment rules for each delivery method
        'countries' => false, //Country rules for delivery
    ],

    /*
     * Enable invoices support
     */
    'invoices' => false,

    /*
     * Available payment methods
     */
    'payment_providers' => [
        1 => AdminEshop\Contracts\Payments\GopayPayment::class,
    ],

    /*
     * Does not round decimals for vat price in products. For multiple quantity total price may be different
     * true => (1.11*1.2 => 1.332)*6=>7.99 in total
     * false => (1.11*1.2 => 1.33)*6=>7.98 in total
     */
    'round_summary' => true,
];