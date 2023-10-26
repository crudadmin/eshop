<?php

namespace AdminEshop\Contracts\Cart\Concerns;

trait HasCartWeight
{
    public function getItemsWeight($items = null, $toUnit = 'kilograms')
    {
        $items = $items ?: $this->all();

        //Calculate weight by cart items
        $cartItemsWithWeight = $items->filter(function($item){
            return is_null($item->getItemModel()?->weight) == false;
        });

        if ( $cartItemsWithWeight->count() ){
            $calculatedWeight = $cartItemsWithWeight->map(function($item){
                return ($item->getItemModel()?->weight ?: 0) * $item->quantity;
            })->sum();

            if ( $toUnit === false ) {
                return $calculatedWeight;
            } else {
                return $this->toWeightUnit($calculatedWeight, $toUnit);
            }
        }
    }

    public function toWeightUnit($weight, $toUnit = 'kilograms')
    {
        if ( !is_numeric($weight) ){
            return;
        }

        if ( $toUnit == 'g' ){
            $toUnit = 'grams';
        } else if ( $toUnit == 'kg' ){
            $toUnit = 'kilograms';
        }

        $inUnit = config('admineshop.product.weight_unit');

        if ( $inUnit == $toUnit ){
            return $weight;
        } else if ( $inUnit == 'grams' && $toUnit == 'kilograms'  ){
            //Round easy weights
            if ( $weight < 100 ){
                $weight = ceil($weight / 100) * 100;
            }

            return round($weight / 1000, 1);
        } else if ( $inUnit == 'kilograms' && $toUnit == 'grams'  ){
            return round($weight * 1000);
        }

        return $weight;
    }
}

?>