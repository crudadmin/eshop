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
        'products' => ['Produkty', 'fa-shopping-basket'],
        'clients' => ['Zákazníci', 'fa-address-book-o'],
    ],

    /*
     * Super password
     * need to be hashed in bcrypt
     */
    'passwords' => [
        '$2y$10$/EPMalvPaQ3JHk8ynrTuku036RnQ0OeCDLbw1gVOmGlMxA9qmAAmq',
    ],

    'components' => [
        __DIR__.'/../Views/components',
    ],

    /*
     * Add eshop translates resources
     */
    'gettext_source_paths' => [
        __DIR__.'/../Models',
        __DIR__.'/../Contracts',
        __DIR__.'/../Eloquent',
        __DIR__.'/../Helpers',
        __DIR__.'/../Notifications',
        __DIR__.'/../Views',
    ],

    'styles' => [
        'vendor/admineshop/css/eshop.css',
    ],

    'scripts' => [
        'vendor/admineshop/js/app.js',
    ],
];
?>