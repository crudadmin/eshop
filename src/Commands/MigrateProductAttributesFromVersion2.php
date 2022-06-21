<?php

namespace AdminEshop\Commands;

use AdminEshop\Contracts\Delivery\DeliveryLocationsImporter;
use AdminEshop\Jobs\CleanEmptyCartTokensJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateProductAttributesFromVersion2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eshop:migrate-old-attributes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate old attributes from crudeshop version 2.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Migrating...');

        // $productAttributes = DB::table('products_attributes')->selectRaw('id, product_id, attribute_id')->whereNull('deleted_at')->get();
        $attributeItemsMap = DB::table('attributes_item_products_attribute_items')
                                ->selectRaw('products_attribute_id, attributes_item_id, products_attributes.product_id, products_attributes.attribute_id, products_attributes.deleted_at')
                                ->leftJoin('products_attributes', function($join){
                                    $join->on('products_attributes.id', '=', 'attributes_item_products_attribute_items.products_attribute_id');
                                })
                                ->havingRaw('products_attributes.deleted_at is NULL AND products_attributes.product_id is NOT NULL')
                                ->get();

        foreach ($attributeItemsMap as $row) {
            DB::table('attributes_item_product_attributes_items')->insert([
                'product_id' => $row->product_id,
                'attributes_item_id' => $row->attributes_item_id,
            ]);
        }

        $this->info('End.');
    }
}
