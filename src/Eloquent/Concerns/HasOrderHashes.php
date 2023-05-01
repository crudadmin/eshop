<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasOrderHashes
{
    public function getHash()
    {
        $key = (env('APP_KEY').$this->getKey().'XL');

        return hash('sha256', $key);
    }
}