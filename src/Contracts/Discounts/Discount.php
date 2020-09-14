<?php

namespace AdminEshop\Contracts\Discounts;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Concerns\DriverSupport;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Discounts\Discountable;
use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Admin\Core\Contracts\DataStore;
use Admin\Eloquent\AdminModel;
use Cart;
use Discounts;
use Store;

class Discount implements Discountable
{
    use DataStore,
        DriverSupport;

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
     * Discount message
     *
     * @var  string
     */
    public $message = '';

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
        ProductsVariant::class,
        OrdersItem::class,
    ];

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
     * Here will be binded response from isActive
     *
     * @var  mixed
     */
    public $response = null;

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
    public function getMessage($isActiveResponse)
    {
        if ( in_array($this->operator, ['+', '-', '*']) )
            return $this->value.' '.Store::getCurrency();

        if ( in_array($this->operator, ['+%', '-%']) )
            return $this->value.' %';

        return '';
    }

    /**
     * On which models can be applied this discount
     *
     * @return  array
     */
    public function applyOnModels()
    {
        return $this->applyOnModels;
    }

    /**
     * Can be this discount applied on whole order?
     *
     * @return  bool
     */
    public function applyOnWholeCart()
    {
        return false;
    }

    /**
     * If discount can be applied in specific/all product on whole website
     *
     * @param  Admin\Eloquent\AdminModel  $model
     * @return  bool
     */
    public function canApplyOnModel(AdminModel $model)
    {
        return $this->cache('applyOnModels.'.$model->getTable(), function() use ($model) {
            $item = get_class($model);

            $models = array_map(function($item){
                return class_basename($item);
            }, $this->applyOnModels() ?: []);

            return in_array(class_basename($item), $models);
        });
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
        $exceptAcutal = Discounts::getDiscounts([ $this->getKey() ]);

        return $this->getCartItems()->getSummary(null, $exceptAcutal);
    }

    /**
     * Build cart from given order in discounts
     *
     * @param  array  $discounts
     * @return  Collection
     */
    public static function buildCartFromOrderItems($items, $discounts = null)
    {
        $collection = new CartCollection($items->map(function($item) {
            return $item->getCartItem();
        }));

        return $collection->toCartFormat($discounts);
    }

    /**
     * Check if discount is given from final price
     *
     * @return  bool
     */
    public function hasSumPriceOperator()
    {
        return in_array($this->operator, ['-', '+']);
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
     * Set discount message
     *
     * @param  mixed  $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Set isActive response
     *
     * @param  mixed  $message
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * Set isActive response
     *
     * @param  mixed  $message
     */
    public function getResponse()
    {
        return $this->response;
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
            'message',
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

                $value = $this->{$key};
            }

            $data[$key] = $value;
        }

        return $data;
    }
}

?>