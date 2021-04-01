<?php

namespace AdminEshop\Admin\Rules;

use AdminEshop\Contracts\Cart\Identifiers\DefaultIdentifier;
use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Store;

class CastAttributeItemValue extends AdminRule
{
    public function creating(AdminModel $row)
    {
        $this->castValue($row);
    }

    public function updating(AdminModel $row)
    {
        $this->castValue($row);
    }

    public function castValue($row)
    {
        if ( ($unit = Store::getUnit(request('unit_id'))) && $unit->isNumericType ){
            $name = str_replace(',', '.', $row->name);

            $row->name = $name;
        }
    }
}