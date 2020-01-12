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
];