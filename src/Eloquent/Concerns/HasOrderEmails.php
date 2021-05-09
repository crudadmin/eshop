<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasOrderEmails
{
    public function getClientEmailMessage()
    {
        return sprintf(_('Vaša objednávka č. %s zo dňa %s bola úspešne prijatá.'), $this->number, $this->created_at->format('d.m.Y'));
    }

    public function getStoreEmailMessage()
    {
        return sprintf(_('Gratulujeme! Obržali ste objednávku č. %s.'), $this->number);
    }
}