<?php

namespace AdminEshop\Contracts\Order\Mutators;

use Admin;
use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Contracts\Order\Validation\CountryValidator;
use AdminEshop\Models\Orders\Order;
use Cart;
use Store;

class CountryMutator extends Mutator
{
    /**
     * Register validator with this mutators
     *
     * @var  array
     */
    protected $validators = [
        CountryValidator::class,
    ];

    /*
     * driver key for delivery
     */
    const COUNTRY_KEY = 'country_id';

    /**
     * Flush driver data on order successfully completed
     * We does not want to flush country data on order completed
     * we want save previous selected country
     *
     * @var  bool
     */
    public function flushOnComplete()
    {
        return false;
    }

    /**
     * Returns if mutators is active
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActive()
    {
        return config('admineshop.delivery.countries') == true;
    }

    /**
     * Mutation of cart response request
     *
     * @param  $response
     * @return  array
     */
    public function mutateFullCartResponse($response) : array
    {
        return array_merge($response, [
            'selectedCountry' => $this->getSelectedCountry(),
        ]);
    }

    /*
     * Get delivery from driver
     */
    public function getSelectedCountry($id = null)
    {
        $id = $id ?: $this->getDriver()->get(self::COUNTRY_KEY);

        if ( !$id ){
            $clientData = $this->getClientDataMutator()->getClientData();

            $id = ($clientData['delivery_country_id'] ?? null)
                    ?: ($clientData['country_id'] ?? null);
        }

        if ( ! $id ){
            return;
        }

        return $this->cache('selectedDelivery'.$id, function() use ($id) {
            return Store::getCountries()->where('id', $id)->first();
        });
    }

    /**
     * Save delivery into driver
     *
     * @param  int|null  $id
     * @return  this
     */
    public function setCountry($id = null)
    {
        $selectedCountry = $this->getSelectedCountry($id);

        //Wrong id has been passed
        if ( !$selectedCountry ) {
            $id = null;
        }

        //If delivery is selected already, we need check if selected delivery has this country
        //if no, we need reset delivery
        else if ( $selectedDelivery = $this->getDeliveryMutator()->getSelectedDelivery() ) {
            if ( $selectedDelivery->countries()->where('countries.id', $selectedCountry->getKey())->count() == 0 ) {
                $this->getDeliveryMutator()->setDelivery(null);
            }
        }

        $this->getDriver()->set(self::COUNTRY_KEY, $id);

        return $this;
    }
}

?>