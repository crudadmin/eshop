<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasWarehouse
{
    public function getHasStockAttribute()
    {
        return $this->warehouse_count > 0;
    }

    public function getIsAvailableAttribute()
    {
        return $this->warehouse_quantity > 0 || $this->canOrderEverytime();
    }

    /*
     * Check if order can be ordered with zero quantity on warehouse
     */
    public function canOrderEverytime()
    {
        return $this->warehouse_type == 'everytime';
    }

    /*
     * Check if product can be ordered
     */
    public function getCanOrderAttribute()
    {
        if ( $this->isAvailable )
            return true;

        return false;
    }

    public function getStockTextAttribute()
    {
        if ( $this->hasStock ) {
            return _('Skladom');
        }

        return $this->warehouse_sold ?: _('Nie je skladom');
    }
}