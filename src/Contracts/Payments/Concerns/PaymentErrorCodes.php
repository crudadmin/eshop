<?php

namespace AdminEshop\Contracts\Payments\Concerns;

use Exception;

class PaymentErrorCodes
{
    const CODE_ERROR = 0;
    const CODE_PAYMENT_UNVERIFIED = 1;
    const CODE_PAID = 2;

    public static $messages = [];

    public static function setMessages($messages)
    {
        self::$messages = $messages;
    }

    static function getMessages()
    {
        return self::$messages + [
            self::CODE_ERROR => _('Nastala nečakaná chyba pri spracovani platby. Skúste neskôr prosím.'),
            self::CODE_PAYMENT_UNVERIFIED => _('Vaša objednávka bola úspešne zaznamenaná, no prevod Vašej platby sme zatiaľ nezaznamenali. V prípade opätovnej platby môžete využiť možnosť platby z emailu, alebo nás kontaktujte pre ďalšie informácie.'),
            self::CODE_PAID => _('Vaša objednávka už bola úspešne zaplatená.'),
        ];
    }

    static function getMessage($code)
    {
        return self::getMessages()[$code] ?? null;
    }
}