<?php

namespace AdminEshop\Middleware;

use Closure;

class StoreMiddleware
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
        $tokenName = config('admineshop.cart.header_token');

        if ( config('admineshop.cart.session') === false && empty($request->header($tokenName)) ){
            return abort(401, $tokenName.' header has not been set.');
        }

        return $next($request);
    }
}
