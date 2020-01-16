<?php

namespace AdminEshop\Contracts\Order\Validation;

use Facades\AdminEshop\Contracts\Order\Mutators\PaymentMethodMutator;
use AdminEshop\Contracts\Order\Validation\Validation;

class PaymentMethodValidator extends Validator
{
    /*
     * Pass validation
     */
    public function pass()
    {
        return PaymentMethodMutator::isActive() ?: false;
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