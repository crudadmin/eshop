<?php

namespace AdminEshop\Contracts\Order\Mutators;

use Admin;
use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Contracts\Order\Validation\CountryValidator;
use AdminEshop\Models\Orders\Order;
use Cart;
use Facades\AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
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
    private $countryKey = 'country_id';

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
    public function mutateCartResponse($response) : array
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
        $id = $id ?: Cart::getDriver()->get($this->countryKey);

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
        else if ( $selectedDelivery = DeliveryMutator::getSelectedDelivery() ) {
            if ( $selectedDelivery->countries()->where('countries.id', $selectedCountry->getKey())->count() == 0 ) {
                DeliveryMutator::saveDelivery(null);
            }
        }

        Cart::getDriver()->set($this->countryKey, $id);

        return $this;
    }
}

?>