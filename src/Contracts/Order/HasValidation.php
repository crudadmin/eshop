<?php

namespace AdminEshop\Contracts\Order;

use AdminEshop\Contracts\Order\Validation\StockValidator;

trait HasValidation
{
    /**
     * Which validators will be applied on order validation
     *
     * @var  array
     */
    protected $orderValidators = [
        StockValidator::class,
    ];

    /**
     * Error messages from validators
     *
     * @var  array
     */
    protected $errorMessages = [];

    /**
     * Add order validator
     *
     * @param  string  $class
     */
    public function addOrderValidator($namespace)
    {
        $this->orderValidators[] = $namespace;
    }

    /**
     * Returns error messages
     *
     * @return  array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages ?: [];
    }

    /**
     * Validate order
     *
     * @return  void
     */
    public function validate()
    {
        $validators = $this->orderValidators;

        //Register validators from all mutators
        foreach ($this->getMutators() as $mutator) {
            $validators = array_merge($validators, $mutator->getValidators());
        }

        foreach ($validators as $validation) {
            $validation = new $validation;

            if ( $validation->pass() === false )  {
                $this->errorMessages[] = $validation->getMessage();
            }
        }

        return $this;
    }

    /**
     * Check all order errors
     *
     * @return  array|null
     */
    public function passesValidation()
    {
        $this->validate();

        return count($this->getErrorMessages()) === 0;
    }
}
?>