<?php

namespace AdminEshop\Admin\Buttons;

use AdminEshop\Contracts\Delivery\DPD\DPDShipping;
use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Illuminate\Support\Collection;
use OrderService;

class DPDExportButton extends Button
{
    /*
     * Button type
     * button|action|multiple
     */
    public $type = 'action';

    //Name of button on hover
    public $name = 'DPD Export objednávok';

    //Button classes
    public $class = 'btn-default';

    //Button Icon
    public $icon = 'fa-truck';

    /**
     * Here you can set your custom properties for each row
     * @param Admin\Models\Model $row
     */
    public function __construct($row)
    {
        $this->active = true;
    }

    /**
     * Firing callback on press action for multiple items
     * @param Illuminate\Support\Collection $rows
     */
    public function fireMultiple(Collection $rows)
    {
        $path = OrderService::makeShippingExport(DPDShipping::class, $rows);

        return $this->message('Export bol úspešne vytvorený.')->download($path);
    }
}