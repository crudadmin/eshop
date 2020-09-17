<?php

namespace AdminEshop\Contracts\Delivery;

use AdminEshop\Models\Delivery\DeliveriesLocation;
use Carbon\Carbon;
use Facades\AdminEshop\Contracts\Delivery\DPDShipping;
use OrderService;

class DeliveryLocationsImporter
{
    private $configKey = 'admineshop.delivery.providers';

    private $command;

    public function setCommand($command)
    {
        $this->command = $command;
    }

    private function getCommand()
    {
        return $this->command;
    }

    public function import()
    {
        foreach (config($this->configKey) as $deliveryId => $value) {
            $provider = OrderService::getProviderById($this->configKey, $deliveryId);

            if ( !method_exists($provider, 'importLocations') || @$provider->getOptions()['import_locations'] !== true ){
                continue;
            }

            $this->importDelivery($deliveryId, $provider);
        }
    }

    private function importDelivery($deliveryId, $provider)
    {
        $this->getCommand()->comment('Importing delivery: '.$deliveryId);

        $locationsToImport = $provider->importLocations();

        $total = count($locationsToImport);

        //If no locations to import has been found
        if ( $total == 0 ){
            $this->getCommand()->line('No found locations to import.');
            return;
        }

        $importIdentifier = array_column($locationsToImport, 'identifier');

        $existingLocations = DeliveriesLocation::where('delivery_id', $deliveryId)
                                        ->whereIn('identifier', $importIdentifier)
                                        ->withUnpublished()
                                        ->get();

        $this->getCommand()->line('To import locations: '.$total);

        //Create/update/locations
        foreach ($locationsToImport as $i => $location) {
            $percentage = ceil(100/$total*$i);

            if ( $percentage % 10 == 0 ) {
                $this->getCommand()->line('Imported: '.($i+1).'/'.$total.' ('.$percentage.'%)');
            }

            $this->updateOrCreateLocation($existingLocations, $deliveryId, $location);
        }

        //Hide locations missing in import
        DeliveriesLocation::where('delivery_id', $deliveryId)
                            ->whereNotIn('identifier', $importIdentifier)
                            ->update([
                                'published_at' => null,
                            ]);
    }

    private function updateOrCreateLocation($existingLocations, $deliveryId, $location)
    {
        //If locations does exists, just update and publish it.
        if ( $dbLocation = $existingLocations->where('identifier', $location['identifier'])->first() ){
            if ( ! $dbLocation->published_at ){
                $dbLocation->published_at = Carbon::now();
            }

            $dbLocation->update($location);
        }

        //Create new location if is missing
        else {
            $dbLocation = DeliveriesLocation::create($location + [
                'delivery_id' => $deliveryId
            ]);
        }
    }
}