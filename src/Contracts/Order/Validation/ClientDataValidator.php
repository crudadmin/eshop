<?php

namespace AdminEshop\Contracts\Order\Validation;

class ClientDataValidator extends Validator
{
    /*
     * Pass validation
     */
    public function pass()
    {
        return $this->getMutator()->isActive() ?: false;
    }

    /**
     * Returns validation message
     *
     * @return  message
     */
    public function getMessage()
    {
        return _('Nevyplnili ste informácie o objednávke. Prekontrolujte prosím predchadzajúce kroky Vášho košíka.');
    }
}

?>