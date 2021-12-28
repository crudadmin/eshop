<?php

namespace AdminEshop\Contracts\Order\Concerns;

trait HasOrderItemNames
{
    /**
     * Returns all product name information in array
     *
     * @return  array
     */
    public function getProductNameParts() : array
    {
        if ( !($identifier = $this->getIdentifierClass()) ){
            return [];
        }

        return $identifier->getProductNameParts($this);
    }

    /**
     * Returns array with two keys
     * First key will be used as head product name, second key has all additional product informations
     *
     * @return  mixed
     */
    public function getProductNamePartsSections($part = null)
    {
        $parts = $this->getProductNameParts();

        $items = [
            @$parts[0],
            $this->joinMultiple(array_slice($parts, 1) ?: [])
        ];

        //Return specific key
        if ( is_null($part) === false ){
            return @$items[$part];
        }

        return $items;
    }

    private function joinMultiple($items)
    {
        return implode(' - ', array_filter(array_wrap($items)));
    }

    /**
     * Returns product name with all additional informations divided with string separator
     *
     * @return  string
     */
    public function getProductName()
    {
        $items = $this->getProductNameParts();

        $name = '';

        if ( $this->order_item_id ){
            $name .= '<i class="fa fa-level-up-alt" style="transform: scaleX(-1); margin-left: 10px; margin-right: 10px"></i>';
        }

        $name .= e($this->joinMultiple($items));

        return $name;
    }

    public function emailItemName()
    {
        $name = e($this->getProductNamePartsSections(0));

        if ( $additional = $this->getProductNamePartsSections(1) ) {
            $name .= ' <small>'.e($additional).'</small>';
        }

        return $name;
    }

    public function invoiceItemName()
    {
        $name = e($this->getProductNamePartsSections(0));

        if ( $additional = $this->getProductNamePartsSections(1) ) {
            $name .= ' - '.e($additional);
        }

        return $name;
    }
}