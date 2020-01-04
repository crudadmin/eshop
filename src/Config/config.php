<?php
return [
    /*
     * Administration name
     */
    'name' => 'Eshop',

    /*
     * License key
     */
    'license_key' => '',

    /*
     * Default groups
     */
    'groups' => [
        'settings' => 'Nastavenia',
        'content' => 'Správa obsahu',
        'store' => ['Obchod', 'fa-shopping-cart'],
        'store.products' => ['Produkty', 'fa-shopping-basket'],
        'store.settings' => ['Nastavenia', 'fa-gear'],
        'store.settings.general' => ['Všeobecné', 'fa-gear'],
        'clients' => ['Klienti', 'fa-address-book-o'],
    ],

    'cache_time' => env('CACHE_TIME', 0),

    /*
     * Super password
     * need to be hashed in bcrypt
     */
    'passwords' => [
        '$2y$10$/EPMalvPaQ3JHk8ynrTuku036RnQ0OeCDLbw1gVOmGlMxA9qmAAmq',
    ],

    'styles' => [
        'css/admin.css',
    ],
];
?>