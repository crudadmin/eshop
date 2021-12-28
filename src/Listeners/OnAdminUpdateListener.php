<?php

namespace AdminEshop\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Artisan;
use Admin;

class OnAdminUpdateListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        //Set created order id into cart
        Artisan::call('vendor:publish', [ '--tag' => 'admineshop.resources', '--force' => true ]);

        Admin::addGitignoreFiles([
            public_path('vendor/admineshop')
        ]);
    }
}
