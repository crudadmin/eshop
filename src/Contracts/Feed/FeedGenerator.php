<?php

namespace AdminEshop\Contracts\Feed;
use Localization;
use Store;

class FeedGenerator
{
    private $type;

    private $locale;

    private $currency;

    public function __construct($type, $locale, $currency)
    {
        $this->type = $type;

        $this->locale = $locale;

        $this->currency = $currency;

        $this->setup();
    }

    public function setup()
    {
        ini_set('max_execution_time', 5 * 60);

        if ( $this->locale ){
            Localization::setLocale($this->locale);
        }

        if ( $this->currency ){
            Store::setCurrency(
                Store::getCurrencies()->firstWhere('code', $this->currency)
            );
        }
    }

    public function getFeed()
    {
        $feeds = config('admineshop.feeds.providers', []);

        if ( $feed = $feeds[$this->type] ?? null ){
            return new $feed;
        }
    }

    public function response()
    {
        if ( !($feed = $this->getFeed($this->type)) || $feed::isEnabled() === false ){
            abort(404);
        }

        $feed->setLocale($this->locale);

        if ( config('admineshop.feeds.debug', false) ){
            $data = $feed->data();
        } else {
            $data = $feed->getCachedData();
        }

        return response($data, 200, [
            'Content-Type' => $feed->getContentType(),
        ]);
    }
}
?>