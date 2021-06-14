<?php

return [
    /*
     * Automaticaly boot routes
     */
    'routes' => [
        'enabled' => [
            'products' => false,
            'discounts' => false,
            'cart' => false,
            'cart_submit' => false,
            'cart_payments' => false,
        ],
    ],

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
            'orderableVariants' => true,
            'loadInCart' => false, //variants relation in product model should not be loaded, because variant is loaded separately in cart item
        ]
    ],

    'categories' => [
        'enabled' => false,
        'max_level' => 1,
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
        'filtrable' => true, //Toggle if attribute should be presnet in filter
        'attributesText' => false, //display attributesText property in products
        'attributesVariants' => false, //set which attributes can define variants
        'separator' => [
            'item' => ', ',
            'attribute' => ', ',
        ],
        'types' => [
            'colors' => false,
            'images' => false,
        ],
    ],

    'gallery' => [
        //Allowed models with gallery
        'eloquents' => [
            AdminEshop\Models\Products\Product::class,
            // AdminEshop\Models\Products\ProductsVariant::class,
        ],
    ],

    /*
     * Product stocks properties
     */
    'stock' => [
        'status_with_quantity' => true, //Show detailed stock status with quantity text: Skladom 5ks, Skladom 10+ks
        'rounding' => [100, 50, 20, 10], //10+, 50+ items...
        'rounding_less_than_char' => '<',
        'rounding_more_than_char' => '>',
    ],

    /*
     * Delivery settings
     */
    'delivery' => [
        'enabled' => true,
        'multiple_locations' => false, //Multiple locations for one delivery method
        'multiple_locations_autoload' => false, //Automatically load multiple locations into cart response
        'payments' => false, //Payment rules for each delivery method
        'countries' => false, //Country rules for delivery
        'price_limit' => false, //Deny deliveries over price
        'packeta' => false, //Is packeta enabled?
        'providers' => [
            // env('DELIVERY_DPD_ID') => [
            //     'provider' => AdminEshop\Contracts\Delivery\DPD\DPDShipping::class,
            //     'options' => [
            //         'type' => 'parcelshop',
            //         'import_locations' => true,
            //     ],
            // ],
            // env('DELIVERY_PACKETA_ID') => [
            //     'provider' => AdminEshop\Contracts\Delivery\Packeta\PacketaShipping::class,
            //     'options' => [
            //         'API_KEY' => env('PACKETA_API_KEY'),
            //         'HOST' => 'http://www.zasilkovna.cz',
            //     ],
            // ],
        ],
    ],

    /**
     * Payment methods settings
     */
    'payments_methods' => [
        'enabled' => true,
        'price_limit' => true,

        /*
         * Available payment methods
         */
        'providers' => [
            1 => AdminEshop\Contracts\Payments\GopayPayment::class,
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
        'header_token' => 'Cart-Token',

        'driver' => AdminEshop\Contracts\Cart\Drivers\SessionDriver::class,
        // 'driver' => AdminEshop\Contracts\Cart\Drivers\MySqlDriver::class,

        //For session (NonRest) authorization with MySqlDriver..
        //If we want use header_token (REST), we need turn off session
        'session' => true,

        //Cart and order mutators
        'mutators' => [
            AdminEshop\Contracts\Order\Mutators\BaseOrderMutator::class,
            AdminEshop\Contracts\Order\Mutators\ClientDataMutator::class,
            AdminEshop\Contracts\Order\Mutators\CountryMutator::class,
            AdminEshop\Contracts\Order\Mutators\DeliveryMutator::class,
            AdminEshop\Contracts\Order\Mutators\PaymentMethodMutator::class,
            AdminEshop\Contracts\Order\Mutators\PacketaMutator::class,
        ],

        'order' => [
            'number' => [
                'custom' => true, //If custom order number is turned on, number will be generated from ym00000number
                'length' => 6, //How many decimals hould order number consist of. 000123
            ],

            //Submit order validato request
            'validator' => AdminEshop\Requests\SubmitOrderRequest::class,

            //Which additional validation rules needs to be applied
            'validator_rules' => [
                'license_terms' => 'required',
            ],

            //Which field should be displayed in email template in section "Additional fields"
            'additional_email_fields' => [],

            /**
             * For example if is_company is not checked, we can reset all company fields when they present in request.
             *
             * example value for both parameters:
             * [ 'is_company' => ['company_name', 'company_id', 'company_tax_id', 'company_vat_id'] ] -> reset all company fields, if is_company is not checked
             * [] -> does not reset anything
             * null -> reset by defailt settings
             */
            'fields_reset_process' => null, //Reseting fields in mid-save detail info process step
            'fields_reset_submit' => null, //Reseting fields in final submit order step
        ],

        //Reregister cart identifier
        'identifiers' => [],

        //What identifier will be used by default for all cart requests
        'default_identifier' => 'ProductsIdentifier',
    ],

    'mail' => [
        //Show without vat product values in email template
        'show_no_vat' => false,
    ],

    'client' => [
        'addresses' => false,
        'favourites' => false,
        'groups' => false,
    ],

    /*
     * Validation for phone numbers in order/clients,address etc...
     * SK,CZ...
     */
    'phone_countries' => 'SK,CZ',

    /*
     * Should be products and other attributes localized?
     */
    'localization' => false,
];