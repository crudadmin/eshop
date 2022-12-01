<?php

namespace AdminEshop\Contracts\Concerns;

interface HasStoreAttributes
{
    /**
     * Returns export string
     *
     * @return  string
     */
    public function getStoreExportString();
}