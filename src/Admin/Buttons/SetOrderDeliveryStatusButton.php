<?php

namespace AdminEshop\Admin\Buttons;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Admin;

class SetOrderDeliveryStatusButton extends Button
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct()
    {
        //Name of button on hover
        $this->name = _('Zmeniť stav dopravy');

        //Button classes
        $this->class = 'btn-default';

        //Button Icon
        $this->icon = 'fa-truck';

        $this->type = 'action';
    }

    /*
     * Ask question with form before action
     */
    public function question()
    {
        return $this->title(_('Vyberte stav dopravy:'))
                    ->component('SetOrderDeliveryStatus', [
                        'statuses' => getDeliveryStates(),
                    ])
                    ->type('default');
    }

    /*
     * Firing callback on press button
     */
    public function fire(AdminModel $row)
    {
        return $this->fireMultiple(collect([$row]));
    }

    public function fireMultiple($rows)
    {
        $status = request('status');

        if ( !$status ) {
            return $this->error(_('Nezvolili ste stav objednávky.'));
        }

        $rows->each(function($row) use ($status) {
            $row->update(['delivery_status' => $status]);
        });

        return $this->success(_('Stav dopravy bol úspešne zmenený.'));
    }
}