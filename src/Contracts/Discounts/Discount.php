<?php

namespace AdminEshop\Contracts\Discounts;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Concerns\ActiveInterface;
use AdminEshop\Contracts\Cart\Concerns\ActiveResponse;
use AdminEshop\Contracts\Cart\Concerns\DriverSupport;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Discounts\Discountable;
use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Products\Product;
use Admin\Core\Contracts\DataStore;
use Admin\Eloquent\AdminModel;
use Cart;
use Discounts;
use Store;

class Discount implements Discountable, ActiveInterface
{
    use DataStore,
        DriverSupport,
        ActiveResponse;

    /**
     * Discount operator for managing price values
     * @see  +%, -%, +, -, *
     *
     * @var  null
     */
    public $operator = null;

    /**
     * Discount value
     *
     * @var  float|int|callable
     */
    public $value = null;

    /**
     * Apply multiple operators and multiple discounts
     *
     * @var  array [ ['operator' => '%', 'value' => 50], ... ]
     */
    public $operators = [];

    /**
     * Discount messages
     *
     * @var  string
     */
    public $messages = [];

    /**
     * Order will be applied in administration for order items prices calculations
     *
     * @var  AdminEshop\Models\Orders\Order|null
     */
    public $order;

    /**
     * Discount can be applied on those models
     *
     * @var  array
     */
    public $applyOnModels = [
        Product::class,
        OrdersItem::class,
    ];

    /**
     * Can be discount applied on whole cart price?
     *
     * @var  bool
     */
    public $applyOnWholeCart = false;

    /**
     * Can apply discount on items on whole ecommerce including cart
     *
     * @var  bool
     */
    public $canApplyOutsideCart = true;

    /**
     * Can apply discount on products in cart
     *
     * @var  bool
     */
    public $canApplyInCart = true;

    /**
     * Do we want store all discount data in database into given order?
     * All discount parameters should be then loaded from time of creating a order.
     * This prevents that discounts may change after order editing
     *
     * @var  bool
     */
    public $cachableResponse = true;

    /**
     * Order of given discount. This value will be binded automatically given by admineshop configruation
     *
     * @var  int
     */
    private $orderIndex = null;

    /**
     * Discount key
     * Can be usefull if you need rewrite core discount
     *
     * @return string
     */
    public function getKey()
    {
        return class_basename(get_class($this));
    }

    /**
     * Returns cache key for given discount
     *
     * @return  string
     */
    public function getCacheKey()
    {
        return $this->getKey();
    }

    /**
     * Returns discount name
     *
     * @return  string
     */
    public function getName()
    {
        return 'Your discount name';
    }

    /**
     * Returns if discount is active on website
     *
     * @return  bool
     */
    public function isActive()
    {
        return false;
    }

    /**
     * Returns if discount is active in administration
     *
     * @return  bool
     */
    public function isActiveInAdmin(Order $order)
    {
        return false;
    }

    /**
     * Should be discounts data cached in database with order?
     *
     * @return  bool
     */
    public function isCachableResponse()
    {
        return $this->cachableResponse;
    }

    /**
     * One discount can register muliple price reductions
     *
     * @return  array
     */
    public function getAllOperators()
    {
        $operators = [];

        if ( $this->operator && !is_null($this->value) ){
            $operators[] = [
                'operator' => $this->operator,
                'value' => $this->value,
                'applyOnModels' => $this->discountedModelsToBasename($this->applyOnModels),
                'applyOnWholeCart' => $this->applyOnWholeCart,
            ];
        }

        foreach ($this->operators as $data) {
            $applyOnModels = $data['applyOnModels'] ?? null;
            $applyOnModels = $applyOnModels === true || is_null($applyOnModels) ? $this->applyOnModels : $applyOnModels;

            $operators[] = array_merge($data, [
                'operator' => $data['operator'],
                'value' => $data['value'],
                'applyOnModels' => $this->discountedModelsToBasename($applyOnModels),
                'applyOnWholeCart' => $data['applyOnWholeCart'] ?? false,
            ]);
        }

        return $operators;
    }

    private function discountedModelsToBasename($models)
    {
        if ( is_array($models) ){
            foreach ($models as $key => $model) {
                $models[$key] = class_basename($model);
            }
        }

        return $models;
    }

    /**
     * Boot discount parameters after isActive check
     *
     * @param  mixed  $isActiveResponse
     * @return void
     */
    public function boot($isActiveResponse)
    {
        //set your values...
    }

    /**
     * When order is before creation status, you can modify order data
     * before creation from your discount.
     *
     * @param  array  $row
     * @return  array
     */
    public function mutateOrderRow(Order $order, CartCollection $items)
    {
        //$order->somethingId = 5;
    }

    /**
     * Return discount message
     *
     * @param  mixed  $isActiveResponse
     * @return void
     */
    private function getBaseDiscountMessage($isActiveResponse)
    {
        if ( in_array($this->operator, ['+', '-', '*']) ) {
            return $this->value.' '.Store::getCurrencyCode();
        }

        if ( in_array($this->operator, ['+%', '-%']) ) {
            return $this->value.' %';
        }

        return '';
    }

    public function getMessages($isActiveResponse)
    {
        return [
            [
                'name' => $this->getName(),
                'value' => $this->getBaseDiscountMessage($isActiveResponse)
            ],
        ];
    }

    /**
     * If discount can be applied in specific/all product in cart
     *
     * @param  Admin\Eloquent\AdminModel  $item
     * @return  bool
     */
    public function canApplyInCart($item)
    {
        return $this->canApplyInCart;
    }

    /**
     * If discount can be applied in specific/all product in cart
     *
     * @param  Admin\Eloquent\AdminModel  $item
     * @return  bool
     */
    public function canApplyOutsideCart($item)
    {
        return $this->canApplyOutsideCart;
    }

    /**
     * Return all cart items without actual discount
     * If actual discount would be applied, intifity loop will throw and error
     *
     * @return  CartCollection
     */
    public function getCartItems() : CartCollection
    {
        $exceptAcutal = Discounts::getDiscounts([ $this->getKey() ]);

        if ( $order = Discounts::getOrder() ) {
            return self::buildCartFromOrderItems($order->items, $exceptAcutal);
        }

        return Cart::all($exceptAcutal);
    }

    /**
     * Returns given cart items prices summary
     *
     * @return  array
     */
    public function getCartSummary()
    {
        return $this->cache('summary.'.static::class, function(){
            $exceptAcutal = Discounts::getDiscounts([ $this->getKey() ]);

            return $this->getCartItems()->getSummary(false, $exceptAcutal, true);
        });
    }

    /**
     * Build cart from given order in discounts
     *
     * @param  array  $discounts
     * @return  Collection
     */
    public static function buildCartFromOrderItems($items, $discounts = null)
    {
        $collection = new CartCollection($items);

        return $collection->toCartFormat($discounts);
    }

    /**
     * Check if discount is given from final price
     *
     * @return  bool
     */
    public function hasSumPriceOperator($operator)
    {
        return in_array($operator, ['-', '+']);
    }

    /**
     * Set order
     *
     * @param  mixed  $message
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * Get admin order
     *
     * @return  AdminEshop\Models\Orders\Order|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Returns client of order
     *
     * @return  AdminEshop\Models\Clients\Client|null
     */
    public function getClient()
    {
        if ( $order = $this->getOrder() ){
            return $order->client;
        }

        return client();
    }

    /**
     * Set discount messages
     *
     * @param  mixed  $messages
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    /**
     * Set order index
     *
     * @param  this  $index
     */
    public function setOrderIndex(int $orderIndex)
    {
        $this->orderIndex = $orderIndex;

        return $this;
    }

    public function getOrderIndex()
    {
        return $this->orderIndex;
    }

    /**
     * Which field will be visible in the cart request
     *
     * @return  array
     */
    public function getVisible()
    {
        return [
            'key' => 'getKey',
            'name' => 'getName',
            'messages',
            'operator',
            'value',
        ];
    }

    /**
     * Can be this discount shown in email?
     *
     * @return  bool
     */
    public function canShowInEmail()
    {
        return true;
    }

    /**
     * Convert to array
     *
     * @return  array
     */
    public function toArray()
    {
        $data = [];

        foreach ($this->getVisible() as $key => $method) {
            if ( is_string($key) && is_string($method) ) {
                $value = method_exists($this, $method) ? $this->{$method}() : $this->{$key};
            } else {
                $key = $method;

                $value = $this->{$key} ?? null;
            }

            $data[$key] = $value;
        }

        return $data;
    }
}

?>