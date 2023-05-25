<?php

return [
    /*
     * Administration name
     */
    'name' => 'Eshop',

    /*
     * Default groups
     */
    'groups' => function(){
        return [
            'settings' => _('Nastavenia'),
            'content' => _('Správa obsahu'),
            'store' => [_('Obchod'), 'fa-shopping-cart'],
            'products' => [_('Produkty'), 'fa-shopping-basket'],
            'clients' => [_('Zákazníci'), 'fa-address-book-o'],
        ];
    },

    /*
     * Super password
     * need to be hashed in bcrypt
     */
    'passwords' => [
        '$2y$10$/EPMalvPaQ3JHk8ynrTuku036RnQ0OeCDLbw1gVOmGlMxA9qmAAmq',
    ],

    'modules' => [
        'AdminEshop\Admin\Modules' => __DIR__.'/../Admin/Modules',
    ],

    'components' => [
        __DIR__.'/../Views/components',
    ],

    /*
     * Add eshop translates resources
     */
    'gettext_source_paths' => [
        app_path('Eshop'),
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
        __DIR__.'/../Helpers',
        __DIR__.'/../Config',
        __DIR__.'/../Admin',
        __DIR__.'/../Eloquent',
        __DIR__.'/../Models',
        __DIR__.'/../Resources/js',
        __DIR__.'/../Views/components',
    ],

    'styles' => [
        'vendor/admineshop/css/eshop.css',
    ],

    'scripts' => [
        'vendor/admineshop/js/app.js',
    ],
];
?>