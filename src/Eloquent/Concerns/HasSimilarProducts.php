<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasSimilarProducts
{
    protected $perPageSimilarProducts = 8;

    public function paginateSimilarProducts($query)
    {
        if ( $this->perPageSimilarProducts > 0 ) {
            $listing = $query->productsPaginate($this->perPageSimilarProducts);

            return $listing->getCollection()->each->setSimilarProductResponse();
        } else {
            return $query->get()->each->setSimilarProductResponse();
        }
    }

    public function getSimilarProducts()
    {
        if ( !($lastCategory = collect($this->getCategoriesTree()[0] ?? [])->last()) )   {
            return [];
        }

        $similarProducts = $this->newInstance()->withListingResponse([
            'filter' => [
                '_categories' => [$lastCategory->getKey()],
            ]
        ])->where('products.id', '!=', $this->getKey());

        return $this->paginateSimilarProducts($similarProducts);
    }

    public function setSimilarProductResponse()
    {
        return $this->setListingResponse();
    }
}

?>