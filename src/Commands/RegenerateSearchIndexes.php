<?php

namespace AdminEshop\Commands;

use Illuminate\Console\Command;
use Admin;

class RegenerateSearchIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eshop:search-indexes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate search indexes';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach (Admin::getAdminModels() as $model) {
            if ( $model->getProperty('localeSearch') == true ){
                $this->regenerate($model);
            }
        }

    }

    private function regenerate($model)
    {
        $columns = ['id', 'fulltext_index', ...$model->searchableIndexes()];

        $this->line('Regenerating '.$model->getTable());

        $rows = $model->select($columns)->get();

        $total = $rows->count();
        $tenpercent = round($total / 10);

        $this->line('Rows: '.$total);

        foreach ($rows as $i => $row) {
            if ( $i % $tenpercent == 0 ){
                $this->line('Percentage: '.$i.'/'.$total);
            }

            $row->setSearchIndex();
            $row->save();


        }
    }
}
