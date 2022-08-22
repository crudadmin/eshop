<?php

namespace AdminEshop\Controllers\Feed;

use AdminEshop\Contracts\Feed\FeedGenerator;
use AdminEshop\Controllers\Controller;

class FeedController extends Controller
{
    public function index($type)
    {
        return (new FeedGenerator($type))->response();
    }
}
