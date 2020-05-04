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
        'settings.store' => ['Obchod', 'fa-shopping-cart'],
        'products' => ['Produkty', 'fa-shopping-basket'],
        'clients' => ['Klienti', 'fa-address-book-o'],
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
];
?>