<?php

namespace AdminEshop\Contracts\Order\Validation;

class DeliveryLocationValidator extends Validator
{
    /*
     * Pass validation
     */
    public function pass()
    {
        $mutator = $this->getMutator();

        //No delivery has been selected. Or delivery is simple missing.
        if ( !($activeResponse = $mutator->isActive()) || !($delivery = $activeResponse['delivery']) ){
            return true;
        }

        //If delivery does not have multiple locations
        if ( !$delivery->multiple_locations ) {
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