<?php

namespace AdminEshop\Commands;

use AdminEshop\Contracts\Delivery\DeliveryLocationsImporter;
use AdminEshop\Jobs\CleanEmptyCartTokensJob;
use Illuminate\Console\Command;

class CleanEmptyCartTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eshop:clean-cart-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean and delete old empty cart tokens';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        dispatch(new CleanEmptyCartTokensJob);

        $this->line('Tokens cleaned.');
    }
}
