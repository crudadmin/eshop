<?php

namespace AdminEshop\Contracts\Feed;
use Localization;

class FeedGenerator
{
    private $type;

    private $locale;

    public function __construct($type, $locale)
    {
        $this->type = $type;

        $this->locale = $locale;

        $this->setup();
    }

    public function setup()
    {
        ini_set('max_execution_time', 5 * 60);

        if ( $this->locale ){
            Localization::setLocale($this->locale);
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