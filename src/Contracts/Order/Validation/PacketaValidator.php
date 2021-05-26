<?php

namespace AdminEshop\Contracts\Order\Validation;

class PacketaValidator extends Validator
{
    /*
     * Pass validation
     */
    public function pass()
    {
        $mutator = $this->getMutator();

        //If is not packeta shipping
        if ( $mutator->isPacketaShipping() === false ){
            return true;
        }

        return $mutator->getSelectedPoint() ? true : false;
    }

    /**
     * Returns validation message
     *
     * @return  message
     */
    public function getMessage()
    {
        return _('Pri doprave Zasielkovňa ste nezvolili odberné miesto, kde si prajete svoj balík vyzdvihnúť.');
    }
}

?>