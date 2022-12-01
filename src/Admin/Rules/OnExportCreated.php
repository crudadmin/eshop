<?php

namespace AdminEshop\Admin\Rules;

use AdminEshop\Contracts\Delivery\ShippingProvider;
use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Exception;
use Log;
use OrderService;

class OnExportCreated extends AdminRule
{
    public function creating(AdminModel $row)
    {
        $this->createExport($row);
    }

    public function createExport($row)
    {
        //Set limits
        ini_set('max_execution_time', 300);

        if ( !($exporter = ($row->getExportTypes()[$row->type] ?? null)) ){
            autoAjax()->error(_('Nebol nájdený žiaden exporter.'))->throw();
        }

        try {
            $provider = new $exporter['class'];

            $response = '';

            if ( $provider instanceof ShippingProvider ){
                $response = $provider->getExportData($row);
            } else {
                //Todo make support for Excel/Csv responses from maatwebsite/excel package
            }

            $extension = $response['extension'];

            $filename = $row->type.'_export-'.str_random(10).'.'.$extension;

            $path = $row->getStorageFilePath('file', $filename);

            $row->getFieldStorage('file')->put($path, $response['data']);

            $row->file = $filename;
        } catch (Exception $e){
            Log::error($e);

            autoAjax()->error($e->getMessage())->throw();
        }
    }
}