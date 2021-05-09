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
        return _('Nastala nečakaná chyba pri ukladani informácii o klientovi, skúste neskôr prosím, poprípade nás kontaktujte.');
    }
}

?>