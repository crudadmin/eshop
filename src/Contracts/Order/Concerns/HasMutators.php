<?php

namespace AdminEshop\Contracts\Order\Concerns;

use Admin;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Order\Mutators\ClientDataMutator;
use AdminEshop\Contracts\Order\Mutators\CountryMutator;
use AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
use AdminEshop\Contracts\Order\Mutators\PaymentMethodMutator;
use Cart;

trait HasMutators
{
    /**
     * Available order mutators
     *
     * If null is present, config mutators will be loaded
     *
     * @var  array|null
     */
    protected $mutators;

    /**
     * Which motators are currently booting up
     *
     * @var  array
     */
    protected $bootingMutators = [];

    /**
     * Set order available mutators
     *
     * @param  string|array  $namespace
     */
    public function setMutators($mutators = null)
    {
        $this->mutators = $mutators;

        return $this;
    }

    public function getConfigMutators()
    {
        return $this->cache('config.mutators', function(){
            $mutators = config('admineshop.cart.mutators');

            //We need obtain cartSteps without firing Cart.
            $cartSteps = \AdminEshop\Contracts\Cart::getCartSteps();
            $stepMutators = $cartSteps->map(function($step){
                return array_merge($step['mutators'] ?? [], $step['validators'] ?? []);
            })->flatten()->unique()->toArray();

            return array_values(array_unique(array_merge($mutators, $stepMutators)));
        });
    }

    /**
     * Returns all available order mutators
     *
     * @return  array
     */
    public function getMutators(CartCollection $cartItems = null)
    {
        $mutators = is_array($this->mutators) ? $this->mutators : $this->getConfigMutators();

        $mutators = $this->cache('orderMutators.'.implode(';', $mutators), function() use ($mutators) {
            return array_map(function($item) {
                return new $item;
            }, $mutators);
        });

        return array_map(function($mutator) use ($cartItems) {
            return $mutator->bootMutator($cartItems);
        }, $mutators);
    }

    public function getCachedMutators()
    {
        return $this->cache('mutators.list', function(){
            return $this->getMutators();
        });
    }

    public function hasMutator($mutator)
    {
        $mutators = is_array($this->mutators) ? $this->mutators : $this->getConfigMutators();
        $mutators = array_map(function($mutator){
            return class_basename(is_object($mutator) ? $mutator::class : $mutator);
        }, $mutators);

        $mutator = class_basename($mutator);

        return in_array($mutator, $mutators);
    }

    /**
     * Returns active mutators for given order
     *
     * @return  array
     */
    public function getActiveMutators(CartCollection $cartItems = null)
    {
        $cacheKey = $cartItems ? $cartItems->getCartKey() : 'default';

        return $this->cache('active.mutators.'.$cacheKey, function() use ($cartItems, $cacheKey) {
            $mutators = $this->getMutators($cartItems);

            return array_filter(array_map(function($mutator) {
                $className = $mutator::class;

                //This mutator is already booting. This would cause infinity loop.
                if ( isset($this->bootingMutators[$className]) ){
                    return;
                }

                $this->bootingMutators[$className] = true;

                if ( Admin::isAdmin() ) {
                    $response = $mutator->isActiveInAdmin($this->getOrder());
                } else {
                    $response = $mutator->isActive($this->getOrder());
                }

                unset($this->bootingMutators[$className]);

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
            }, $mutators));
        });
    }

    public function getMutatedResponses($response, $fullCartResponse, $mutators = null)
    {
        $mutators = is_null($mutators) ? $this->getMutators() : $mutators;

        //Mutate cart response
        foreach ($mutators as $mutator) {
            if ( is_string($mutator) ){
                $mutator = (new $mutator)->bootMutator();
            }

            $hasForceFullCartResponse = in_array(get_class($mutator), $mutator::$forceCartResponse);

            $methods = array_filter([
                'mutateCartResponse',
                $fullCartResponse == true || $hasForceFullCartResponse ? 'mutateFullCartResponse' : null
            ]);

            foreach ($methods as $method) {
                //Mutate basic response
                if ( method_exists($mutator, $method) ) {
                    $response = $mutator->{$method}($response);
                }
            }
        }

        return $response;
    }
}
?>