<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasOrderHashes
{
    public function makePaymentHash(string $type)
    {
        return sha1(md5(sha1(md5(env('APP_KEY').$this->payment_method_id.$this->getKey().$type))));
    }

    public function getHash()
    {
        return sha1(env('APP_KEY').$this->getKey().'XL');
    }
}