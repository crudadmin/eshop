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
        $this->name = _('DPD Export objednávok');

        $this->active = true;
    }

    /**
     * Firing callback on press action for multiple items
     * @param Illuminate\Support\Collection $rows
     */
    public function fireMultiple(Collection $rows)
    {
        $path = OrderService::makeShippingExport(DPDShipping::class, $rows);

        return $this->message(_('Export bol úspešne vytvorený.'))->download($path);
    }
}