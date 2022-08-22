<?php

namespace AdminEshop\Contracts\Feed;

class FeedGenerator
{
    private $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function setup()
    {
        ini_set('max_execution_time', 5 * 60);
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