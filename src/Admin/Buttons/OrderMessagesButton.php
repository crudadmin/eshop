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
        $this->name = $this->getOrderLogContent($row, false);

        //Button classes
        $this->class = 'btn-warning';

        //Button Icon
        $this->icon = 'fa-exclamation-triangle';

        //Allow button only when invoices are created
        $this->active = $row->log->count() > 0;

        //Should be tooltip encoded?
        $this->tooltipEncode = false;
    }

    private function getOrderLogContent($row, $withLog = false)
    {
        $lines = [
            '<strong>Hlásenia:</strong>'
        ];

        $total = $row->log->count();

        foreach ($row->log as $i => $log) {
            $id = 'id-'.$log->getKey();

            $logInfo = '';

            $color = $log->type == 'error' ? 'red' : 'inherit';

            $message = $log->getSelectOption('code').' '.$log->created_at->format('d.m.Y H:i').($log->message ? ' - '.$log->message : '');

            //Add clone log into clipboard
            if ( $log->log && $withLog == true ) {
                $logInfo = '
                    <i class="fa fa-info-circle" data-toggle="tooltip" title="Nakopírovať hlásenie" onclick="var t = this.nextElementSibling; t.style.display = \'block\'; t.select();document.execCommand(\'copy\'); t.style.display = \'none\'"></i>
                    <textarea id="'.$id.'" style="display: none">'.e($log->log).'</textarea>
                ';
            }

            $lines[] = '<span class="log-type-'.$log->type.'">'.$logInfo.' '.$message.'</span>';
        }

        return implode('<br>', $lines);
    }

    /*
     * Firing callback on press button
     */
    public function fire(AdminModel $row)
    {
        return $this->title('Hlásenia ('.$row->log->count().')')->warning(
            $this->getOrderLogContent($row, true)
        );
    }
}