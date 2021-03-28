<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasProductFilter
{
    public function scopeFilterCategory($query, $category)
    {
        $query->whereHas('categories', function($query) use ($category) {
            $query->where('categories.id', $category->getKey());
        });
    }
}