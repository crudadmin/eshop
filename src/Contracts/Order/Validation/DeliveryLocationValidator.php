<?php

namespace AdminEshop\Contracts\Order\Validation;

use AdminEshop\Contracts\Order\Validation\Validation;

class DeliveryLocationValidator extends Validator
{
    /*
     * Pass validation
     */
    public function pass()
    {
        $mutator = $this->getMutator();

        $delivery = $mutator->isActive();

        //If delivery has multiple locations
        if ( ! ($delivery = $mutator->isActive()) || !$delivery->multiple_locations ) {
            return true;
        }

        return $mutator->getSelectedLocation();
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