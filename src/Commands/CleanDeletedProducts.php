<?php

namespace AdminEshop\Commands;

use Illuminate\Console\Command;
use Admin;

class CleanDeletedProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eshop:clean-deleted-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all relationships when products are deleted.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit', '1G');

        $model = Admin::getModel('Product');

        $products = $model
                        ->onlyTrashed()
                        ->selectOnlyRelationColumns()
                        ->get();

        $total = $products->count();

        foreach ($products as $i => $product) {
            $product->forceRemoveWithRelations();

            $this->line($i.'/'.$total);
        }

        $this->line('Products relationships deleted.');
    }
}
