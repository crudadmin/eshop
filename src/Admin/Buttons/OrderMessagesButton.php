<?php

namespace AdminEshop\Admin\Buttons;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;

class OrderMessagesButton extends Button
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct(AdminModel $row)
    {
        //Name of button on hover
        $this->name = $this->getOrderLogContent($row);

        //Button classes
        $this->class = 'btn-warning';

        //Button Icon
        $this->icon = 'fa-exclamation-triangle';

        //Allow button only when invoices are created
        $this->active = $row->log->count() > 0;

        //Should be tooltip encoded?
        $this->tooltipEncode = false;
    }

    private function getOrderLogContent($row)
    {
        $lines = [
            '<strong>Hlásenia:</strong>'
        ];

        $total = $row->log->count();

        foreach ($row->log as $i => $log) {
            $color = $log->type == 'error' ? 'red' : 'inherit';

            $message = ($total - $i).'. '.$log->getSelectOption('code').''.($log->message ? ' - '.$log->message : '');

            $lines[] = '<span class="log-type-'.$log->type.'">'.$message.'</span>';
        }

        return implode('<br>', $lines);
    }

    /*
     * Firing callback on press button
     */
    public function fire(AdminModel $row)
    {
        return $this->warning(
            $this->getOrderLogContent($row)
        );
    }
}