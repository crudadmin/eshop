<?php

namespace AdminEshop\Contracts\Cart\Concerns;

use OrderService;

trait HasCartSteps
{
    public static function getCartSteps()
    {
        return collect(array_merge([
            // Default initial step to use also global mutators
            [
                'name' => '_global',
                'mutators' => config('admineshop.cart.mutators'),
            ]
        ], config('admineshop.cart.steps', [])));
    }

    private function getStepIndex($stepName)
    {
        //Get current cart step
        $stepIndex = $this->getCartSteps()->search(function($step) use ($stepName) {
            return $step['name'] == $stepName;
        });

        return is_numeric($stepIndex) ? $stepIndex : -1;
    }

    public function getCartStep($stepName)
    {
        $steps = $this->getCartSteps();
        $stepIndex = $this->getStepIndex($stepName);

        return $steps[$stepIndex] ?? null;
    }

    public function getStepValidators($stepName)
    {
        $steps = $this->getCartSteps();

        $stepIndex = $this->getStepIndex($stepName);
        $mutatorsToValidate = [];

        for ($i=0; $i <= $stepIndex; $i++) {
            $step = $steps[$i];

            //Do not validate current step mutators. Only from previous.
            //If current step has some validators, we may validate only them.
            $stepValidators = $i === $stepIndex
                                        ? ($step['validators'] ?? [])
                                        : array_merge(
                                            $step['validators'] ?? [],
                                            $step['mutators'] ?? []
                                        );

            $mutatorsToValidate = array_merge($mutatorsToValidate, $stepValidators);
        }

        return array_values(array_unique($mutatorsToValidate));
    }

    public function getStepMutators($stepName)
    {
        $steps = $this->getCartSteps();

        $stepIndex = $this->getStepIndex($stepName);
        $mutators = [];

        for ($i=0; $i <= $stepIndex; $i++) {
            $step = $steps[$i];

            $mutators = array_merge($mutators, array_merge(
                $step['validators'] ?? [],
                $step['mutators'] ?? []
            ));
        }

        return array_values(array_unique($mutators));
    }

    /**
     * Check cart step validation
     *
     * @param  string $stepName
     *
     */
    public function passesCartValidation($stepName, $submitOrder = false)
    {
        $toValidate = $this->getStepValidators($stepName);

        if ( count($toValidate) ){
            OrderService::validate($toValidate, $submitOrder);
        }

        return count(OrderService::getErrorMessages()) === 0;
    }
}

?>