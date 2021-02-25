<?php

namespace AdminEshop\Contracts\Cart\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

trait ActiveResponse
{
    /**
     * Response from isActive/isActiveInAdmin methods
     *
     * @var  mixed
     */
    protected $activeResponse;

    /**
     * Set activeResponse
     *
     * @var mixed $response
     *
     * @return  bool
     */
    public function setActiveResponse($response)
    {
        $this->activeResponse = $response;
    }

    /**
     * Returns active response
     *
     * @return  mixed
     */
    public function getActiveResponse()
    {
        return $this->activeResponse;
    }

    /**
     * Returns serialized active response
     *
     * @return  mixed
     */
    public function getSerializedResponse()
    {
        $response = $this->getActiveResponse();

        try {
            //We can serialize also additional model data
            if ( $response instanceof Model || $response instanceof EloquentCollection ) {
                $response = new ActiveResponseSerializator($response);
            }

            return serialize($response);
        } catch (Exception $e){
            throw new Exception($e->getMessage().' - Passed data into discounts and order mutators active state may be only: string, numeric, array, boolean, Eloquent, EloquentCollection.');
        }
    }

    public function unserializeResponse($response)
    {
        $response = unserialize($response);

        if ( $response instanceof ActiveResponseSerializator ) {
            return $response->getValue();
        }

        return $response;
    }

    /**
     * Alias for response
     *
     * @return  mixed
     */
    public function getResponse()
    {
        return $this->getActiveResponse();
    }
}