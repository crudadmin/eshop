<?php

namespace AdminEshop\Admin\Buttons;

use AdminEshop\Contracts\Delivery\DPD\DPDShipping;
use Admin\Contracts\Exports\AdminButtonExport;

class DPDExportButton extends AdminButtonExport
{
    //Button Icon
    public $icon = 'fa-file-export';

    /**
     * Here you can set your custom properties for each row
     * @param Admin\Models\Model $row
     */
    public function __construct($row)
    {
        $this->name = _('DPD Export');

        $this->active = true;
    }

    public function fire($row)
    {
        return $this->fireMultiple(collect([$row]));
    }

    /**
     * Firing callback on press action for multiple items
     * @param Illuminate\Support\Collection $rows
     */
    public function fireMultiple($rows)
    {
        $data = DPDShipping::export($rows);

        $path = $this->path('export/dpd_export.xml');

        // Save file
        $this->storage()->put($path, $data['data']);

        // Get path
        $basepath = $this->storage()->path($path);

        return $this->downloadResponse($basepath);
    }
}