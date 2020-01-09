<?php
function operator_types($allow = null, $except = [])
{
    $operators = [
        'default' => 'Použiť predvolené',
        '+%' => '% - Pričítať k aktualnej cene',
        '-%' => '% - Odčítať z aktuálnej ceny',
        '+' => '+ Pripočítať k cene',
        '-' => '- Odčítať z ceny',
        '*' => '* Vynásobit cenu',
    ];

    return array_diff_key($operators, array_flip($except));
}

/**
 * Modify number by operator
 * @param  [type] $number   [description]
 * @param  [type] $operator [description]
 * @param  [type] $value    [description]
 * @param  [type] $count    for basolute values, we have two options of modifing number. first ist counting, and second replacing value
 * @return [type]           [description]
 */
function operator_modifier($number, $operator, $value)
{
    if ( $operator == '+%' ){
        $number = $number * (1 + ($value / 100));
    } else if ( $operator == '-%' ){
        return operator_modifier($number, '+%', -$value);
    } else if ( $operator == '+' ){
        $number += $value;
    } else if ( $operator == '-'){
        $number -= $value;
    } else if ( $operator == '*'){
        $number *= $value;
    } else if ( $operator == 'abs' ){
        $number = $value;
    }

    return $number;
}

function client()
{
    $guard = auth()->guard('client');

    if ( ! $guard->check() )
        return null;

    return $guard->user();
}
?>