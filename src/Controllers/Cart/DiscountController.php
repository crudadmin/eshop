<?php

namespace AdminEshop\Controllers\Cart;

use AdminEshop\Contracts\Discounts\DiscountCode;
use AdminEshop\Controllers\Controller;
use AdminEshop\Events\DiscountCodeAdded;
use Cart;
use Illuminate\Validation\ValidationException;
use OrderService;

class DiscountController extends Controller
{
    public function addDiscountCode()
    {
        $codeName = request('code');

        validator()->make(request()->all(), ['code' => 'required'])->validate();

        $discount = OrderService::getDiscountCodeDiscount();

        $codes = $discount->getDiscountCodes([
            $codeName
        ]);

        //Validate code and throw error
        foreach ($codes as $code) {
            if ( $errorMessage = $discount->getCodeError($code) ){
                throw ValidationException::withMessages([
                    'code' => $errorMessage,
                ]);
            }

            $discount->setDiscountCode($code->code);

            //Event for added discount code
            event(new DiscountCodeAdded($code));
        }

        return api(
            Cart::baseResponse()
        );
    }

    public function removeDiscountCode()
    {
        OrderService::getDiscountCodeDiscount()->removeDiscountCode(request('code'));

        return api(
            Cart::baseResponse()
        );
    }
}
