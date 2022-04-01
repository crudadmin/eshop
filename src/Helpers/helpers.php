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
        '+%' => '% - Pričítať k aktualnej cene',
        '-%' => '% - Odčítať z aktuálnej ceny',
        '+V' => '+ Pripočítať k cene (s DPH)',
        '-V' => '- Odčítať z ceny (s DPH)',
        '+' => '+ Pripočítať k cene (bez DPH)',
        '-' => '- Odčítať z ceny (bez DPH)',
        '*' => '* Vynásobit cenu',
        'abs' => 'Nová hodnota',
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
    $guard = auth()->guard(config('admineshop.auth.guard'));

    if ( ! $guard->check() ) {
        return null;
    }

    return $guard->user();
}

if ( !function_exists('phoneValidatorRule') )
{
    function phoneValidatorRule()
    {
        return 'phone:'.config('admineshop.phone_countries', 'SK');
    }
}
?>