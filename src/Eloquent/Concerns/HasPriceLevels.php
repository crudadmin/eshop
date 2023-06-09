<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use DB;
use Store;

trait HasPriceLevels
{
    public function scopeWithPriceLevels($query)
    {
        if ( !config('admineshop.prices.price_levels') ){
            return;
        }

        $table = 'products_prices as pl';

        //Check if join is registered already.
        $joins = ($query instanceof \Illuminate\Database\Query\Builder ? $query : $query->getQuery())->joins ?: [];

        if ( collect($joins)->firstWhere('table', $table) ){
            return;
        }

        $query->leftJoin($table, function($join) {
            $join
                ->on('pl.'.Admin::getModel('ProductsPrice')->getForeignColumn($this->getTable()), '=', $this->qualifyColumn('id'))
                ->where('pl.currency_id', Store::getCurrency()->getKey())
                ->whereNotNull('pl.published_at')
                ->whereNull('pl.deleted_at');
        });
    }

    public function scopeWithPriceLevelsColumns($query)
    {
        if ( config('admineshop.prices.price_levels') ) {
            $query
                ->withPriceLevels()
                ->addSelect(DB::raw('
                    COALESCE(pl.price, '.$this->qualifyColumn('price').') as price,
                    COALESCE(pl.vat_id, '.$this->qualifyColumn('vat_id').') as vat_id,
                    pl.currency_id
                '));
        }
    }
}