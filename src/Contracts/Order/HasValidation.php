<?php

namespace AdminEshop\Contracts\Order;

use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Contracts\Order\Validation\StockValidator;

trait HasValidation
{
    /**
     * Which global validators will be applied on order validation
     * in every validation process
     *
     * @var  array
     */
    protected $orderValidators = [];

    /**
     * Error messages from validators
     *
     * @var  array
     */
    protected $errorMessages = [];

    /**
     * Error validators
     *
     * @var  array
     */
    protected $invalidValidators = [];

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
     * Returns error validators
     *
     * @return  array
     */
    public function getInvalidValidators()
    {
        return $this->invalidValidators ?: [];
    }

    /**
     * Validate order
     *
     * @param  array|null mutators
     * @param  bool $submitOrder
     *
     * @return  void
     */
    public function validate(array $mutators = null, $submitOrder = false)
    {
        $toValidate = [
            [
                'mutator' => null,
                'validators' => $this->orderValidators,
            ],
        ];

        $mutators = $mutators ?: $this->getMutators();

        //Register validators from all mutators
        foreach ($mutators as $mutator) {
            if ( is_string($mutator) ){
                $mutator = (new $mutator)->bootMutator();
            }

            $toValidate[] = [
                'mutator' => $mutator,
                'validators' => $mutator->getValidators(),
            ];
        }

        foreach ($toValidate as $row) {
            $isMutator = isset($row['mutator']) && $row['mutator'] instanceof Mutator;

            foreach ($row['validators'] as $validator) {
                $validator = new $validator;

                //Filter only submitOnly validators
                if ( $validator->isOnlyOrderSubmit() === true && $submitOrder === false ){
                    continue;
                }

                //Pass mutator into validator
                if ( $isMutator ){
                    $validator->setMutator($row['mutator']);
                }

                //Skip non active validator
                if ( $validator->isActive() === false ){
                    continue;
                }

                if ( ! $validator->pass() )  {
                    $this->errorMessages[] = $validator->getMessage();
                    $this->invalidValidators[] = $validator;
                }
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
    public function passesValidation(array $mutators = null, $submitOrder = false)
    {
        $this->validate($mutators, $submitOrder);

        return count($this->getErrorMessages()) === 0;
    }
}
?>