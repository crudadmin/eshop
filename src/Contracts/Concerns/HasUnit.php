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

    public function getHasUnitSpaceAttribute()
    {
        $unitId = $this->getAttribute('unit_id');

        if ( $unitId && $unit = Store::getUnit($unitId) ) {
            return $unit->getAttribute('space');
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