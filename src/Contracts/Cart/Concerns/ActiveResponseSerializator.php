<?php

namespace AdminEshop\Contracts\Cart\Concerns;

use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Queue\SerializesModels;

class ActiveResponseSerializator
{
    use SerializesModels;

    /**
     * Serialization response
     *
     * @var  mixed
     */
    public $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}