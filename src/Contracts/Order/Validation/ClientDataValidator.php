<?php

namespace AdminEshop\Contracts\Order\Validation;

use Facades\AdminEshop\Contracts\Order\Mutators\ClientDataMutator;
use AdminEshop\Contracts\Order\Validation\Validation;

class ClientDataValidator extends Validator
{
    /*
     * Pass validation
     */
    public function pass()
    {
        return ClientDataMutator::isActive() ?: false;
    }

    /**
     * Returns validation message
     *
     * @return  message
     */
    public function getMessage()
    {
        return _('Nastala nečakaná chyba pri ukladani informácii o klientovi, skúste neskôr prosím, poprípade nás kontaktujte.');
    }
}

?>