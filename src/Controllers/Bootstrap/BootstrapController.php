<?php

namespace AdminEshop\Controllers\Bootstrap;

use AdminEshop\Contracts\Request\BootstrapRequest;
use Admin\Controllers\Controller;
use Cache;

class BootstrapController extends Controller
{
    public function index()
    {
        $cacheMinutes = config('admineshop.routes.bootstrap.cache');
        $bootstrapper = new (config('admineshop.routes.bootstrap.class'));

        //Return cached response
        if ( is_numeric($cacheMinutes) && $cacheMinutes >= 1 ) {
            $response = json_decode(Cache::remember($bootstrapper->getCacheKey(), $cacheMinutes * 60, function() use ($bootstrapper) {
                return json_encode($bootstrapper->request());
            }), true);
        }

        //Return uncached response
        else {
            $response = $bootstrapper->request();
        }

        return api($response);
    }
}
