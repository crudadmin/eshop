<?php

namespace AdminEshop\Contracts\Order;

use AdminEshop\Contracts\Order\Validation\StockValidator;

trait HasValidation
{
    /**
     * Which global validators will be applied on order validation
     * in every validation process
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
     * @param  array|null mutators
     *
     * @return  void
     */
    public function validate(array $mutators = null)
    {
        $validators = $this->orderValidators;

        $mutators = $mutators ?: $this->getMutators();

        //Register validators from all mutators
        foreach ($mutators as $mutator) {
            if ( is_string($mutator) ){
                $mutator = new $mutator;
            }

            $validators = array_merge($validators, $mutator->getValidators());
        }

        foreach ($validators as $validation) {
            $validation = new $validation;

            //Skip non active validator
            if ( $validation->isActive() === false ){
                continue;
            }

            if ( $validation->pass() === false )  {
                $this->errorMessages[] = $validation->getMessage();
            }
        }

        return $this;
    }

    /**
     * Check all order errors
     *
     * @param  array|null mutators
     *
     * @return  array|null
     */
    public function passesValidation(array $mutators = null)
    {
        $this->validate($mutators);

        return count($this->getErrorMessages()) === 0;
    }
}
?>