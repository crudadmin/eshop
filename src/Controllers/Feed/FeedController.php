<?php

namespace AdminEshop\Controllers\Feed;

use Admin\Eloquent\AdminModel;
use AdminEshop\Controllers\Controller;
use AdminEshop\Contracts\Feed\FeedGenerator;

class FeedController extends Controller
{
    public function index($type = 'heureka')
    {
        AdminModel::$localizedResponseArray = false;

        return (new FeedGenerator($type, request('locale'), request('currency')))->response();
    }
}
