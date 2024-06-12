<?php

namespace AdminEshop\Controllers\Feed;

use AdminEshop\Contracts\Feed\FeedGenerator;
use AdminEshop\Controllers\Controller;

class FeedController extends Controller
{
    public function index($type = 'heureka')
    {
        return (new FeedGenerator($type, request('locale'), request('currency')))->response();
    }
}
