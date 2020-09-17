<?php

namespace AdminEshop\Commands;

use AdminEshop\Contracts\Delivery\DeliveryLocationsImporter;
use Illuminate\Console\Command;

class ImportPickupPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pickuppoints:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all pickup points into desired deliveries';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $importer = new DeliveryLocationsImporter;
        $importer->setCommand($this);
        $importer->import();

        $this->line('DPD Pickup points has been successfuly imported.');
    }
}
