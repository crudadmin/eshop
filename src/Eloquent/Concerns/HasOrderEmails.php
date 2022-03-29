<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasOrderEmails
{
    public function getClientEmailMessage()
    {
        if ( $this->status && $text = $this->status->parseOrderText('email_content', $this) ) {
            return $this->status->parseOrderText('email_content', $this);
        }

        return sprintf(_('Vaša objednávka č. %s zo dňa %s bola úspešne prijatá.'), $this->number, $this->created_at->format('d.m.Y'));
    }

    public function getStoreEmailMessage()
    {
        return sprintf(_('Gratulujeme! Obržali ste objednávku č. %s.'), $this->number);
    }

    public function getClientEmailSubject()
    {
        return _('Objednávka č. ') . $this->number;
    }

    public function getStoreEmailSubject()
    {
        return _('Objednávka č. ') . $this->number;
    }
}