<?php

namespace AdminEshop\Contracts\Order\Validation;

use AdminEshop\Contracts\Order\Mutators\Mutator;

class Validator
{
    /**
     * Message of validator
     *
     * @var  string|null
     */
    protected $message;

    private $mutator;

    /**
     * Enable validator only on orders submit
     *
     * @var  bool
     */
    protected $onSubmitOnly = false;

    /**
     * Set mutator
     *
     * @param  Mutator  $mutator
     */
    public function setMutator(Mutator $mutator)
    {
        $this->mutator = $mutator;
    }

    /**
     * Return mutator
     *
     * @return  AdminEshop\Contracts\Order\Mutators\Mutator
     */
    public function getMutator()
    {
        return $this->mutator;
    }

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

    /**
     * Validate only on order submit
     *
     * @return  bool
     */
    public function isOnlyOrderSubmit()
    {
        return $this->onSubmitOnly === true;
    }
}

?>