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
        //Allowed models with attributes
        'eloquents' => [
            AdminEshop\Models\Products\Product::class,
            AdminEshop\Models\Products\ProductsVariant::class,
        ],
        'load_in_cart' => true, //Load attributes data in cart responses
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
        'rounding_less_than_char' => '<',
    ],

    /*
     * Delivery settings
     */
    'delivery' => [
        'multiple_locations' => false, //Multiple locations for one delivery method
        'multiple_locations_autoload' => false, //Automatically load multiple locations into cart response
        'payments' => false, //Payment rules for each delivery method
        'countries' => false, //Country rules for delivery
        'providers' => [
            // 3 => [
            //     'provider' => AdminEshop\Contracts\Delivery\DPD\DPDShipping::class,
            //     'options' => [
            //         'type' => 'parcelshop',
            //         'import_locations' => true,
            //     ],
            // ],
        ],
    ],

    /*
     * Discount settings
     */
    'discounts' => [
        'classes' => [
            // AdminEshop\Contracts\Discounts\DiscountCode::class,
            // AdminEshop\Contracts\Discounts\FreeDeliveryFromPrice::class,
            // AdminEshop\Contracts\Discounts\FreeDeliveryByCode::class,
        ],
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

    'cart' => [
        //Return full cart response with all deliveries, payment methods etc,,, in every cart request
        //add/update/delete items, manage discount codes, etc... By default those data are returned only on cart page.
        //But we can turn it on everywere
        'default_full_response' => false,

        //For REST authorization
        'header_key' => 'Cart-Token',

        'driver' => AdminEshop\Contracts\Cart\Drivers\SessionDriver::class,
        // 'driver' => AdminEshop\Contracts\Cart\Drivers\MySqlDriver::class,

        //For session (NonRest) authorization with MySqlDriver..
        //If we want use header_key (REST), we need turn off session
        'session' => true,

        //Cart and order mutators
        'mutators' => [
            AdminEshop\Contracts\Order\Mutators\ClientDataMutator::class,
            AdminEshop\Contracts\Order\Mutators\CountryMutator::class,
            AdminEshop\Contracts\Order\Mutators\DeliveryMutator::class,
            AdminEshop\Contracts\Order\Mutators\PaymentMethodMutator::class,
        ],

        'order' => [
            //Submit order validato request
            'validator' => AdminEshop\Requests\SubmitOrderRequest::class,

            //Which additional validation rules needs to be applied
            'validator_rules' => [
                'license_terms' => 'required',
            ],

            //Which field should be displayed in email template in section "Additional fields"
            'additional_email_fields' => [],
        ],
    ],

    'mail' => [
        //Show without vat product values in email template
        'show_no_vat' => false,
    ],
];