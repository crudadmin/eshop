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
        'settings' => _('Nastavenia'),
        'content' => _('Správa obsahu'),
        'store' => [_('Obchod'), 'fa-shopping-cart'],
        'products' => [_('Produkty'), 'fa-shopping-basket'],
        'clients' => [_('Zákazníci'), 'fa-address-book-o'],
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
        __DIR__.'/../Controllers',
        __DIR__.'/../Contracts',
        __DIR__.'/../Eloquent',
        __DIR__.'/../Helpers',
        __DIR__.'/../Notifications',
        __DIR__.'/../Mail',
        __DIR__.'/../Views',
    ],

    'gettext_admin_source_paths' => [
        __DIR__.'/../Config/admin.php',
        __DIR__.'/../Admin',
        __DIR__.'/../Resources/js',
    ],

    'styles' => [
        'vendor/admineshop/css/eshop.css',
    ],

    'scripts' => [
        'vendor/admineshop/js/app.js',
    ],
];
?>