<?php

namespace AdminEshop\Contracts;

use AdminEshop\Contracts\Discounts\DiscountCode;
use AdminEshop\Contracts\Discounts\FreeDelivery;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Core\Contracts\DataStore;
use Admin\Eloquent\AdminModel;
use Store;

class Discounts
{
    use DataStore;

    /**
     * @see config admineshop.php in admineshop.discounts.classes
     *
     * All registered discounts applied on cart items and whole store...
     */
    private $discounts = [];

    /*
     * Which model attributes are discountable
     * 'myOtherParam' => true/false
     * (true = withVat / false = withoutVat / auto = 'by client type')
     */
    private $discountableAttributes = [
        'clientPrice' => 'auto',
    ];

    /**
     * Set order where discounts will be applied
     *
     * @var  Admin\Eloquent\AdminModel|null
     */
    private $order;

    /**
     * Discounts caches for fixing recursive loops
     *
     * @var  bool
     */
    private $discountsCache = [];

    /**
     * Which discounts are in boot state
     *
     * @var  array
     */
    private $bootingDiscounts = [];

    /**
     * Consturct Discount class
     */
    public function __construct()
    {
        //Register discount classes from config
        foreach (config('admineshop.discounts.classes', []) as $discountClass) {
            if ( class_exists($discountClass) ) {
                $this->discounts[] = $discountClass;
            }
        }
    }

    /**
     * Add discount class
     *
     * @param  string|array  $discountClass
     */
    public function addDiscount($discountClass)
    {
        foreach (array_wrap($discountClass) as $class) {
            $this->discounts[] = $class;
        }
    }

    /**
     * Check if given discount has been registred
     *
     * @param  string  $discountClass
     * @return  bool
     */
    public function isRegistredDiscount($discountClass)
    {
        return in_array($discountClass, $this->discounts);
    }

    /**
     * Set order where, from which will be loaded items to discounts
     *
     * @param  AdminModel  $order
     * @return  this
     */
    public function setOrder(AdminModel $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Returns order of discounts
     *
     * @return  Admin\Eloquent\AdminModel|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Get all registered discounts in cart
     *
     * @param  array|string  $exceps
     * @return array
     */
    public function getDiscounts($exceps = [])
    {
        $exceps = array_merge(array_wrap($exceps), $this->bootingDiscounts);

        $discounts = $this->cache('store_discounts', function(){
            return array_map(function($className){
                return new $className;
            }, $this->discounts);
        });

        //Returns only active discounts
        $discounts = array_values(array_filter($discounts, function($discount) use ($exceps) {
            if ( in_array($discount->getKey(), $exceps) ) {
                return false;
            }

            //Set order into every discount
            if ( $order = $this->getOrder() ) {
                $discount->setOrder($order);
            }

            return $this->cache('discounts.'.$discount->getCacheKey(), function() use ($discount) {
                //This discount is now under "boot" state. We need save this state
                //because in isActive method may be needed cartSummary. In this case
                //summary with all discounts except booting one will be retrieved. But if other discount
                //also requires cart summary, where will be infinity loop. So in this case we need
                //skip actual booting discount from all other discounts which requires cart summary.
                $this->bootingDiscounts[] = $discount->getKey();

                //If is in except mode
                if ( !($response = $this->getActiveDiscountState($discount)) ) {
                    $this->removeDiscountFromBootState($discount);

                    return false;
                }

                //Set is active response
                $discount->setActiveResponse($response);
                $discount->boot($response);
                $discount->setMessage($discount->getMessage($response));

                $this->removeDiscountFromBootState($discount);

                return true;
            });
        }));

        //Set correct order index of applied discount
        foreach ($discounts as $key => $discount) {
            $discount->setOrderIndex($key);
        }

        return $discounts;
    }

    public function get(string $discountClass)
    {
        return $this->cache('discount_class.'.$discountClass, function() use ($discountClass) {
            return @array_filter($this->getDiscounts(), function($class) use ($discountClass) {
                return class_basename(get_class($class)) == $discountClass;
            })[0];
        });
    }

    private function removeDiscountFromBootState($discount)
    {
        //Remove discounts from boot state
        if ( !in_array($discount->getKey(), $this->bootingDiscounts) ) {
            return;
        }

        unset($this->bootingDiscounts[
            array_search($discount->getKey(), $this->bootingDiscounts)
        ]);
    }

    private function getActiveDiscountState($discount)
    {
        //If is not active in backend/administration
        if ( $order = $this->getOrder() ) {
            //Get order discounts data
            $orderDiscountData = $order->discount_data ?: [];

            //Id discount is set as cachable, we need retrieve all discount data and set this data as actual discount state
            if ( $discount->isCachableResponse() === true ) {
                //If response is negative, we can cache discount
                //Or If discount data are not present in saved order
                if (
                    !array_key_exists($discount->getKey(), $orderDiscountData)
                    || !(
                        $response = $discount->unserializeResponse(
                            $orderDiscountData[$discount->getKey()]
                        )
                    )
                ) {
                    return $this->cacheDiscountState($discount, false);
                }
            }

            //If discount is dynamical also in administration, we can check for actual discount state
            else if ( !($response = $discount->isActiveInAdmin($order)) ) {
                return $this->cacheDiscountState($discount, false);
            }
        }

        //If is not active in frontend
        else if ( !($response = $discount->isActive()) ) {
            return $this->cacheDiscountState($discount, false);
        }

        return $this->cacheDiscountState($discount, $response);
    }

    public function cacheDiscountState($discount, $state)
    {
        $this->discountsCache[$discount->getKey()] = $state;

        return $state;
    }

    /**
     * Return all discounts except given
     *
     * @param  array|string  $exceps
     * @return array
     */
    public function exceptDiscounts($exceps = [])
    {
        return $this->getDiscounts($exceps);
    }


    /**
     * Register all discounts into product
     *
     * @param  object  $item
     * @param  array  $discounts
     * @param  callable  $rule
     *
     * @return object
     */
    public function applyDiscountsOnModel($item, array $discounts = null, callable $canApplyDiscount)
    {
        $discounts = $discounts !== null ? $discounts : $this->getDiscounts();

        foreach ($discounts as $discount) {

            //If discount is allowed for cart items
            if ( $discount->canApplyOnModel($item) && $canApplyDiscount($discount, $item) ) {
                $item->addDiscount($discount);
            }
        }

        return $item;
    }

    /**
     * Add discountable attribute
     *
     * @param  string|array  $key
     */
    public function addDiscountableAttributes($attributes)
    {
        $this->discountableAttributes = array_merge(
            $this->discountableAttributes,
            $attributes
        );

        return $this;
    }

    /**
     * Return discountable attributes
     *
     * @return  array
     */
    public function getDiscountableAttributes()
    {
        return $this->discountableAttributes;
    }

    /**
     * Returns value of vat for given parameter
     *
     * @return  bool|null
     */
    public function getDiscountableAttributeVatValue($key)
    {
        $discountableAttributes = $this->getDiscountableAttributes();

        //If is not discountable attribute by withVat/WithouVat
        //try other dynamic fields from discounts settings
        if ( array_key_exists($key, $discountableAttributes) ) {
            $vatValue = $discountableAttributes[$key];

            return $vatValue == 'auto' ? !Store::hasB2B() : $vatValue;
        }
    }

    /**
     * Check if given item has discountable trait
     *
     * @param   mixed  $item
     * @return  bool
     */
    public function hasDiscountableTrait($item)
    {
        return is_object($item) && in_array(PriceMutator::class, class_uses_recursive($item));
    }
}

?>