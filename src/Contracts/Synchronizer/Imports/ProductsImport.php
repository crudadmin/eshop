<?php

namespace AdminEshop\Contracts\Synchronizer\Imports;

use AdminEshop\Contracts\Synchronizer\Synchronizer;
use AdminEshop\Contracts\Synchronizer\SynchronizerInterface;
use Admin;
use Store;

class ProductsImport extends Synchronizer implements SynchronizerInterface
{
    public function getProductIdentifier()
    {
        return 'code';
    }

    public function getVariantsIdentifier()
    {
        return 'code';
    }

    public function handle(array $rows = null)
    {
        $this->synchronize(
            Admin::getModel('Product'),
            $this->getProductIdentifier(),
            $rows
        );

        $this->synchronize(
            Admin::getModel('ProductsVariant'),
            $this->getVariantsIdentifier(),
            $this->getVariants($rows)
        );
    }

    private function getVariants($rows)
    {
        $variants = [];

        $productModel = Admin::getModel('Product');

        foreach ($rows as $product) {
            foreach ($product['$variants'] ?? [] as $variant) {
                $variants[] = $variant + [
                    'product_id' => $this->getExistingRows($productModel)[
                        $product[$this->getProductIdentifier()]
                    ],
                ];
            }
        }

        return $variants;
    }

    public function setProductsVariantVatNumberAttribute($value, &$row)
    {
        $row['vat_id'] = $this->getVatIdByValue($value);
    }

    private function getVatIdByValue($value)
    {
        return $this->cache('vat.'.$value, function() use ($value){
            if ( $vat = Store::getVats()->where('vat', $value)->first() ){
                return $vat->getKey();
            }

            $vat = Admin::getModel('Vat')->create([
                'name' => $value.'%',
                'vat' => $value,
            ]);

            return $vat->getKey();
        });
    }
}