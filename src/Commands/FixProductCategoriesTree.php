<?php

namespace AdminEshop\Commands;

use Illuminate\Console\Command;
use Admin;

class FixProductCategoriesTree extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eshop:product-categories-levels-fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all missing categories from given tree';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $products = Admin::getModel('Product')
                        ->select('id')
                        ->whereIn('product_type', ['regular', 'variants'])
                        ->with([
                            'categories' => function($query){
                                $query->selectRaw('categories.id, categories.category_id')->with([
                                    'category' => function($query) {
                                        $query->selectRaw('categories.id, categories.category_id')->with([
                                            'category:id,category_id',
                                        ]);
                                    }
                                ]);
                            }
                        ])
                        ->whereHas('categories')
                        ->get();

        foreach ($products as $product) {
            $assignedIds = $product->categories->pluck('id')->toArray();
            $missingIds = [];

            foreach ($product->categories as $category) {
                if ( $category->category_id && !in_array($category->category_id, $assignedIds) ){
                    $missingIds[] = $category->category_id;

                    if ( $category->category && $category->category->category_id ){
                        $missingIds[] = $category->category->category_id;
                    }
                }
            }

            if ( count($missingIds) ){
                $ids = array_merge($assignedIds, $missingIds);
                asort($ids);
                $ids = array_values($ids);

                $product->categories()->detach($ids);
                $product->categories()->sync($ids);

                $this->line('Fixing tree for product id: '.$product->getKey());
            }
        }

        $this->line('Products categories fixed.');
    }
}
