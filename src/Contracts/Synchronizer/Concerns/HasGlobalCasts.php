<?php

namespace AdminEshop\Contracts\Synchronizer\Concerns;

trait HasGlobalCasts
{
    public function castEditorValue($value)
    {
        if ( is_array($value) ){
            foreach ($value as $key => $string) {
                $value[$key] = $this->castEditorValue($string);
            }
        } else if ( is_string($value) && $value && strpos($value, '<p>') === false ) {
            $value = '<p>'.$value.'</p>';
        }

        return $value;
    }
}