<?php

namespace AdminEshop\Contracts\Order\Validation;

class PaymentMethodValidator extends Validator
{
    /*
     * Pass validation
     */
    public function pass()
    {
        return $this->getMutator()->getSelectedPaymentMethod() ?: false;
    }

    /**
     * Returns validation message
     *
     * @return  message
     */
    public function getMessage()
    {
        return _('Nevybrali ste žiadnu platobnú metódu, prekontrolujte prosím predchádzajúce kroky vášho košíka.');
    }
}

?>