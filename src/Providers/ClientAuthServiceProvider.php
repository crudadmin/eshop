<?php

namespace AdminEshop\Providers;

use Illuminate\Support\ServiceProvider;
use \Admin;

class ClientAuthServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $auth = $this->app['config']->get('auth', []);

        //Register client guard
        $auth['guards']['client'] = [
            'driver' => 'session',
            'provider' => 'clients',
        ];

        //Get client model if is overrided
        $client_model = Admin::getModel('Client');

        //Register provider
        $auth['providers']['clients'] = [
            'driver' => 'eloquent',
            'model' => $client_model ? get_class($client_model) : \AdminEshop\Models\Client\Client::class,
        ];

        //Register passwords
        $auth['passwords']['clients'] = [
            'provider' => 'clients',
            'table' => 'password_resets',
            'expire' => 60,
        ];

        $this->app['config']->set('auth', $auth);
    }
}