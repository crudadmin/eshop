<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use DB;
use Store;

trait HasPriceLevels
{
    public function scopeWithPriceLevels($query, $key = 'pl')
    {
        if ( !config('admin_eshop.prices.price_levels') ){
            return;
        }

        $table = 'products_prices as '.$key;

        //Check if join is registered already.
        $joins = ($query instanceof \Illuminate\Database\Query\Builder ? $query : $query->getQuery())->joins ?: [];

        if ( collect($joins)->firstWhere('table', $table) ){
            return;
        }

        $query->leftJoin($table, function($join) use ($key) {
            $join
                ->on($key.'.'.Admin::getModel('ProductsPrice')->getForeignColumn('products'), '=', $this->qualifyColumn('id'))
                ->where($key.'.currency_id', Store::getCurrency()->getKey())
                ->whereNotNull($key.'.published_at')
                ->whereNull($key.'.deleted_at');

            $this->priceLevelsJoin($join);
        });
    }

    public function priceLevelsJoin($join)
    {

    }

    public function scopeWithPriceLevelsColumns($query)
    {
        if ( !config('admin_eshop.prices.price_levels') ) {
            return;
        }

        $priceLevelsOnly = $this->getFilterOption('price_levels_only', false);

        $query
            ->withPriceLevels()
            ->addSelect(DB::raw('
                COALESCE(pl.price, '.($priceLevelsOnly ? 0 : $this->qualifyColumn('price')).') as price,
                COALESCE(pl.vat_id, '.($priceLevelsOnly ? 0 : $this->qualifyColumn('vat_id')).') as vat_id,
                pl.currency_id
            '));
    }
}