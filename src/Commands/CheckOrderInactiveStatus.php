<?php

namespace AdminEshop\Commands;

use Admin;
use AdminEshop\Jobs\SetOrderStatusAfterInactivness;
use Illuminate\Console\Command;

class CheckOrderInactiveStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eshop:check-order-inactive-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check inactive statuses.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        dispatch(new SetOrderStatusAfterInactivness);

        $this->line('Order statuses checked.');
    }
}
