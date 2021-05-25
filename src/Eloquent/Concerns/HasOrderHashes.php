<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasOrderHashes
{
    public function makePaymentHash(string $type)
    {
        $key = env('APP_KEY').$this->payment_method_id.$this->getKey().$type;

        return hash('sha256', sha1(md5(sha1(md5($key)))));
    }

    public function getHash()
    {
        $key = (env('APP_KEY').$this->getKey().'XL');

        return hash('sha256', $key);
    }
}