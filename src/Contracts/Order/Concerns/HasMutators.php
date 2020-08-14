<?php

namespace AdminEshop\Contracts\Order\Concerns;

use AdminEshop\Contracts\Order\Mutators\ClientDataMutator;
use AdminEshop\Contracts\Order\Mutators\CountryMutator;
use AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
use AdminEshop\Contracts\Order\Mutators\PaymentMethodMutator;
use Admin;
use Cart;

trait HasMutators
{
    /**
     * Available order mutators
     *
     * @var  array
     */
    protected $mutators = [
        ClientDataMutator::class,
        CountryMutator::class,
        DeliveryMutator::class,
        PaymentMethodMutator::class,
    ];

    /**
     * Register new order mutator
     *
     * @param  string|array  $namespace
     */
    public function addMutator($mutators)
    {
        $namespaces = array_wrap($mutators);

        foreach ($namespaces as $namespace) {
            $this->mutators[] = $namespace;
        }
    }

    /**
     * Returns all available order mutators
     *
     * @return  array
     */
    public function getMutators()
    {
        return $this->cache('orderMutators', function(){
            return array_map(function($item){
                return new $item;
            }, $this->mutators);
        });
    }


    /**
     * Fire all registered mutators and apply them on order
     *
     * @return  this
     */
    public function fireMutators()
    {
        foreach ($this->getActiveMutators() as $mutator) {
            if ( method_exists($mutator, 'mutateOrder') ) {
                $mutator->mutateOrder($this->getOrder(), $mutator->getActiveResponse());
            }
        }

        return $this;
    }

    /**
     * Returns active mutators for given order
     *
     * @return  array
     */
    public function getActiveMutators()
    {
        return array_filter(array_map(function($mutator){
            if ( Admin::isAdmin() ) {
                $response = $mutator->isActiveInAdmin($this->getOrder());
            } else {
                $response = $mutator->isActive($this->getOrder());
            }

            //Apply all discounts on given reponse if is correct type
            //Sometimes mutator may return Discountable admin model.
            //So we need apply discounts to this model
            Cart::addCartDiscountsIntoModel($response);

            //If no response has been given, skip this mutator
            if ( ! $response ) {
                return;
            }

            $mutator->setActiveResponse($response);

            return $mutator;
        }, $this->getMutators()));
    }
}
?>