<?php

namespace AdminEshop\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductsWithAttributesResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->collection->each->setCategoryResponse();

        return parent::toArray($request);
    }
}
