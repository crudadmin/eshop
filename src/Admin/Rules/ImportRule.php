<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Exception;
use Log;

class ImportRule extends AdminRule
{
    protected $importer;

    private function setConfigIni()
    {
        //Set limits
        ini_set('max_execution_time', 1200);
        ini_set('memory_limit', '2048M');
    }

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
            autoAjax()->error(_('Nebol nájdený žiaden importný systém pre tento typ importu.'))->throw();
        }

        $this->setConfigIni();

        try {
            $this->importer = new $importer['class']($row->file);

            $this->importer->checkColumnsAviability();
        } catch (Exception $e){
            Log::error($e);

            autoAjax()->error($e->getMessage())->throw();
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