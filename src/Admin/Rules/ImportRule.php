<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Ajax;
use Exception;
use Log;

class ImportRule extends AdminRule
{
    protected $importer;

    public function creating(AdminModel $row)
    {
        $this->bootImport($row);
    }

    public function updating(AdminModel $row)
    {
        $this->bootImport($row);
    }

    public function bootImport($row)
    {
        if ( !($importer = $row->getImporter()) ){
            Ajax::error('Nebol nájdený žiaden importný systém pre tento typ importu.');
        }

        try {
            $this->importer = new $importer['class']($row->file);

            $this->importer->checkColumnsAviability();
        } catch (Exception $e){
            Log::error($e);

            Ajax::error($e->getMessage());
        }
    }

    public function created(AdminModel $row)
    {
        $this->importer->import($row);
    }

    public function updated(AdminModel $row)
    {
        $this->importer->import($row);
    }
}