<?php

namespace AdminEshop\Contracts\Order\Validation;

class DeliveryPointValidator extends Validator
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

        if ( !($shippingProvider = $delivery->getShippingProvider()) || $shippingProvider->hasPickupPoints() === false ){
            return true;
        }

        return $shippingProvider->getPickupPoint() ? true : false;
    }

    /**
     * Returns validation message
     *
     * @return  message
     */
    public function getMessage()
    {
        return _('Nevybrali ste konkretný odberný bod pri danom type doručenia.');
    }
}

?>