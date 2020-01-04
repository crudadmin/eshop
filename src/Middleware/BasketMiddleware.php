<?php

namespace AdminEshop\Middleware;

use Closure;
use Basket;

class BasketMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $type = null)
    {
        return $next($request);
    }
}
