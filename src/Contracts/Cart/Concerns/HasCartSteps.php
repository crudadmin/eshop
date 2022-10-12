<?php

namespace AdminEshop\Contracts\Cart\Concerns;

use OrderService;

trait HasCartSteps
{
    public function getCartSteps()
    {
        //Fallback config for 3.3 version
        //Depreaced, can be removed in the future.
        if ( ($fallbackStepMutators = config('admineshop.cart.validation_steps')) && count($fallbackStepMutators) ){
            $steps = [];

            foreach ($fallbackStepMutators as $key => $mutators) {
                $steps[] = [
                    'name' => $key,
                    'mutators' => $mutators,
                ];
            }
        }

        //Latest version
        else {
            $steps = config('admineshop.cart.steps', []);
        }

        return collect($steps);
    }

    private function getStepIndex($stepName)
    {
        //Get current cart step
        return $this->getCartSteps()->search(function($step) use ($stepName) {
            return $step['name'] == $stepName;
        });
    }

    public function getCartStep($stepName)
    {
        $steps = $this->getCartSteps();
        $stepIndex = $this->getStepIndex($stepName);

        return is_numeric($stepIndex) ? ($steps[$stepIndex] ?? null) : null;
    }

    public function getStepValidators($stepName)
    {
        $steps = $this->getCartSteps();

        $stepIndex = $this->getStepIndex($stepName);
        $mutatorsToValidate = [];

        if ( is_numeric($stepIndex) ){
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
        }

        return array_values(array_unique($mutatorsToValidate));
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

    public function getCartStepResponse($stepName)
    {
        $step = $this->getCartStep($stepName);

        $mutators = $step ? ($step['mutators'] ?? []) : null;

        return $this->response(true, $mutators);
    }
}

?>