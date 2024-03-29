<?php

namespace AdminEshop\Contracts\Cart\Identifiers;

use Admin;
use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Identifiers\Concerns\UsesIdentifier;
use AdminEshop\Contracts\Cart\Identifiers\Identifier;
use AdminEshop\Models\Orders\OrdersItem;
use Store;

class ProductsIdentifier extends Identifier
{
    /*
     * Retuns name of identifier
     */
    public function getName()
    {
        return 'products';
    }

    /**
     * Can discounts be applied on item with this identifier?
     *
     * @return  bool
     */
    public function hasDiscounts()
    {
        return true;
    }

    /**
     * Temporary stock block
     *
     * @return  bool
     */
    public function hasTemporaryStockBlock()
    {
        return Cart::isStockBlockEnabled();
    }

    /**
     * Keys in array are assigned to eloquents tables
     *
     * @return  array
     */
    public static function getIdentifyKeys()
    {
        return [
            'id' => [
                'table' => 'products',
                'modelKey' => 'product',
                'scope' => function($query){
                    return $query->withCartResponse();
                },
                'orders_items_column' => 'product_id',
            ],
            'variant_id' => [
                'table' => 'products',
                'modelKey' => 'variant',
                'scope' => function($query){
                    return $query->withCartResponse(true);
                },
                'orders_items_column' => 'product_id',
            ],
        ];
    }

    public function cloneFromItem(object $item)
    {
        $identifier = parent::cloneFromItem($item);

        //We need reset variant_id, if is same id.
        if ( $identifier->getIdentifier('id') == $identifier->getIdentifier('variant_id') ){
            $identifier->setIdentifier('variant_id', null);
        }

        return $this;
    }

    /**
     * Boot identifier from request data
     *
     * @param  array  $request
     *
     * @return  this
     */
    public function bootFromRequestData(array $request = [])
    {
        return $this->bindInKeysOrder(
            $this->getValidProductIdFromRequest($request),
            $this->getValidVariantIdFromRequest($request)
        );
    }

    /**
     * Get model by given cart type
     * If this method returns false instead of null
     * item without model will be valid and
     * wont be automatically removed from cart.
     *
     * @param  CartItem  $item
     * @return  Admin\Eloquent\AdminModel|null
     */
    public function getItemModel($item, $cache)
    {
        $product = $cache['product'] ?? null;

        if ( $item->variant_id ) {
            return ($variant = ($cache['variant'] ?? null)) && $product ? $variant : null;
        }

        if ( $item->id ) {
            return $product;
        }
    }

    /**
     * Returns if product in cart item is on stock
     * (we need ask originalItem model, and not cached one, because quantity may change.
     *  so we does not want cached eloquent.)
     *
     * @return  bool
     */
    public function hasQuantityOnStock($item)
    {
        //TODO: this rule is probably uneccessary if variant is ordered.
        //May be worth of refactoring
        if ( ! ($model = $item->getOriginalItemModel('product')) ) {
            return true;
        }

        //We can skip checking products which all allowed all the time.
        //This rule will check only parent Product model.
        if ( $model->canOrderEverytime ) {
            return true;
        }

        //Check final assigned item, for example ProductVariant.
        $model = $item->getOriginalItemModel();

        //Check orderable status
        if ( $model && $model->canOrderEverytime ){
            return true;
        }

        //Check if quantity in cart is lower that quantity on stock
        return $item->quantity <= $model->stock_quantity;
    }

    /**
     * Modify item on cart items render into website
     *
     * @param  CartItem  $item
     * @return  void
     */
    public function onRender(CartItem $item)
    {
        $item->hasQuantityOnStock = $this->hasQuantityOnStock($item);
    }

    /**
     * Returns all product name information in array
     *
     * @return  array
     */
    public function getProductNameParts(UsesIdentifier $item) : array
    {
        if ( $item instanceof OrdersItem ) {
            if ( $item->product?->isType('variant') ){
                $variant = $item->product;
                $product = $item->product->product;
            } else {
                $product = $item->product;
                $variant = null;
            }
        } else {
            $product = $item->getValue('product');
            $variant = $item->getValue('variant');
        }

        if ( !($productOrVariant = $variant ?: $product) ){
            return [];
        }

        $items = [
            $variant ? ($variant->name ?: $product?->name) : $product?->name
        ];

        //If text attributes are not loaded
        if ( config('admineshop.attributes.attributesVariants', false) || config('admineshop.attributes.attributesText', false) ) {
            if ( $productOrVariant->relationLoaded('attributesItems') == false ){
                $productOrVariant->load(['attributesItems' => function($query){
                    $query->withTextAttributes();
                }]);
            }
        }

        if ( config('admineshop.attributes.attributesVariants', false) == true ) {
            $this->loadAttributes($productOrVariant);

            $items[] = $productOrVariant->attributesVariantsText;
        } else if ( config('admineshop.attributes.attributesText', false) == true ) {
            $this->loadAttributes($productOrVariant);

            $items[] = $productOrVariant->attributesText;
        }

        return array_filter($items);
    }

    private function loadAttributes($product)
    {
        if ( $product && $product->hasAttributesEnabled() && $product->relationLoaded('attributesItems') == false ){
            $product->load('attributesItems');
        }
    }

    /*
     * Verify if row exists in db and return row key
     */
    private function getValidProductIdFromRequest(array $request)
    {
        if ( !($id = $request['product_id'] ?? $request['id']) ){
            return;
        }

        return Store::cache('cart.product_id.'.$id, function() use ($request, $id) {
            return Admin::getModelByTable('products')
                        ->select(['id'])
                        ->where('id', $id)
                        ->firstOrFail()
                        ->getKey();
        });
    }

    /*
     * Verify if variant exists in db and returns key
     */
    private function getValidVariantIdFromRequest(array $request)
    {
        if ( ! ($request['variant_id'] ?? null) ) {
            return;
        }

        return Admin::cache('cart.variant_id.'.$request['variant_id'], function() use ($request) {
            return Admin::getModelByTable('products')
                        ->select(['id'])
                        ->where('id', $request['variant_id'])
                        ->where('product_id', $this->getValidProductIdFromRequest($request))
                        ->firstOrFail()
                        ->getKey();
        });
    }

    /**
     * Build product cart order item
     *
     * @param  CartItem  $item
     * @return  array
     */
    public function onOrderItemCreate(CartItem $item)
    {
        $data = parent::onOrderItemCreate($item);

        //rewrite and Assign correct product or variant
        $data['product_id'] = ($item->variant_id ?? null) ?: ($item->product_id ?? null) ?: ($item->id ?? null);

        return $data;
    }
}

?>