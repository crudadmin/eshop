<?php

namespace AdminEshop\Contracts\Order\Validation;

class Validator
{
    /**
     * Message of validator
     *
     * @var  string|null
     */
    protected $message;

    /**
     * Set if given validator is active
     *
     * @return  bool
     */
    public function isActive()
    {
        return true;
    }

    /**
     * Define if order validation passes
     *
     * @return  bool
     */
    public function pass()
    {
        return false;
    }

    /**
     * Returns validation errormessage
     *
     * @return  string|null
     */
    public function getMessage()
    {
        return $this->message ?: _('Veľmi sa ospravedlňujeme, no nastala u nás nečakná chyba, skúste neskôr prosím.');
    }
}

?>