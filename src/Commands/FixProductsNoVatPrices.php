<?php

namespace AdminEshop\Commands;

use Illuminate\Console\Command;
use Admin;
use Store;

class FixProductsNoVatPrices extends Command
{
    protected $signature = 'eshop:fix-products-no-vat-prices';

    protected $description = 'Fix no vat prices for products';

    public function handle()
    {
        if ( !$this->confirm('Are you sure you want to fix no vat prices for products?')) {
            return;
        }

        $products = Admin::getModel('Product')
                        ->withUnpublished()
                        ->with(['vat'])
                        ->get();

        $this->line('Found '.count($products).' products to fix.');

        foreach ($products as $product) {
            $vat = $product->vat?->vat ?: 0;
            $priceWithVat = $product->calculateVatPrice($product->price, $vat);
            $fixedPriceWithoutVat = Store::removeVat($priceWithVat, $vat, false);

            $product->update([
                'price' => $fixedPriceWithoutVat,
            ]);

            $this->line('Product id: '.$product->id.' fixed.');
        }

        $this->line('Products no vat prices fixed.');
    }
}