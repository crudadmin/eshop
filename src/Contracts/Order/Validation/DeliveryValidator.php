<?php

namespace AdminEshop\Contracts\Order\Validation;

use Facades\AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
use AdminEshop\Contracts\Order\Validation\Validation;

class DeliveryValidator extends Validator
{
    /*
     * Pass validation
     */
    public function pass()
    {
        return DeliveryMutator::getSelectedDelivery() ?: false;
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