<?php

namespace AdminEshop\Contracts\Order\Validation;

use Facades\AdminEshop\Contracts\Order\Mutators\CountryMutator;
use AdminEshop\Contracts\Order\Validation\Validation;

class CountryValidator extends Validator
{
    public function isActive()
    {
        return config('admineshop.delivery.countries') === true;
    }

    /*
     * Pass validation
     */
    public function pass()
    {
        return CountryMutator::getSelectedCountry() ? true : false;
    }

    /**
     * Returns validation message
     *
     * @return  message
     */
    public function getMessage()
    {
        return _('Nevybrali ste krajinu do ktorej si prajete doručiť tovar. Prekontrolujte prosím predchádzajúce kroky vášho košíka.');
    }
}

?>