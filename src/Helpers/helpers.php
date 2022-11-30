<?php

use AdminEshop\Http\Resources\NuxtApiResponse;

if ( !function_exists('api') ) {
    function api()
    {
        return new NuxtApiResponse(
            ...func_get_args()
        );
    }
}

function operator_types($except = [])
{
    $operators = [
        '+%' => _('% - Pričítať k aktualnej cene'),
        '-%' => _('% - Odčítať z aktuálnej ceny'),
        '+V' => _('+ Pripočítať k cene (s DPH)'),
        '-V' => _('- Odčítať z ceny (s DPH)'),
        '+' => _('+ Pripočítať k cene (bez DPH)'),
        '-' => _('- Odčítať z ceny (bez DPH)'),
        '*' => _('* Vynásobit cenu'),
        'abs' => _('Nová hodnota'),
    ];

    return array_diff_key($operators, array_flip($except));
}

/**
 * Modify number by operator
 * @param  decimal $number
 * @param  string $operator
 * @param  decimal $value
 * @param  decimal|nullable $vatValue
 */
function operator_modifier($number, $operator, $operatorValue, $vatValue = null)
{
    //Convert currency conversion into price change
    if ( in_array($operator, ['+', '-', '+V', '-V', 'abs']) ){
        $operatorValue = Store::calculateFromDefaultCurrency($operatorValue);
    }

    if ( $operator == '+%' ){
        $number = $number * (1 + ($operatorValue / 100));
    } else if ( $operator == '-%' ){
        return operator_modifier($number, '+%', -$operatorValue);
    } else if ( $operator == '+' ){
        $number += $operatorValue;
    } else if ( $operator == '-'){
        $number -= $operatorValue;
    } else if ( $operator == '+V' ){
        $number += Store::removeVat($operatorValue, $vatValue);
    } else if ( $operator == '-V'){
        $number -= Store::removeVat($operatorValue, $vatValue, false);
    } else if ( $operator == '*'){
        $number *= $operatorValue;
    } else if ( $operator == 'abs' ){
        $number = $operatorValue;
    }

    return $number;
}

function client()
{
    $guard = auth()->guard(config('admineshop.client.guard'));

    if ( ! $guard->check() ) {
        return null;
    }

    return $guard->user();
}

if ( !function_exists('phoneValidatorRule') )
{
    function phoneValidatorRule($rule = true)
    {
        $validation = config('admineshop.validation.phone_countries', 'SK');

        if ( $validation === false ){
            return '';
        }

        return ($rule == true ? 'phone:' : '').$validation;
    }
}
?>