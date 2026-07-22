<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Store;

class RebuildOrder extends AdminRule
{
    /*
     * Firing callback on create row
     */
    public function created(AdminModel $row)
    {
        //If is order created via admin, then uncount
        $row->syncStock('-', 'order.new-backend');

        $row->calculatePrices();
    }

    /*
     * Firing callback on update row
     */
    public function updated(AdminModel $row)
    {
        //If order is canceled, then add products back to stock
        if (
            Store::getOrdersStatus($row->status_id)?->return_stock === true
            && Store::getOrdersStatus($row->getOriginal('status_id'))?->return_stock !== true
        ) {
            $row->syncStock('+', 'order.canceled');
        }

        // Recalculate order on save if priceable fields have been changed
        if ( $this->hasChangedPriceableFields($row) ) {
            $this->tryRecalculateOrder($row);
        }
    }

    /*
     * On delete product from admin, add goods back to stock
     */
    public function deleted($row)
    {
        //If order has been uncounted from stock yet
        if ( Store::getOrdersStatus($row->status_id)?->return_stock !== true ) {
            $row->syncStock('+', 'order.deleted');
        }
    }

    /**
     * Check if order has changed priceable fields
     *
     * @param  mixed $row
     * @return void
     */
    private function hasChangedPriceableFields(AdminModel $row)
    {
        $priceableFields = $row->getPriceableFields();

        $isModelDirty = $row->isDirty($priceableFields);

        $isAdminDirty = $this->isFieldDirty($priceableFields);

        return $isModelDirty || $isAdminDirty;
    }

    /**
     * Recalculate final order price if any of field has been changed
     *
     * @param  mixed $row
     * @return void
     */
    private function tryRecalculateOrder(AdminModel $row)
    {
        $priceBefore = (float)$row->getOriginal('price_vat');

        //Change delivery prices etc..
        $row->calculatePrices();

        //If order price has been changed on the background,
        //we need notify user about this. Because sometimes bug may happend!
        //We need know about that, especially administrator to findout that something is wrong.
        if ( $priceBefore !== (float)$row->price_vat ) {
            autoAjax()->warning(
                sprintf(
                    _('Cena objednávky bola po uložení zmenená z <strong>%s</strong> na <strong>%s</strong>.'),
                    Store::priceFormat($priceBefore),
                    Store::priceFormat($row->price_vat),
                )
            );
        }
    }
}