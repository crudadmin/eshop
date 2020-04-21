<?php

namespace AdminEshop\Contracts\Order\Validation;

use Facades\AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
use AdminEshop\Contracts\Order\Validation\Validation;

class DeliveryLocationValidator extends Validator
{
    /*
     * Pass validation
     */
    public function pass()
    {
        //If delivery has multiple locations
        if ( ! DeliveryMutator::isActive() || !($delivery = DeliveryMutator::getSelectedDelivery())->multiple_locations ) {
            return true;
        }

        return DeliveryMutator::getSelectedLocation();
    }

    /**
     * Returns validation message
     *
     * @return  message
     */
    public function getMessage()
    {
        return _('Nevybrali ste konkretnú predajňu pri danom type doručenia. prekontrolujte prosím predchádzajúce kroky vášho košíka.');
    }
}

?>