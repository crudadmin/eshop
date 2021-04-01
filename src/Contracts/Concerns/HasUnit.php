<?php

namespace AdminEshop\Contracts\Concerns;

use Store;

trait HasUnit
{
    public function getUnitNameAttribute()
    {
        if ( $this->unit_id && $unit = Store::getUnit($this->unit_id) ) {
            return $unit->unit;
        }
    }
}