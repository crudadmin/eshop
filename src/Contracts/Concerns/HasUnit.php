<?php

namespace AdminEshop\Contracts\Concerns;

use Store;

trait HasUnit
{
    public function getUnitNameAttribute()
    {
        $unitId = $this->getAttribute('unit_id');

        if ( $unitId && $unit = Store::getUnit($unitId) ) {
            return $unit->getAttribute('unit');
        }
    }

    public function getUnitFormatAttribute()
    {
        $unitId = $this->getAttribute('unit_id');

        if ( $unitId && $unit = Store::getUnit($unitId) ) {
            return $unit->getAttribute('format');
        }
    }
}