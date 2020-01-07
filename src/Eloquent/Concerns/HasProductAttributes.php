<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasProductAttributes
{
    public function getAttributesTextAttribute()
    {
        $attributes = [];

        foreach ($this->getValue('attributes') as $attribute) {
            $attributes[] = $attribute->item->getValue('name').$attribute->attribute->unit;
        }

        return implode(', ', $attributes);
    }
}