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