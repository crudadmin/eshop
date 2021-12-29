<?php

namespace AdminEshop\Eloquent\Concerns;

use Illuminate\Support\Facades\DB;

trait HasProductSorter
{
    public function getAvailableSorts()
    {
        return [
            'cheapest' => [
                'name' => _('Cheapest'),
                'scope' => function($query){
                    $query->sortByColumnValue('cheapest', 'products.price', false);
                },
            ],
            'expensive' => [
                'name' => _('Most expensive'),
                'scope' => function($query){
                    $query->sortByColumnValue('expensive', 'products.price', true);
                },
            ],
            'latest' => [
                'name' => _('Latest'),
                'scope' => function($query){
                    $query->sortByColumnValue('latest', 'products.id', true);
                },
            ],
        ];
    }

    public function scopeSortByParams($query)
    {
        $filterParams = $this->getFilterOption('filter');

        if ( !($sortBy = $filterParams['_sort'] ?? null) ){
            return;
        }

        $sorter = $this->getAvailableSorts($query)[$sortBy];

        if ( isset($sorter['scope']) ){
            if ( is_callable($sorter['scope']) ){
                $sorter['scope']($query);
            }
        }
    }

    public function scopeSortByColumnValue($query, string $type, string $agregatedColumn, bool $isDesc = false)
    {
        //Enabled variant extraction
        if ( $this->getFilterOption('variants.extract') === false ) {
            $variantsPrices = DB::table('products')
                                ->selectRaw(($isDesc ? 'max' : 'min').'('.$agregatedColumn.') as aggregator, product_id')
                                ->where('product_type', 'variant')
                                ->groupBy('product_id');

            $query
                ->leftJoinSub($variantsPrices, 'pricedVariants', function($join){
                    $join->on('products.id', '=', 'pricedVariants.product_id');
                })
                ->addSelect(DB::raw('IFNULL(pricedVariants.aggregator, '.$agregatedColumn.') as aggregator'));
        } else {
            $query->addSelect(DB::raw($agregatedColumn.' as aggregator'));
        }

        $query->orderBy('aggregator', $isDesc ? 'DESC' : 'ASC');
    }
}