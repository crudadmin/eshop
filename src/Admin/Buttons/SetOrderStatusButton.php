<?php

namespace AdminEshop\Admin\Buttons;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Admin;

class SetOrderStatusButton extends Button
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct()
    {
        //Name of button on hover
        $this->name = _('Zmeniť stav objednávky');

        //Button classes
        $this->class = 'btn-default';

        //Button Icon
        $this->icon = 'fa-check-circle';

        $this->type = 'action';
    }

    /*
     * Ask question with form before action
     */
    public function question()
    {
        $statuses = Admin::getModel('OrdersStatus')->pluck('name', 'id');

        return $this->title(_('Vyberte novy stav objednávky:'))
                    ->component('SetOrderStatus', [
                        'statuses' => $statuses,
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
        $statusId = request('status');

        if ( !$statusId ) {
            return $this->error(_('Nezvolili ste stav objednávky.'));
        }

        $rows->each(function($row) use ($statusId) {
            $row->update(['status_id' => $statusId]);
        });

        return $this->success(_('Stav objednávky bol úspešne zmenený.'));
    }
}