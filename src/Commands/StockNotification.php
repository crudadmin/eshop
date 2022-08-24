<?php

namespace AdminEshop\Commands;

use AdminEshop\Jobs\ProductAvaiabilityChecker;
use Illuminate\Console\Command;

class StockNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eshop:stock-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check stock notifications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        dispatch(new ProductAvaiabilityChecker);

        return 0;
    }
}
