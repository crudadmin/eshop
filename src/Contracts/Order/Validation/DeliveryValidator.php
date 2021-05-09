<?php

namespace AdminEshop\Contracts\Order\Validation;

class DeliveryValidator extends Validator
{
    /*
     * Pass validation
     */
    public function pass()
    {
        return $this->getMutator()->getSelectedDelivery() ?: false;
    }

    /**
     * Returns validation message
     *
     * @return  message
     */
    public function getMessage()
    {
        return _('Nevybrali ste žiadnu dopravu, prekontrolujte prosím predchádzajúce kroky vášho košíka.');
    }
}

?>