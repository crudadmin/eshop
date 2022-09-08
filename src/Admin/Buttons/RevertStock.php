<?php

namespace AdminEshop\Admin\Buttons;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Illuminate\Support\Collection;

class RevertStock extends Button
{
    /*
     * Button type
     * button|action|multiple
     */
    public $type = 'multiple';

    //Button classes
    public $class = 'btn-default';

    //Button Icon
    public $icon = 'fa-history';

    public $reloadAll = true;

    /**
     * Here you can set your custom properties for each row
     * @param Admin\Models\Model $row
     */
    public function __construct($row)
    {
        $this->name = _('Vrátiť skladovosť');

        if ( $row instanceof AdminModel ){
            $this->active = $row->reverted == false && !$row->log_id;
        }
    }

    public function question($row)
    {
        return $this->warning(_('Naozaj chcete túto skladovú zmenu vrátiť?'));
    }

    /**
     * Firing callback on press button
     * @param Admin\Models\Model $row
     * @return object
     */
    public function fire(AdminModel $row)
    {
        return $this->fireMultiple(collect([$row]));
    }

    public function fireMultiple(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->revertRow($row);
        }

        return $this->success(_('Skladovosť bola úspešne vrátená.'));
    }

    private function revertRow($row)
    {
        $row->product->commitStockChange(
            '+',
            $row->sub * -1,
            $row->order_id,
            'revert',
            $row->getKey()
        );

        $row->update([
            'reverted' => true
        ]);
    }
}