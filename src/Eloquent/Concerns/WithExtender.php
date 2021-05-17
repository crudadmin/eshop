<?php

namespace AdminEshop\Eloquent\Concerns;

trait WithExtender
{
    /**
     * Register with relationship, or extend existing if does exists.
     *
     * @param  Builder  $query
     * @param  Relationships  $array
     */
    public function scopeExtendWith($query, $array)
    {
        $eagerLoads = $query->getEagerLoads();

        foreach ($array as $key => $scope) {
            //Register basic with relation
            if ( is_numeric($key) ){
                // $query->with($scope);
            }

            //Extend if relation does exists
            if ( is_string($key) ){
                $eagerLoads = array_merge($eagerLoads, [
                    $key => function($query) use ($eagerLoads, $key, $scope) {
                        if ( isset($eagerLoads[$key]) ) {
                            $eagerLoads[$key]($query);
                        }

                        $scope($query);
                    },
                ]);
            }
        }

        $query->setEagerLoads($eagerLoads);
    }
}