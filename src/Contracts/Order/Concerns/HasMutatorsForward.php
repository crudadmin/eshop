<?php

namespace AdminEshop\Contracts\Order\Concerns;

use BadMethodCallException;
use Store;

trait HasMutatorsForward
{
    public function __call($method, $parameters)
    {
        if ( $mutator = $this->getMutatorFromCall($method) ){
            return $mutator;
        }

        if ( method_exists($this, $method) == false ){
            $this->throwBadMethodCallException($method);
        }

        return $this->{$method}(...$parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    private function getMutatorFromCall($method)
    {
        if ( substr($method, 0, 3) == 'get' && strtolower(substr($method, -7)) == 'mutator' ) {
            $lowerCaseMethod = strtolower(substr($method, 3));

            $classess = $this->getMutatorNames();

            if ( array_key_exists($lowerCaseMethod, $classess) ) {
                return $classess[$lowerCaseMethod];
            }
        }
    }

    private function getMutatorNames()
    {
        return Store::cache('mutators.classmap', function(){
            $mutators = config('admineshop.cart.mutators');

            $classmap = [];

            foreach ($mutators as $classname) {
                $name = strtolower(class_basename($classname));

                $classmap[$name] = new $classname;
            }

            return $classmap;
        });
    }

    /**
     * Throw a bad method call exception for the given method.
     *
     * @param  string  $method
     * @return void
     *
     * @throws \BadMethodCallException
     */
    protected static function throwBadMethodCallException($method)
    {
        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}
?>